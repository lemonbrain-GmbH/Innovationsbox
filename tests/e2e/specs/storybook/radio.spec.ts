import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Radio component', () => {
  test('Unchecked (default): renders input type=radio', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/radio`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('type="radio"');
  });

  test('Unchecked (default): name attribute is present on input', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/radio`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('name="');
  });

  test('Unchecked (default): label element wraps the input', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/radio`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<label');
    expect(html).toContain('radio__label');
  });

  test('Checked: input has checked attribute', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/radio&checked=true`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('checked');
  });

  test('Disabled: input has disabled attribute and modifier class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/radio&disabled=true`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('disabled');
    expect(html).toContain('radio--disabled');
  });
});
