import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Tabs component', () => {
  test('Line (default): tablist container has role=tablist', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/tabs`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('role="tablist"');
  });

  test('Line (default): renders three tabs with role=tab', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/tabs`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    const roleTabMatches = html.match(/role="tab"/g);
    expect(roleTabMatches).not.toBeNull();
    expect(roleTabMatches!.length).toBe(3);
  });

  test('Line (default): first tab has aria-selected=true', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/tabs`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('aria-selected="true"');
  });

  test('Line (default): non-active panels are hidden', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/tabs`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    // At least one tabpanel must carry the `hidden` attribute
    expect(html).toMatch(/role="tabpanel"[\s\S]*?hidden/);
  });

  test('Second Active: second tab has aria-selected=true', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/tabs&activeIndex=1`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    // Verify there is exactly one selected tab
    const selected = html.match(/aria-selected="true"/g);
    expect(selected).not.toBeNull();
    expect(selected!.length).toBe(1);
  });
});
