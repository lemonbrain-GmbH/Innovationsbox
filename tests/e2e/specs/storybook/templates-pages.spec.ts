import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Template components', () => {
  test('default-page: renders full page layout with header, main, footer', async ({ request }) => {
    const res = await request.get(`${URL}/?id=templates/default-page`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('ccm-page');
    expect(html).toContain('page-header');
    expect(html).toContain('main-content-area');
    expect(html).toContain('<footer>');
  });

  test('view-page: renders with hero-image instead of page header', async ({ request }) => {
    const res = await request.get(`${URL}/?id=templates/view-page`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('ccm-page');
    expect(html).toContain('hero-image-wrapper');
    expect(html).toContain('<footer>');
  });
});

test.describe('Page examples', () => {
  test('homepage: renders complete homepage with hero, content, page list', async ({ request }) => {
    const res = await request.get(`${URL}/?id=pages/homepage`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('hero-image-wrapper');
    expect(html).toContain('Innovationsbox');
    expect(html).toContain('content-block');
    expect(html).toContain('page-list-pages');
    expect(html).toContain('<footer>');
  });

  test('inner-page: renders standard page with content block', async ({ request }) => {
    const res = await request.get(`${URL}/?id=pages/inner-page`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('page-header');
    expect(html).toContain('content-block');
    expect(html).toContain('<footer>');
  });
});
