import { test, expect } from '@playwright/test';

import {
  loginViaUI,
  fillCKEditor,
  clearMailpit,
  showEmailInMailpit,
} from '../fixtures/helpers';

test.describe.serial('Moderation feedback flow', () => {
  test.setTimeout(120_000);

  const ideaTitle = `Feedback Flow ${String(Date.now())}`;
  const feedbackNotes = 'Please add a cost estimation and timeline.';

  test('initiator submits idea', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');

    await page.goto('/projektidee/einreichen');
    await expect(page.locator('#idea-form, form')).toBeVisible({ timeout: 10_000 });

    await page.fill('#idea-title', ideaTitle);
    await page.waitForFunction(
      () => {
        const sel = document.getElementById('idea-category') as HTMLSelectElement | null;
        return sel && sel.options.length > 1;
      },
      { timeout: 10_000 },
    );
    const firstOption = await page.locator('#idea-category option:not([value=""])').first().getAttribute('value');
    if (firstOption) await page.selectOption('#idea-category', firstOption);

    await fillCKEditor(page, 'idea-description', 'An idea that will receive moderation feedback.');
    await page.click('#idea-submit');
    await page.waitForURL(/\/projektidee\/meine/, { timeout: 30_000 });
    await expect(page.locator('#ideas-container')).toContainText(ideaTitle, { timeout: 15_000 });
  });

  test('moderator requests feedback via UI', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });

    // Wait for the reviews to render (JS fetches asynchronously)
    await expect(page.locator(`#reviews-container a:has-text("${ideaTitle}")`)).toBeVisible({ timeout: 10_000 });
    await page.locator(`#reviews-container a:has-text("${ideaTitle}")`).first().click();

    // The detail page loads and JS fetches the review, rendering btn-feedback dynamically
    await page.waitForSelector('#btn-feedback', { timeout: 15_000 });

    await page.fill('#feedback-notes', feedbackNotes);
    await page.click('#btn-feedback');
    await page.waitForURL(/\/moderation$/, { timeout: 15_000 });
  });

  test('feedback email sent to initiator', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'Rückmeldung');
    expect(msg.HTML || msg.Text).toContain(feedbackNotes);
  });

  test('initiator sees feedback on their idea and edits it', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto('/projektidee/meine');
    await page.waitForSelector('#ideas-container', { timeout: 10_000 });

    // The idea should show feedback_requested status with the feedback notes
    await expect(page.locator('#ideas-container')).toContainText(ideaTitle, { timeout: 10_000 });

    // Click edit button on the idea — the button has data-edit-idea attribute
    // We need to find the edit button within the card that contains our idea title
    const ideaCard = page.locator(`.idea-card:has-text("${ideaTitle}")`).first();
    await expect(ideaCard).toBeVisible({ timeout: 5_000 });
    const editBtn = ideaCard.locator('[data-edit-idea]');
    await expect(editBtn).toBeVisible({ timeout: 5_000 });
    await editBtn.click();

    // Edit modal should appear — the dialog uses dialog.show() (not showModal())
    // Wait for the modal to become visible (has display != none / open attribute)
    const modal = page.locator('#edit-idea-modal');
    await expect(modal).toBeVisible({ timeout: 5_000 });

    // Wait for the title input to be populated with the idea title
    await expect(page.locator('#edit-idea-title')).toHaveValue(ideaTitle, { timeout: 5_000 });

    // Update the title to indicate it was edited
    await page.fill('#edit-idea-title', `${ideaTitle} (updated)`);

    // CKEditor initializes asynchronously (setTimeout 100ms) and setData is called on it.
    // Wait for CKEditor to initialize and have content before submitting.
    await page.waitForFunction(
      () => {
        const w = window as unknown as Record<string, unknown>;
        const CKEDITOR = w['CKEDITOR'] as { instances?: Record<string, { getData(): string }> } | undefined;
        const instance = CKEDITOR?.instances?.['edit-idea-description'];
        return instance && instance.getData().length > 0;
      },
      { timeout: 10_000 },
    );

    // Submit the edit — JS calls apiFetch POST /api/project-idea/{id} then window.location.reload()
    // Use page.route to intercept the API call and capture the response status before navigation
    // discards it. Without the intercept, waitForResponse cannot read the body after navigation.
    let capturedResponseStatus: number | null = null;
    await page.route(/\/api\/project-idea\/[^/]+$/, async (route) => {
      if (route.request().method() === 'POST') {
        const response = await route.fetch();
        capturedResponseStatus = response.status();
        await route.fulfill({ response });
      } else {
        await route.continue();
      }
    });

    // Click the submit button and wait for the page to reload
    await page.click('#edit-idea-submit');

    // Wait for the navigation triggered by window.location.reload()
    await page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20_000 });

    // Verify the route interceptor captured the API call with a success status
    expect(capturedResponseStatus).toBe(200);

    // Wait for the ideas container to be populated with updated data from the mine API
    await expect(page.locator('#ideas-container')).toContainText(`${ideaTitle} (updated)`, { timeout: 15_000 });
  });

  test('moderator approves resubmitted idea', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    await page.goto('/moderation');
    await page.waitForSelector('#reviews-container', { timeout: 10_000 });

    // The resubmitted idea should appear in the queue with the updated title
    await expect(page.locator(`#reviews-container a:has-text("${ideaTitle} (updated)")`)).toBeVisible({ timeout: 10_000 });
    await page.locator(`#reviews-container a:has-text("${ideaTitle} (updated)")`).first().click();

    // Wait for the detail page to load and render the approve button
    await page.waitForSelector('#btn-approve', { timeout: 15_000 });

    await page.click('#btn-approve');
    await page.waitForURL(/\/moderation$/, { timeout: 15_000 });
  });

  test('approval email sent after resubmission', async ({ page, request }) => {
    const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'freigegeben');
    expect(msg.HTML || msg.Text).toBeTruthy();
  });
});
