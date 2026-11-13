import { test, expect } from '@playwright/test';

import { loginViaUI } from '../fixtures/helpers';

const DETAIL_URL = '/projektraum/view/e2e_space_active';

test.describe('Projektraum Detail', () => {
  test('page loads with project title', async ({ page }) => {
    await page.goto(DETAIL_URL);
    await expect(page.locator('#space-title')).toBeVisible();
  });

  test('header shows status badge', async ({ page }) => {
    await page.goto(DETAIL_URL);
    await expect(page.locator('#space-status-badge .badge').first()).toBeVisible();
  });

  test('status update timeline is visible', async ({ page }) => {
    await page.goto(DETAIL_URL);
    await page.waitForSelector('.status-update-timeline', { timeout: 10_000 });
    const entries = page.locator('.status-update-entry');
    const count = await entries.count();
    expect(count).toBeGreaterThanOrEqual(1);
  });

  test('status update shows Swiss date format', async ({ page }) => {
    await page.goto(DETAIL_URL);
    await page.waitForSelector('.status-update-entry__meta', { timeout: 10_000 });
    const meta = await page.locator('.status-update-entry__meta').first().textContent();
    expect(meta).toMatch(/\d{2}\.\d{2}\.\d{4}/);
  });

  test('sidebar shows team members', async ({ page }) => {
    test.setTimeout(60_000);
    // Members are only visible to authenticated members of the space
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(DETAIL_URL);
    await page.waitForSelector('.project-space-sidebar', { timeout: 10_000 });
    const members = page.locator('.project-space-sidebar__member-row');
    const count = await members.count();
    expect(count).toBeGreaterThanOrEqual(1);
  });

  test('collaboard section is present', async ({ page }) => {
    // Collaboard links are only visible to members/initiators of the space
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(DETAIL_URL);
    // The collaboard panel should be visible for initiators (even if empty)
    const panel = page.locator('.collaboard-panel');
    await expect(panel).toBeVisible({ timeout: 10_000 });
  });

  test('back link is at bottom of page', async ({ page }) => {
    await page.goto(DETAIL_URL);
    const backLink = page.locator('.projektraum-page__back');
    await expect(backLink).toBeVisible();
    await expect(backLink).toContainText('Zurück');
  });
});
