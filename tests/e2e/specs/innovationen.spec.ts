import { test, expect } from '@playwright/test';

const BASE = '/innovationen';

test.describe('Innovationen Overview', () => {
  test('page loads with title and search', async ({ page }) => {
    await page.goto(BASE);
    await expect(page.locator('h1')).toContainText('Innovationen');
    await expect(page.locator('#project-search')).toBeVisible();
  });

  test('status filter buttons are present', async ({ page }) => {
    await page.goto(BASE);
    const filters = page.locator('#project-filters');
    await expect(filters.locator('[data-value=""]')).toContainText('Alle');
    await expect(filters.locator('[data-value="active"]')).toContainText('Aktiv');
    await expect(filters.locator('[data-value="completed"]')).toContainText('Abgeschlossen');
  });

  test('category filter pills are rendered from API', async ({ page }) => {
    await page.goto(BASE);
    const catFilters = page.locator('#category-filters');
    await expect(catFilters.locator('[data-category=""]')).toContainText('Alle');
    await expect(catFilters.locator('[data-category="energie"]')).toBeVisible();
    await expect(catFilters.locator('[data-category="mobilitaet"]')).toBeVisible();
  });

  test('project cards are displayed', async ({ page }) => {
    await page.goto(BASE);
    await page.waitForSelector('.project-card', { timeout: 10_000 });
    const cards = page.locator('.project-card');
    expect(await cards.count()).toBeGreaterThanOrEqual(2);
  });

  test('category filter narrows results', async ({ page }) => {
    await page.goto(BASE);
    await page.waitForSelector('.project-card', { timeout: 10_000 });
    const initialCount = await page.locator('.project-card').count();
    await page.locator('#category-filters [data-category="energie"]').click();
    // Wait for filter to re-render — either cards disappear or count changes
    await expect(page.locator('.project-card').first()).toBeVisible({ timeout: 10_000 });
    const filteredCount = await page.locator('.project-card').count();
    expect(filteredCount).toBeGreaterThanOrEqual(1);
    expect(filteredCount).toBeLessThanOrEqual(initialCount);
  });

  test('search filters by title', async ({ page }) => {
    await page.goto(BASE);
    await page.waitForSelector('.project-card', { timeout: 10_000 });
    const initialCount = await page.locator('.project-card').count();
    await page.fill('#project-search', 'Glarus');
    // Wait for search debounce + API response + re-render
    await page.waitForTimeout(1500);
    const filteredCount = await page.locator('.project-card').count();
    expect(filteredCount).toBeGreaterThanOrEqual(1);
    expect(filteredCount).toBeLessThanOrEqual(initialCount);
  });

  test('cards show category badge', async ({ page }) => {
    await page.goto(BASE);
    await page.waitForSelector('.project-card', { timeout: 10_000 });
    const badge = page.locator('.project-card__badge--category').first();
    await expect(badge).toBeVisible();
  });
});
