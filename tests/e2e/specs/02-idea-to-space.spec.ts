import { test, expect } from '@playwright/test';

import {
  loginViaUI,
  fillCKEditor,
  clearMailpit,
  showEmailInMailpit,
} from '../fixtures/helpers';

test.describe.serial('Idea to Project Space', () => {
  test.setTimeout(120_000);

  const ideaTitle = `GUI E2E Idea ${String(Date.now())}`;

  test('initiator submits idea via form', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');

    await page.goto('/projektidee/einreichen');
    await expect(page.locator('#idea-form, form')).toBeVisible({ timeout: 10_000 });

    // Fill title
    await page.fill('#idea-title', ideaTitle);

    // Select category — wait for options to populate
    await page.waitForFunction(
      () => {
        const sel = document.getElementById('idea-category') as HTMLSelectElement | null;
        return sel && sel.options.length > 1;
      },
      { timeout: 10_000 },
    );
    const firstOption = await page.locator('#idea-category option:not([value=""])').first().getAttribute('value');
    if (firstOption) await page.selectOption('#idea-category', firstOption);

    // Fill CKEditor description
    await fillCKEditor(page, 'idea-description', 'A project idea submitted entirely through the browser GUI.');

    // Submit
    await page.click('#idea-submit');
    await page.waitForURL(/\/projektidee\/meine/, { timeout: 30_000 });

    // Verify idea appears in my ideas list
    await expect(page.locator('#ideas-container')).toContainText(ideaTitle, { timeout: 15_000 });
  });

  test('submission email sent to moderator', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'moderator@e2e.test', ideaTitle);
    expect(msg.Subject).toContain(ideaTitle);
  });

  test('idea appears in my ideas with submitted badge', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto('/projektidee/meine');
    await page.waitForSelector('#ideas-container', { timeout: 10_000 });
    await expect(page.locator('#ideas-container')).toContainText(ideaTitle, { timeout: 5_000 });
  });

  test('moderator sees idea in moderation queue', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });
    await expect(page.locator('#reviews-container')).toContainText(ideaTitle, { timeout: 5_000 });
  });

  test('moderator approves idea via UI', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });

    // Click the review link containing our idea title
    await page.locator(`#reviews-container a:has-text("${ideaTitle}")`).first().click();
    await page.waitForSelector('#btn-approve', { timeout: 10_000 });

    // Click approve
    await page.click('#btn-approve');
    await page.waitForURL(/\/moderation/, { timeout: 10_000 });
  });

  test('approval email sent to initiator', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'freigegeben');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  test('approved idea links to active project space', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto('/projektidee/meine');
    await page.waitForSelector('#ideas-container', { timeout: 10_000 });

    // Find the approved idea card and navigate to the project space
    const ideaCard = page.locator(`#ideas-container :has-text("${ideaTitle}")`).first();
    await expect(ideaCard).toBeVisible({ timeout: 5_000 });
    const spaceLink = ideaCard.locator('a[href*="/projektraum/"]');
    await expect(spaceLink).toBeVisible({ timeout: 5_000 });
    await spaceLink.click();

    // Space should be created as active (no activation step needed)
    await expect(page.locator('#space-title')).toContainText(ideaTitle, { timeout: 15_000 });
    await expect(page.locator('#space-status-badge')).toContainText('Aktiv', { timeout: 10_000 });
  });

  test('project space appears in public spaces list', async ({ page }) => {
    await page.goto('/projektraeume');
    await page.waitForSelector('#project-spaces-grid', { timeout: 10_000 });
    await expect(page.locator('#project-spaces-grid')).toContainText(ideaTitle, { timeout: 5_000 });
  });

  test('initiator can withdraw a newly submitted idea', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');

    // Submit a fresh idea that we can withdraw (the earlier idea is already approved)
    const withdrawTitle = `Withdraw E2E ${String(Date.now())}`;
    await page.goto('/projektidee/einreichen');
    await expect(page.locator('#idea-form, form')).toBeVisible({ timeout: 10_000 });

    await page.fill('#idea-title', withdrawTitle);

    await page.waitForFunction(
      () => {
        const sel = document.getElementById('idea-category') as HTMLSelectElement | null;
        return sel && sel.options.length > 1;
      },
      { timeout: 10_000 },
    );
    const firstOption = await page.locator('#idea-category option:not([value=""])').first().getAttribute('value');
    if (firstOption) await page.selectOption('#idea-category', firstOption);

    await fillCKEditor(page, 'idea-description', 'This idea will be withdrawn immediately after submission.');

    await page.click('#idea-submit');
    await page.waitForURL(/\/projektidee\/meine/, { timeout: 30_000 });
    await expect(page.locator('#ideas-container')).toContainText(withdrawTitle, { timeout: 15_000 });

    // Now withdraw it — click the withdraw button on the matching card
    const card = page.locator(`.idea-card:has-text("${withdrawTitle}")`).first();
    await expect(card).toBeVisible({ timeout: 10_000 });

    const withdrawBtn = card.locator('[data-withdraw-idea]');
    await expect(withdrawBtn).toBeVisible({ timeout: 10_000 });
    await withdrawBtn.click();

    // Confirm in the modal
    const modal = page.locator('#withdraw-modal');
    await expect(modal).toBeVisible({ timeout: 5_000 });
    await page.click('#withdraw-confirm');

    // After successful withdrawal the page reloads; the idea card must be gone
    await page.waitForFunction(
      (title) => !document.body.innerText.includes(title),
      withdrawTitle,
      { timeout: 15_000 },
    );
    await expect(page.locator('#ideas-container')).not.toContainText(withdrawTitle, { timeout: 5_000 });
  });
});
