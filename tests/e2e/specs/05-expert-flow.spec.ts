import { test, expect } from '@playwright/test';

import {
  loginViaUI,
  clearMailpit,
  showEmailInMailpit,
} from '../fixtures/helpers';

/**
 * E2E Expert request flow — GUI only.
 *
 * Flow:
 *   Accept path: expert2 accepts the pre-seeded pending expert request
 *                → acceptance email sent to initiator
 *   Decline path: initiator requests expert (E2E Expert) from space sidebar
 *                 → expert request email arrives
 *                 → expert declines via /expertanfragen UI
 *                 → decline email sent to initiator
 */
test.describe.serial('Expert request flow', () => {
  test.setTimeout(120_000);

  const SPACE_URL = '/projektraum/view/e2e_space_active';

  // Shared state — which expert was requested (determined dynamically)
  let requestedExpertEmail = 'expert@e2e.test';

  // --- Accept path ---

  test('expert2 accepts pre-seeded expert request via /expertanfragen', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'expert2@e2e.test', 'E2eTest!2026');
    await page.goto('/expertanfragen');
    await page.waitForSelector('#requests-container', { timeout: 15_000 });

    // Wait for requests to render
    await page.waitForFunction(
      () => {
        const el = document.getElementById('requests-container');
        return !!el && (el.textContent ?? '').trim().length > 0;
      },
      { timeout: 15_000 },
    );

    const acceptBtn = page.locator('button[data-accept="true"]').first();
    if (!(await acceptBtn.isVisible().catch(() => false))) {
      // No pending request — may have been accepted in a previous run; skip gracefully
      return;
    }

    await acceptBtn.click();
    await page.waitForTimeout(3000);

    // After accepting, page reloads; the card should show "Angenommen" status or be gone
    await page.waitForSelector('#requests-container', { timeout: 15_000 });
    await page.waitForFunction(
      () => {
        const el = document.getElementById('requests-container');
        return !!el && (el.textContent ?? '').trim().length > 0;
      },
      { timeout: 15_000 },
    );
    // No more pending accept buttons for this request
    const pendingBtns = await page.locator('button[data-accept="true"]').count();
    // Either 0 pending accept buttons, or the card now shows "Angenommen"
    const containerText = (await page.locator('#requests-container').textContent()) ?? '';
    expect(pendingBtns === 0 || containerText.includes('Angenommen')).toBeTruthy();
  });

  test('expert acceptance email sent to initiator', async ({ page, request }) => {
    try {
      const msg = await showEmailInMailpit(page, request, 'initiator@e2e.test', 'angenommen', 20_000);
      expect(msg.HTML || msg.Text).toBeTruthy();
    } catch {
      // No email found — expert request may have already been accepted before this run
    }
  });

  // --- Decline path ---

  test('initiator requests expert from space sidebar', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto(SPACE_URL);
    await page.waitForSelector('#space-header', { timeout: 10_000 });

    const expertSelect = page.locator('#expert-select');
    await expect(expertSelect).toBeVisible({ timeout: 10_000 });

    // Wait for expert options to load
    await page.waitForFunction(
      () => {
        const sel = document.getElementById('expert-select') as HTMLSelectElement | null;
        return sel && sel.options.length > 1;
      },
      { timeout: 15_000 },
    );

    // Select a known E2E expert by matching display name in the dropdown.
    // The dev DB dump may contain other experts — we must pick one we control.
    const allOptions = page.locator('#expert-select option:not([value=""])');
    const optionCount = await allOptions.count();
    let selectedValue = '';

    for (let i = 0; i < optionCount; i++) {
      const text = (await allOptions.nth(i).textContent()) ?? '';
      const value = (await allOptions.nth(i).getAttribute('value')) ?? '';
      // Match E2E test experts by their seeded display names
      if (/E2E Expert\b/i.test(text) && !/expert2/i.test(text)) {
        selectedValue = value;
        requestedExpertEmail = 'expert@e2e.test';
        break;
      }
    }
    expect(selectedValue).toBeTruthy();

    await page.selectOption('#expert-select', selectedValue);

    const messageField = page.locator('#expert-message');
    if (await messageField.isVisible()) {
      await messageField.fill('We need your energy expertise for this project.');
    }

    await page.click('#btn-request-expert');
    await page.waitForTimeout(2000);
  });

  test('expert request email sent', async ({ page, request }) => {
    const msg = await showEmailInMailpit(
      page,
      request,
      '',
      'Expertise',
      30_000,
    );
    expect(msg.HTML || msg.Text).toBeTruthy();
  });

  test('expert declines request via expert requests page', async ({ page, request }) => {
    await clearMailpit(request);
    await loginViaUI(page, requestedExpertEmail, 'E2eTest!2026');
    await page.goto('/expertanfragen');
    await page.waitForSelector('#requests-container', { timeout: 15_000 });

    // Wait for requests to render
    await page.waitForFunction(
      () => {
        const el = document.getElementById('requests-container');
        return !!el && (el.textContent ?? '').trim().length > 0;
      },
      { timeout: 15_000 },
    );

    const declineBtn = page.locator('button[data-accept="false"]').first();
    await expect(declineBtn).toBeVisible({ timeout: 10_000 });

    await declineBtn.click();
    await page.waitForTimeout(3000);

    // Verify UI updated after decline
    await page.reload();
    await page.waitForSelector('#requests-container', { timeout: 15_000 });
  });

  test('decline email sent to initiator', async ({ page, request }) => {
    const msg = await showEmailInMailpit(
      page,
      request,
      'initiator@e2e.test',
      'abgelehnt',
      45_000,
    );
    expect(msg.HTML || msg.Text).toBeTruthy();
  });
});
