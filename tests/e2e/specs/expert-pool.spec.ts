import { test, expect } from '@playwright/test';

import { loginViaUI } from '../fixtures/helpers';

test.describe('Expert pool visibility', () => {
  test('anonymous user sees expert pool without emails or profile links', async ({ page }) => {
    await page.goto('/members/directory');

    // Title should be Expertinnen-Pool
    await expect(page.locator('h1')).toContainText('Expertinnen-Pool');

    // If there are member cards, they should NOT have email or profile links
    const cards = page.locator('.member-card');
    const cardCount = await cards.count();

    if (cardCount > 0) {
      // Cards should be <div> not <a> (no links for anonymous)
      const firstCard = cards.first();
      const tagName = await firstCard.evaluate(el => el.tagName.toLowerCase());
      expect(tagName).toBe('div');

      // No email should be visible
      await expect(firstCard.locator('.member-card__email')).toHaveCount(0);
    }
  });

  test('logged-in user sees expert pool with profile links', async ({ page }) => {
    await loginViaUI(page, 'user@e2e.test', 'E2eTest!2026');

    // Visit expert pool
    await page.goto('/members/directory');
    await expect(page.locator('h1')).toContainText('Expertinnen-Pool');

    // If there are member cards, they should be links
    const cards = page.locator('.member-card');
    const cardCount = await cards.count();

    if (cardCount > 0) {
      const firstCard = cards.first();
      const tagName = await firstCard.evaluate(el => el.tagName.toLowerCase());
      expect(tagName).toBe('a');
    }
  });
});
