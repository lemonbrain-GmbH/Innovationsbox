import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Search Bar component', () => {
  test('Empty: search input is present', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/search-bar`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('type="search"');
  });

  test('Empty: clear button is NOT in the DOM when no value given', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/search-bar`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).not.toContain('search-bar__clear');
  });

  test('With Value: clear button is present', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/search-bar&value=Solarenergie`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('search-bar__clear');
  });

  test('With Value: clear button has aria-label', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/search-bar&value=Solarenergie`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('aria-label="Suche löschen"');
  });

  test('Not Clearable: clear button absent even with value', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/search-bar&value=Windkraft&clearable=false`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).not.toContain('search-bar__clear');
  });
});
