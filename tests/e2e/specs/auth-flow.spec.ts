import { test, expect } from '@playwright/test';

import { loginViaUI } from '../fixtures/helpers';

const TEST_USER = {
  email: 'user@e2e.test',
  password: 'E2eTest!2026',
};

test.describe('Authentication flow', () => {
  test('login, visit profile edit, and logout', async ({ page }) => {
    // 1. Visit login page
    await page.goto('/login');
    await expect(page.locator('h1, .auth-card__title')).toBeVisible();

    // 2. Login via helper
    await loginViaUI(page, TEST_USER.email, TEST_USER.password);

    // 3. Navigate to edit profile
    await page.goto('/account/edit_profile');
    await expect(page.locator('h1')).toBeVisible({ timeout: 10000 });

    // 4. Check that profile form is accessible (display_name may be rendered as CMS attribute input)
    const profileForm = page.locator('form, [data-view="account"]');
    await expect(profileForm.first()).toBeVisible({ timeout: 5000 });

    // 5. Logout — CMS requires a ccm_token for logout, extract it from the nav
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    const logoutLink = page.locator('a[href*="do_logout"]');
    if (await logoutLink.count() > 0) {
      const href = await logoutLink.first().getAttribute('href');
      if (href) await page.goto(href);
    } else {
      await page.goto('/login/do_logout');
    }
    await page.waitForLoadState('load', { timeout: 10_000 });

    // Verify logged out — login form should be visible
    await page.goto('/login');
    await expect(page.locator('input[name="uName"]')).toBeVisible({ timeout: 5_000 });
  });

  test('avatar page renders cropper for logged-in user', async ({ page }) => {
    await loginViaUI(page, TEST_USER.email, TEST_USER.password);

    // Visit avatar page
    await page.goto('/account/avatar');
    await expect(page.locator('[data-view="account"]')).toBeVisible();

    // The avatar-cropper Vue component should be rendered
    await expect(page.locator('avatar-cropper, .ccm-account-avatar-cropper')).toBeVisible({ timeout: 10000 });
  });
});
