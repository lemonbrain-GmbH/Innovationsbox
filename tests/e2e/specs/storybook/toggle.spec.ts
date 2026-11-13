import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Toggle component', () => {
  test('Off (default): renders input type=checkbox with role=switch', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/toggle`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('type="checkbox"');
    expect(html).toContain('role="switch"');
  });

  test('Off (default): label wraps the input (label element present)', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/toggle`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<label');
    expect(html).toContain('toggle__label');
  });

  test('On: input has checked attribute', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/toggle&checked=true`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('checked');
  });

  test('Disabled: input has disabled attribute', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/toggle&disabled=true`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('disabled');
    expect(html).toContain('toggle--disabled');
  });
});
