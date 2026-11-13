import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Checkbox component', () => {
  test('Unchecked (default): renders input type=checkbox', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/checkbox`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('type="checkbox"');
  });

  test('Unchecked (default): label wraps input (label element present)', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/checkbox`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<label');
    expect(html).toContain('checkbox__label');
  });

  test('Checked: input has checked attribute', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/checkbox&checked=true`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('checked');
  });

  test('Disabled: input has disabled attribute and modifier class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/checkbox&disabled=true`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('disabled');
    expect(html).toContain('checkbox--disabled');
  });

  test('Invalid: input has aria-invalid=true', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/checkbox&invalid=true`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('aria-invalid="true"');
  });
});
