import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Pagination component', () => {
  test('First Page: previous navigation is a disabled span', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/pagination&currentPage=1`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('pagination__link--prev pagination__link--disabled');
    // Must be a span, not an anchor
    expect(html).not.toMatch(/<a[^>]+pagination__link--prev[^>]*>/);
  });

  test('First Page: next navigation is an anchor link', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/pagination&currentPage=1`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toMatch(/<a[^>]+pagination__link--next/);
  });

  test('Middle: both prev and next are anchor links', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/pagination`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toMatch(/<a[^>]+pagination__link--prev/);
    expect(html).toMatch(/<a[^>]+pagination__link--next/);
  });

  test('Many Pages: ellipsis span is present', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/pagination&currentPage=50&totalPages=100`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('pagination__ellipsis');
  });

  test('nav element has aria-label for accessibility', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/pagination`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<nav');
    expect(html).toContain('aria-label=');
  });
});
