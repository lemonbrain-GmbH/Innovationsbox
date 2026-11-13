import { test, expect } from '@playwright/test';

import { loginViaUI } from '../fixtures/helpers';

test.describe('Smoke: Docker environment health', () => {
  test('homepage loads with themed content', async ({ page }) => {
    await page.goto('/');
    expect(page.url()).not.toContain('/install');
    await expect(page.locator('body')).toBeVisible();
    // Innovationsbox theme should be active - check main header
    await expect(page.locator('header').first()).toBeVisible();
  });

  test('login page renders with innovationsbox theme', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('input[name="uName"]')).toBeVisible();
    await expect(page.locator('input[name="uPassword"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  const testUsers = [
    { email: 'user@e2e.test', label: 'registered user' },
    { email: 'moderator@e2e.test', label: 'moderator' },
    { email: 'expert@e2e.test', label: 'expert' },
    { email: 'initiator@e2e.test', label: 'initiator' },
  ];

  for (const { email, label } of testUsers) {
    test(`login as ${label} (${email})`, async ({ page }) => {
      await loginViaUI(page, email, 'E2eTest!2026');
      // After login, should be on a non-login page
      expect(page.url()).not.toContain('/login');
    });
  }

  test('idea submission form loads for logged-in user', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto('/projektidee/einreichen');
    await expect(page.locator('#idea-form, form')).toBeVisible({ timeout: 10_000 });
    await expect(page.locator('#idea-title')).toBeVisible();
  });

  test('moderation queue loads for moderator', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 15_000 });
  });

  test('project spaces page loads with cards', async ({ page }) => {
    await page.goto('/projektraeume');
    await page.waitForSelector('#project-spaces-grid', { timeout: 10_000 });
    const cards = page.locator('#project-spaces-grid .project-card, #project-spaces-grid article');
    await expect(cards.first()).toBeVisible({ timeout: 5_000 });
  });

  test('moderator sees badge count on user menu', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    // Navigate to a themed page to see the user menu
    await page.goto('/projektraeume');

    // Open the nav drawer (mobile) — this moves the user menu into the drawer
    const navToggle = page.locator('.nav-toggle');
    if (await navToggle.isVisible()) {
      await navToggle.click();
      await page.waitForTimeout(500);
    }

    // The moderator has seeded pending reviews — avatar badge should be visible on the trigger
    const badge = page.locator('.user-menu__badge');
    await expect(badge).toBeVisible({ timeout: 5_000 });
    const count = await badge.textContent();
    expect(Number(count)).toBeGreaterThan(0);

    // Open the user menu dropdown to see per-item badges
    const trigger = page.locator('[data-user-menu-trigger]');
    await trigger.click();
    await page.waitForTimeout(300);

    // Moderation item should have an item badge
    const modBadge = page.locator('.user-menu__item:has-text("Moderation") .user-menu__item-badge');
    await expect(modBadge).toBeVisible({ timeout: 5_000 });
  });

  test('user menu has Meine Innovationen item', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto('/innovationen');

    // Open the nav drawer (mobile) — this moves the user menu into the drawer
    const navToggle = page.locator('.nav-toggle');
    if (await navToggle.isVisible()) {
      await navToggle.click();
      await page.waitForTimeout(500);
    }

    // Open the user menu dropdown to see menu items
    const trigger = page.locator('[data-user-menu-trigger]');
    await trigger.click();
    await page.waitForTimeout(300);

    const item = page.locator('.user-menu__item:has-text("Meine Innovationen")');
    await expect(item).toBeVisible({ timeout: 5_000 });
  });
});
