import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Dropdown component', () => {
  test('Default: trigger button is present with aria-haspopup=menu', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/dropdown`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('aria-haspopup="menu"');
  });

  test('Default: trigger button has aria-expanded=false initially', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/dropdown`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('aria-expanded="false"');
  });

  test('Default: menu has role=menu and is hidden', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/dropdown`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('role="menu"');
    // The <ul> with role="menu" must carry the HTML `hidden` attribute
    expect(html).toContain('role="menu" hidden');
  });

  test('Default: trigger button renders with dropdown__trigger class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/dropdown`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('dropdown__trigger');
  });

  test('Secondary Trigger: button uses button--secondary class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/dropdown&variant=secondary`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('button--secondary');
  });
});
