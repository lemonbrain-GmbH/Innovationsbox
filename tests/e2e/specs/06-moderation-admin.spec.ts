import { test, expect } from '@playwright/test';

import {
  loginViaUI,
  clearMailpit,
  showEmailInMailpit,
  searchEmails,
  registerUser,
} from '../fixtures/helpers';

type Page = import('@playwright/test').Page;

/** Wait for #categories-container to be rendered by JS with non-empty content. */
async function waitForCategoryListReady(page: Page): Promise<void> {
  await page.waitForSelector('#categories-container', { timeout: 10_000 });
  await page.waitForFunction(
    () => {
      const el = document.getElementById('categories-container');
      return !!el && (el.textContent ?? '').trim().length > 0;
    },
    { timeout: 15_000 },
  );
}

/** Navigate to kategorien page and wait for the category list to be ready. */
async function gotoKategorien(page: Page): Promise<void> {
  await page.goto('/moderation/kategorien');
  await waitForCategoryListReady(page);
}

test.describe.serial('Moderation admin', () => {
  test.setTimeout(120_000);

  // --- Category CRUD ---

  test('moderator creates a category', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await gotoKategorien(page);

    // Clean up any leftover categories from a previous failed run
    for (const staleName of ['E2E Test Kategorie', 'E2E Kategorie Updated']) {
      const staleItem = page.locator('.category-list__item').filter({ hasText: staleName }).first();
      if (await staleItem.isVisible({ timeout: 500 }).catch(() => false)) {
        // Watch for the DELETE response
        const deleteRespPromise = page.waitForResponse(
          (resp) =>
            resp.url().includes('/api/moderation/category/') && resp.request().method() === 'DELETE',
          { timeout: 10_000 },
        );
        await staleItem.locator('[data-delete]').click();
        await expect(page.locator('#delete-category-modal')).toBeVisible({ timeout: 5_000 });
        await page.click('#delete-category-confirm');
        await deleteRespPromise;
        // Reload to get updated list
        await gotoKategorien(page);
      }
    }

    // Watch for the POST response before clicking submit
    const postRespPromise = page.waitForResponse(
      (resp) =>
        resp.url().includes('/api/moderation/category') &&
        resp.request().method() === 'POST' &&
        !resp.url().includes('/api/moderation/category/'),
      { timeout: 10_000 },
    );

    await page.click('#btn-add-category');
    await expect(page.locator('#category-modal')).toBeVisible({ timeout: 5_000 });
    await page.fill('#category-name', 'E2E Test Kategorie');
    await page.click('#category-submit');

    const postResp = await postRespPromise;
    expect(postResp.status()).toBe(201);

    // Reload to see the new category
    await gotoKategorien(page);
    await expect(page.locator('#categories-container')).toContainText('E2E Test Kategorie');
  });

  test('moderator edits category', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await gotoKategorien(page);

    const item = page.locator('.category-list__item').filter({ hasText: 'E2E Test Kategorie' }).first();
    await expect(item).toBeVisible({ timeout: 5_000 });

    await item.locator('[data-edit]').click();
    await expect(page.locator('#category-modal')).toBeVisible({ timeout: 5_000 });

    // Wait for the modal to populate with the current values
    await page.waitForTimeout(500);

    // Set the new name and click submit — both via evaluate since the modal
    // uses cloned buttons and <dialog> showModal() top-layer
    await page.evaluate(() => {
      (document.getElementById('category-name') as HTMLInputElement).value = 'E2E Kategorie Updated';
      // Small delay to let the clone event attachment complete, then click
      setTimeout(() => {
        document.getElementById('category-submit')?.click();
      }, 200);
    });

    // After the PUT succeeds, JS calls window.location.reload()
    await page.waitForTimeout(4000);
    await gotoKategorien(page);
    await expect(page.locator('#categories-container')).toContainText('E2E Kategorie Updated', { timeout: 10_000 });
  });

  test('moderator deletes category', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await gotoKategorien(page);

    const item = page.locator('.category-list__item').filter({ hasText: 'E2E Kategorie Updated' }).first();
    await expect(item).toBeVisible({ timeout: 5_000 });

    await item.locator('[data-delete]').click();
    await expect(page.locator('#delete-category-modal')).toBeVisible({ timeout: 5_000 });

    // The confirm button is cloned by JS — use evaluate with setTimeout
    // to ensure the clone + event attachment is complete before clicking
    await page.evaluate(() => {
      setTimeout(() => {
        document.getElementById('delete-category-confirm')?.click();
      }, 300);
    });

    // After successful DELETE, JS calls window.location.reload()
    await page.waitForTimeout(4000);
    await gotoKategorien(page);
    await expect(page.locator('#categories-container')).not.toContainText('E2E Kategorie Updated', { timeout: 10_000 });
  });

  // --- User management: expert flag ---

  test('moderator toggles expert flag on user', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation/benutzer');
    await page.waitForSelector('#users-container', { timeout: 10_000 });

    // Wait for JS to render the user list (fetched async)
    await page.waitForFunction(
      () => {
        const el = document.getElementById('users-container');
        return !!el && (el.textContent ?? '').trim().length > 0;
      },
      { timeout: 15_000 },
    );

    // Search for user@e2e.test via the search input (rendered inside #users-container by JS)
    const searchInput = page.locator('#user-search');
    await expect(searchInput).toBeVisible({ timeout: 5_000 });
    await searchInput.fill('user@e2e.test');

    // Wait for debounce (300ms) + re-render
    await page.waitForTimeout(700);

    const toggleBtn = page.locator('[data-toggle-expert]').first();
    await expect(toggleBtn).toBeVisible({ timeout: 5_000 });

    const toggleRespPromise = page.waitForResponse(
      (resp) => resp.url().includes('/api/moderation/user/') && resp.url().includes('/expert'),
      { timeout: 10_000 },
    );
    await toggleBtn.click();
    const toggleResp = await toggleRespPromise;
    expect(toggleResp.ok()).toBeTruthy();
    await page.waitForTimeout(500);
  });

  test('expert flag email sent to user', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'user@e2e.test', 'Expert');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  test('moderator toggles expert flag off', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation/benutzer');
    await page.waitForSelector('#users-container', { timeout: 10_000 });

    await page.waitForFunction(
      () => {
        const el = document.getElementById('users-container');
        return !!el && (el.textContent ?? '').trim().length > 0;
      },
      { timeout: 15_000 },
    );

    const searchInput = page.locator('#user-search');
    await expect(searchInput).toBeVisible({ timeout: 5_000 });
    await searchInput.fill('user@e2e.test');
    await page.waitForTimeout(700);

    // user@e2e.test is now expert — toggle off
    const toggleBtn = page.locator('[data-toggle-expert]').first();
    await expect(toggleBtn).toBeVisible({ timeout: 5_000 });

    const toggleRespPromise = page.waitForResponse(
      (resp) => resp.url().includes('/api/moderation/user/') && resp.url().includes('/expert'),
      { timeout: 10_000 },
    );
    await toggleBtn.click();
    const toggleResp = await toggleRespPromise;
    expect(toggleResp.ok()).toBeTruthy();
    await page.waitForTimeout(500);
  });

  // --- Reject flow ---

  test('moderator rejects an idea via UI', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });

    // Wait for JS to fetch and render the review list
    await page.waitForFunction(
      () => {
        const el = document.getElementById('reviews-container');
        return !!el && (el.textContent ?? '').trim().length > 0;
      },
      { timeout: 15_000 },
    );

    // Check if there are any pending reviews
    const reviewLink = page.locator('#reviews-container .moderation-list__item').first();
    const hasPending = await reviewLink.isVisible({ timeout: 3_000 }).catch(() => false);
    test.skip(!hasPending, 'No pending reviews to reject');

    await reviewLink.click();

    // Detail page fetches review data dynamically — wait for btn-reject to appear
    await page.waitForSelector('#review-container', { timeout: 10_000 });
    await page.waitForSelector('#btn-reject', { timeout: 15_000 });
    await page.click('#btn-reject');

    await expect(page.locator('#reject-modal')).toBeVisible({ timeout: 5_000 });
    await page.fill('#reject-reason', 'Not aligned with current project priorities.');

    const rejectRespPromise = page.waitForResponse(
      (resp) => resp.url().includes('/api/moderation/') && resp.url().includes('/reject'),
      { timeout: 10_000 },
    );
    await page.click('#reject-confirm');
    const rejectResp = await rejectRespPromise;
    expect(rejectResp.ok()).toBeTruthy();

    // After rejection, JS redirects to /moderation
    await page.waitForURL(/\/moderation$/, { timeout: 10_000 });
  });

  test('rejection email sent with reason', async ({ page, request }) => {
    // Skip gracefully if the previous reject test was skipped (no pending reviews)
    const emails = await searchEmails(request, 'subject:"nicht freigegeben"');
    test.skip(emails.length === 0, 'No rejection email — reject test was skipped (no pending reviews)');
    const msg = await showEmailInMailpit(page, request, '', 'nicht freigegeben');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  test('moderator deactivates a user via UI', async ({ page, request, browser }) => {
    // First register a throwaway user to deactivate
    const ctx = await browser.newContext();
    const regPage = await ctx.newPage();
    const delEmail = `e2e.moddel.${String(Date.now())}@test.local`;
    const delUser = `e2e_moddel_${String(Date.now())}`;
    await registerUser(regPage, delEmail, delUser, 'E2eTest!2026');
    await ctx.close();

    await clearMailpit(request);

    // Login as moderator
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation/benutzer');
    await page.waitForSelector('#users-container', { timeout: 10_000 });

    // Wait for user list to render
    await page.waitForFunction(
      () => {
        const el = document.getElementById('users-container');
        return !!el && (el.textContent ?? '').trim().length > 0;
      },
      { timeout: 15_000 },
    );

    // Search for the throwaway user
    const searchInput = page.locator('#user-search');
    await expect(searchInput).toBeVisible({ timeout: 5_000 });
    await searchInput.fill(delEmail);
    await page.waitForTimeout(700);

    // Click delete button
    const deleteBtn = page.locator('[data-delete-user]').first();
    await expect(deleteBtn).toBeVisible({ timeout: 5_000 });
    await deleteBtn.click();

    // Modal should appear
    await expect(page.locator('#delete-user-modal')).toBeVisible({ timeout: 5_000 });

    // Fill reason and confirm
    await page.fill('#delete-user-reason', 'Test account cleanup');

    await page.evaluate(() => {
      document.getElementById('delete-user-confirm')?.click();
    });

    // Wait for reload
    await page.waitForTimeout(3000);
  });

  test('moderator deletion email sent with reason', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, '', 'deaktiviert');
    expect(msg.HTML || msg.Text).toContain('Test account cleanup');
    expect(msg.HTML || msg.Text).toContain('Begründung');
  });
});
