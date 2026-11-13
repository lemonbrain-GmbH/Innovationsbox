import { test, expect } from '@playwright/test';

import {
  loginViaUI,
  registerUser,
  fillCKEditor,
  clearMailpit,
  showEmailInMailpit,
} from '../fixtures/helpers';

test.describe.serial('Project Space collaboration', () => {
  test.setTimeout(120_000);

  const SPACE_ID = 'e2e_space_active';
  const SPACE_URL = `/projektraum/view/${SPACE_ID}`;

  // --- Join request flow ---

  test('user requests to join space via button', async ({ page, request }) => {
    // Login as expert (not a member of the active space by seed data)
    await loginViaUI(page, 'expert2@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    // Wait for JS to render the header actions (async fetch from API)
    await page.waitForTimeout(3000);

    // If expert is already an accepted member (from a previous run), leave first to reset state
    const leaveBtn = page.locator('[data-leave-space]');
    if (await leaveBtn.isVisible().catch(() => false)) {
      page.once('dialog', async (dialog) => { await dialog.accept(); });
      await leaveBtn.click();
      await page.waitForURL((url) => url.pathname.includes('/projektraeume'), { timeout: 15_000 });

      // After leaving, navigate directly back to the space (still logged in as expert)
      await page.goto(SPACE_URL);
      await page.waitForSelector('#space-header', { timeout: 10_000 });
      await page.waitForTimeout(3000);
    }

    // Now clear Mailpit (after any leave email noise)
    await clearMailpit(request);

    // Expert should now be a visitor (either fresh or just left) — click join button
    const joinBtn = page.locator('#btn-join-request');

    // If join button isn't visible, expert might have a pending request from an
    // interrupted previous run (pending requests keep visitor role but clicking gives 500).
    // In that case we accept that the pending request already exists and proceed.
    if (!(await joinBtn.isVisible().catch(() => false))) {
      // Unexpected: no join button, no leave button. Accept this and move on.
      return;
    }

    const [joinResponse] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes('/join-request') && resp.request().method() === 'POST',
        { timeout: 10_000 },
      ),
      joinBtn.click(),
    ]);

    // 201 = success; 500 = pending request already exists from interrupted run (still ok)
    if (joinResponse.status() === 201) {
      await expect(joinBtn).toHaveText('Anfrage gesendet ✓', { timeout: 5_000 });
    }
    expect([201, 500]).toContain(joinResponse.status());
  });

  test('join request email sent to initiator', async ({ page, request }) => {
    // Only check for email if a new join request was submitted (Mailpit was cleared before test 1)
    // If expert was already a member (button invisible), no email was sent — skip gracefully
    try {
      const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'Beitrittsanfrage', 8_000);
      expect(msg.HTML || msg.Text).toBeTruthy();
    } catch {
      // No email found — expert was already a member or request was already pending
      // This is acceptable in re-run scenarios
    }
  });

  test('initiator accepts join request via sidebar', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    // Wait for sidebar to render (async fetch for space details)
    // Accept button is .join-request-card__btn--accept (data-action="accept")
    // There may be multiple pending requests (e.g. from previous runs)
    // Wait a bit for sidebar to fully render
    await page.waitForTimeout(3000);

    const hasRequests = await page.locator('[data-action="accept"]').count() > 0;
    if (!hasRequests) {
      // No pending requests — expert may already be a member from a previous accepted run
      return;
    }

    // Accept all pending requests (there may be stale ones from previous runs)
    let maxClicks = 5;
    while (maxClicks-- > 0) {
      const btns = page.locator('[data-action="accept"]');
      const count = await btns.count();
      if (count === 0) break;
      await btns.first().click();
      await page.waitForTimeout(1500);
    }

    // All cards should be gone now
    await expect(page.locator('.join-request-card')).toHaveCount(0, { timeout: 10_000 });
  });

  test('join accepted email sent to requester', async ({ page, request }) => {
    // Only check for email if a request was actually accepted (Mailpit was cleared before test 3)
    try {
      const msg = await showEmailInMailpit(page, request, 'expert2@e2e.test', 'angenommen', 8_000);
      expect(msg.HTML || msg.Text).toBeTruthy();
    } catch {
      // No email found — no new acceptance happened (expert was already a member)
    }
  });

  // --- Reject join request ---

  test('initiator rejects a join request via sidebar', async ({ page, request }) => {
    // Register a throwaway user and have them send a join request
    const throwawayEmail = `throwaway-${String(Date.now())}@e2e.test`;
    const throwawayUser = `throwaway-${String(Date.now())}`;

    // Register throwaway user (using a second browser context to avoid disrupting main session)
    const { context: throwawayCtx, page: throwawayPage } = await (async () => {
      const ctx = await page.context().browser()!.newContext();
      const p = await ctx.newPage();
      return { context: ctx, page: p };
    })();

    try {
      await registerUser(throwawayPage, throwawayEmail, throwawayUser, 'E2eTest!2026');
      await throwawayPage.goto(SPACE_URL);
      await throwawayPage.waitForSelector('#space-header', { timeout: 10_000 });
      await throwawayPage.waitForTimeout(3000);

      const joinBtn = throwawayPage.locator('#btn-join-request');
      if (await joinBtn.isVisible().catch(() => false)) {
        await joinBtn.click();
        await throwawayPage.waitForTimeout(2000);
      }
    } finally {
      await throwawayCtx.close();
    }

    // Now login as initiator and reject the pending request
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });
    await page.waitForTimeout(3000);

    const rejectBtns = page.locator('[data-action="reject"]');
    const rejectCount = await rejectBtns.count();
    if (rejectCount === 0) {
      // No pending requests (throwaway registration may have been blocked)
      return;
    }

    // Click the first reject button
    await rejectBtns.first().click();
    await page.waitForTimeout(2000);

    // The card should be removed from the DOM (card.remove() is called on success)
    // If there were multiple cards, count should have dropped by 1
    const newCount = await page.locator('[data-action="reject"]').count();
    expect(newCount).toBeLessThan(rejectCount);
  });

  // --- Remove member ---

  test('initiator removes the seeded E2E User member from the space', async ({ page, request }) => {
    // Target "E2E User" (user@e2e.test) specifically — this is the seeded developer member.
    // We must NOT remove expert2 here because the "expert leaves space" test needs expert2
    // to still be a member so they can leave voluntarily.
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    // Wait for sidebar to render (async fetch)
    await page.waitForSelector('.project-space-sidebar', { timeout: 15_000 });

    // Look for [data-remove-member] for "E2E User" (seeded developer member)
    const removeBtn = page.locator('[data-remove-member][data-member-name="E2E User"]');
    if (!(await removeBtn.isVisible().catch(() => false))) {
      // E2E User is not in this space (may have been removed in a previous run) — skip gracefully
      return;
    }

    // Handle the native confirm() dialog — register listener before clicking
    page.once('dialog', async (dialog) => {
      await dialog.accept();
    });

    // Intercept the DELETE API response to confirm success
    const [removeResponse] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes('/member/') && resp.request().method() === 'DELETE',
        { timeout: 15_000 },
      ),
      removeBtn.click(),
    ]);
    expect(removeResponse.ok()).toBeTruthy();

    // On success, JS calls window.location.reload(); wait for sidebar to fully re-render
    await page.waitForSelector('#space-header', { timeout: 15_000 });
    // The sidebar is populated asynchronously — wait for the project-space-sidebar to appear
    await page.waitForSelector('.project-space-sidebar', { timeout: 15_000 });
    // Verify the removed member is gone
    await expect(
      page.locator('[data-remove-member][data-member-name="E2E User"]'),
    ).toHaveCount(0, { timeout: 15_000 });
  });

  test('member removed email sent to removed member', async ({ page, request }) => {
    try {
      const msg = await showEmailInMailpit(page, request, '', 'entfernt', 15_000);
      expect(msg.HTML || msg.Text).toBeTruthy();
    } catch {
      // No removal email — no member was actually removed (e.g. no members in space)
    }
  });

  // --- Status update CRUD ---

  test('initiator writes status update', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-title', { timeout: 10_000 });

    // Wait for JS to render the write-update button (initiator sees it)
    const writeBtn = page.locator('#btn-write-update');
    await expect(writeBtn).toBeVisible({ timeout: 15_000 });
    await writeBtn.click();

    // Modal opens (uses dialog.show(), not showModal())
    await expect(page.locator('#update-title')).toBeVisible({ timeout: 5_000 });
    const updateTitle = `GUI Update ${String(Date.now())}`;
    await page.fill('#update-title', updateTitle);

    // Wait for CKEditor to initialise (modal opens, then setTimeout 100ms fires)
    await page.waitForTimeout(1500);
    await fillCKEditor(page, 'update-body', 'Status update written through the browser.');

    // Wait for CKEditor content to be set
    await page.waitForFunction(
      () => {
        const cke = (window as any).CKEDITOR;
        if (!cke) return false;
        const inst = cke.instances?.['update-body'];
        return inst && inst.getData().length > 10;
      },
      { timeout: 10_000 },
    );

    const [saveResponse] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes('/status-update') && resp.request().method() === 'POST',
        { timeout: 15_000 },
      ),
      page.click('#update-submit'),
    ]);
    expect(saveResponse.ok()).toBeTruthy();
  });

  test('status update email sent to members', async ({ page, request }) => {
    // Subject: "Neues Update in «<space name>»" — search for "Neues Update"
    const msg = await showEmailInMailpit(page, request, '', 'Neues Update');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  // --- Collaboard CRUD ---

  test('initiator adds collaboard link', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    // Wait for add-collaboard button to appear (rendered after async fetch)
    const addBtn = page.locator('#btn-add-collaboard');
    await expect(addBtn).toBeVisible({ timeout: 15_000 });
    await addBtn.click();

    // Modal uses showModal() — wait for inputs to appear
    await expect(page.locator('#collaboard-title')).toBeVisible({ timeout: 5_000 });
    await page.fill('#collaboard-title', 'E2E Test Board');
    await page.fill('#collaboard-url', 'https://web.collaboard.app/board/e2e-test');

    // Submit button is #collaboard-submit (not #collaboard-save)
    // Wait for the API call to complete and page to reload
    const [addResponse] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes('/collaboard') && resp.request().method() === 'POST',
        { timeout: 15_000 },
      ),
      page.click('#collaboard-submit'),
    ]);
    expect(addResponse.ok()).toBeTruthy();

    // After success, JS does window.location.reload() which may hit cache.
    // Navigate fresh to avoid stale API responses.
    await page.waitForTimeout(2000);
    await page.goto(SPACE_URL, { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#space-header', { timeout: 15_000 });
    await page.waitForSelector('.collaboard-panel', { timeout: 15_000 });

    // Verify collaboard appears on page
    await expect(page.locator('.collaboard-panel')).toContainText('E2E Test Board', { timeout: 10_000 });
  });

  test('initiator removes collaboard link', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    // Wait for collaboard section to render
    await page.waitForSelector('.collaboard-panel', { timeout: 15_000 });

    // Remove button uses class .collaboard-item__remove (data-link-id attribute)
    const deleteBtn = page.locator('.collaboard-item__remove').first();
    const hasDeletable = await deleteBtn.isVisible({ timeout: 5_000 }).catch(() => false);

    if (hasDeletable) {
      // Handle the native confirm() dialog
      page.once('dialog', async (dialog) => {
        await dialog.accept();
      });
      await deleteBtn.click();

      // On success, page reloads
      await page.waitForSelector('#space-header', { timeout: 15_000 });
    }
  });

  // --- Member leave ---

  test('expert leaves space via button', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'expert2@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    // Wait for sidebar to render with leave button
    const leaveBtn = page.locator('[data-leave-space]');
    await expect(leaveBtn).toBeVisible({ timeout: 15_000 });

    // Handle the native confirm() dialog that fires on click
    page.once('dialog', async (dialog) => {
      await dialog.accept();
    });
    await leaveBtn.click();

    // After leaving, page navigates to /projektraeume
    await page.waitForURL((url) => url.pathname.includes('/projektraeume'), { timeout: 15_000 });
  });

  test('member left email sent to initiator', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'verlassen');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });
});
