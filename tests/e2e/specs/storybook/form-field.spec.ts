import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Form Field component', () => {
  test('Default: label element is present', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/form-field`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<label');
  });

  test('Default: input element is present', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/form-field`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<input');
  });

  test('Default: hint caption is rendered', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/form-field`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('caption--muted');
    expect(html).toContain('Wir versenden nie Spam');
  });

  test('With Error: input has aria-invalid=true', async ({ request }) => {
    const res = await request.get(
      `${URL}/?id=molecules/form-field&error=${encodeURIComponent('Bitte eine gültige E-Mail eingeben.')}&value=not-an-email`,
    );
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('aria-invalid="true"');
  });

  test('With Error: error caption is visible', async ({ request }) => {
    const error = 'Bitte eine gültige E-Mail eingeben.';
    const res = await request.get(
      `${URL}/?id=molecules/form-field&error=${encodeURIComponent(error)}&value=not-an-email`,
    );
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('caption--error');
    expect(html).toContain(error);
  });
});
