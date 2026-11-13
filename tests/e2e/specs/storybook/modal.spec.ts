import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Modal component', () => {
  test('Default: dialog element is present in DOM', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/modal`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<dialog');
  });

  test('Default: dialog has aria-labelledby pointing to title element id', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/modal`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    // aria-labelledby must reference a title id present in the same markup
    const labelMatch = html.match(/aria-labelledby="([^"]+)"/);
    expect(labelMatch).not.toBeNull();
    const titleId = labelMatch![1];
    expect(html).toContain(`id="${titleId}"`);
  });

  test('Default: modal title is rendered', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/modal`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('modal__title');
    expect(html).toContain('Projektidee einreichen');
  });

  test('Default: close button has aria-label', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/modal`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('modal__close');
    expect(html).toContain('aria-label="Schliessen"');
  });

  test('Without Footer: modal__footer is absent', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/modal&footer=`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).not.toContain('modal__footer');
  });
});
