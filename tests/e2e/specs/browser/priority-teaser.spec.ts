import { test, expect } from '@playwright/test';

test.describe('Browser: Priority teaser on homepage', () => {
  test('flagged older space appears first in homepage teaser', async ({ page, request }) => {
    const isDocker = process.env.E2E_DOCKER === 'true';
    const baseUrl = isDocker ? 'http://localhost:8880' : '';

    // Step 1: Login via API to get a session cookie + CSRF token
    const adminEmail = isDocker ? 'admin@e2e.test' : 'admin@lemonbrain.ch';
    const adminPassword = isDocker ? 'E2eTest!2026' : 'password';

    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="uName"]', adminEmail);
    await page.fill('input[name="uPassword"]', adminPassword);
    await page.click('button[type="submit"]');

    // Wait for redirect (login may redirect to / or /account)
    await page.waitForTimeout(3000);

    // Navigate to a themed page to get the CSRF token
    await page.goto('/innovationen');
    await page.waitForSelector('meta[name="csrf-token"]', { state: 'attached', timeout: 10_000 });
    const csrfToken = (await page.getAttribute('meta[name="csrf-token"]', 'content')) ?? '';

    // Step 2: Set priority on the older active space via API
    const spaceId = 'e2e_space_active';
    const setRes = await page.evaluate(
      async ({ spaceId, csrfToken }) => {
        const res = await fetch(`/api/project-space/${spaceId}/priority`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
          },
          body: JSON.stringify({ priority: 1 }),
        });
        return { ok: res.ok, status: res.status, body: await res.text() };
      },
      { spaceId, csrfToken },
    );
    expect(setRes.ok, `API response: ${setRes.status} ${setRes.body}`).toBe(true);

    // Step 3: Go to homepage and verify the teaser shows the prioritized space first
    await page.goto('/');
    await page.waitForSelector('[data-innovation-teaser-grid]', { timeout: 10_000 });

    // Wait for JS to populate the cards
    const firstCard = page.locator('[data-innovation-teaser-grid] .project-card').first();
    await expect(firstCard).toBeVisible({ timeout: 10_000 });

    // The prioritized space should be the first card
    const firstTitle = await firstCard
      .locator('.project-card__title, h3, h4')
      .first()
      .textContent();
    expect(firstTitle?.trim()).toContain('Fernwärmenetz Glarus Süd');

    // Step 4: Clean up — remove priority
    await page.evaluate(
      async ({ spaceId, csrfToken }) => {
        await fetch(`/api/project-space/${spaceId}/priority`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
          },
          body: JSON.stringify({ priority: 0 }),
        });
      },
      { spaceId, csrfToken },
    );
  });
});
