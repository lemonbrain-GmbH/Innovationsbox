import { test, expect } from '@playwright/test';

import { loginViaUI } from '../fixtures/helpers';

const TEST_USER = {
  email: 'user@e2e.test',
  password: 'E2eTest!2026',
};

test.describe('Profile page access control', () => {
  test('anonymous user sees login form when accessing a profile', async ({
    page,
  }) => {
    // Try to access a profile directly without being logged in
    await page.goto('/members/profile/view/3');

    // CMS replaces page content with login form (no HTTP redirect)
    await expect(page.locator('input[name="uName"]')).toBeVisible({ timeout: 5_000 });
  });

  test('anonymous user sees login form when accessing profile without ID', async ({
    page,
  }) => {
    await page.goto('/members/profile');

    // CMS replaces page content with login form (no HTTP redirect)
    await expect(page.locator('input[name="uName"]')).toBeVisible({ timeout: 5_000 });
  });

  test('logged-in user can view a profile', async ({ page }) => {
    await loginViaUI(page, TEST_USER.email, TEST_USER.password);

    // Now access a profile
    await page.goto('/members/profile/view/3');

    // Should stay on profile page and see the profile content
    await expect(page).not.toHaveURL(/\/login/);
    await expect(page.locator('.member-profile')).toBeVisible();
    await expect(page.locator('.member-profile__name')).toBeVisible();
  });

  test('logged-in user can see email on profile', async ({ page }) => {
    await loginViaUI(page, TEST_USER.email, TEST_USER.password);

    // Visit a profile
    await page.goto('/members/profile/view/3');

    // Email link should be visible
    await expect(page.locator('.member-profile__email')).toBeVisible();
  });
});
