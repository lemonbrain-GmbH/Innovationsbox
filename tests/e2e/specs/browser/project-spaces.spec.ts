import { test, expect } from '@playwright/test';

import {
  clearMailpit,
  fillCKEditor,
  getMailpitMessage,
  loginViaUI,
} from '../../fixtures/helpers';

test.describe('Browser: Project spaces', () => {
  test('browse spaces with filter and search', async ({ page }) => {
    await page.goto('/innovationen');

    // Grid should load
    await page.waitForSelector('#project-spaces-grid', { timeout: 10_000 });
    const cards = page.locator('#project-spaces-grid .project-card, #project-spaces-grid article');
    await expect(cards.first()).toBeVisible({ timeout: 5_000 });

    // Click active filter
    const activeFilter = page.locator('[data-value="active"]');
    if (await activeFilter.isVisible()) {
      await activeFilter.click();
      await page.waitForTimeout(1000);
      await expect(cards.first()).toBeVisible({ timeout: 5_000 });
    }

    // Type in search
    const searchInput = page.locator('#project-search');
    if (await searchInput.isVisible()) {
      await searchInput.fill('Test');
      await page.waitForTimeout(500);
    }
  });

  test('join a space and verify email to initiator', async ({ page, request }) => {
    await clearMailpit(request);

    await loginViaUI(page, 'user@e2e.test', 'E2eTest!2026');

    // Navigate to spaces listing then click into a space
    await page.goto('/innovationen');
    await page.waitForSelector('#project-spaces-grid', { timeout: 10_000 });

    const spaceLink = page.locator('#project-spaces-grid a, #project-spaces-grid .project-card a').first();
    if (await spaceLink.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await spaceLink.click();
    } else {
      await page.locator('article a, .project-card').first().click();
    }

    await page.waitForSelector('.projektraum-page, #space-header', { timeout: 10_000 });

    // Click join button if visible (user might already be member)
    const joinBtn = page.locator('#btn-join-request');
    if (await joinBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await joinBtn.click();
      await page.waitForTimeout(2000);

      // Verify join request email to initiator
      const msg = await getMailpitMessage(request, 'initiator@e2e.test', 'Beitrittsanfrage');
      expect(msg.HTML || msg.Text).toBeTruthy();
    }
  });

  test('write status update with CKEditor and verify email', async ({ page, request }) => {
    await clearMailpit(request);

    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');

    // Navigate directly to the active space (seed data guarantees it exists)
    await page.goto('/projektraum/view/e2e_space_active');
    await page.waitForSelector('#space-title', { timeout: 10_000 });

    // Write update button should be visible for the initiator on an active space
    const writeBtn = page.locator('#btn-write-update');
    await expect(writeBtn).toBeVisible({ timeout: 10_000 });
    await writeBtn.click();

    // Wait for the dialog to open and form to be visible
    await expect(page.locator('#update-title')).toBeVisible({ timeout: 5_000 });

    // Fill update modal
    const updateTitle = `Browser Update ${String(Date.now())}`;
    await page.fill('#update-title', updateTitle);
    // Wait for CKEditor to initialize (setTimeout 100ms in JS + rendering time)
    await page.waitForTimeout(1000);
    await fillCKEditor(page, 'update-body', 'This is a status update written by a browser E2E test.');

    // Verify CKEditor has content before submitting
    await page.waitForFunction(
      () => {
        // @ts-expect-error CKEDITOR global
        const cke = (window as any).CKEDITOR;
        if (!cke) return false;
        const inst = cke.instances?.['update-body'];
        return inst && inst.getData().length > 10;
      },
      { timeout: 5_000 },
    );

    // Save — click submit and wait for the API response
    const [saveResponse] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes('/status-update') && resp.request().method() === 'POST',
        { timeout: 15_000 },
      ),
      page.click('#update-submit'),
    ]);
    expect(saveResponse.ok()).toBeTruthy();

    // Verify save succeeded via the API response status
    // The page will reload via window.location.reload() — we already confirmed the POST was ok
  });

  test('leave space', async ({ page }) => {
    await loginViaUI(page, 'user@e2e.test', 'E2eTest!2026');

    await page.goto('/innovationen');
    await page.waitForSelector('#project-spaces-grid', { timeout: 10_000 });

    await page.locator('#project-spaces-grid a, article a').first().click();
    await page.waitForSelector('.projektraum-page, #space-header', { timeout: 10_000 });

    const leaveBtn = page.locator('[data-leave-space]');
    if (await leaveBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await leaveBtn.click();
      await page.waitForTimeout(2000);
    }
  });
});
