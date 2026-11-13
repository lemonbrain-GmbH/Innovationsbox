import { test, expect } from '@playwright/test';

const PHP_SERVER_URL = 'http://localhost:6007';

test.describe('Storybook smoke tests', () => {
  test('PHP story server responds via ?id= param (Storybook format)', async ({ request }) => {
    const response = await request.get(
      `${PHP_SERVER_URL}/?id=atoms/button&variant=primary&label=Test`
    );
    expect(response.status()).toBe(200);
    const html = await response.text();
    expect(html).toContain('button--primary');
    expect(html).toContain('Test');
  });

  test('PHP story server responds via ?component= param (manual format)', async ({ request }) => {
    const response = await request.get(
      `${PHP_SERVER_URL}/?component=atoms/button&variant=secondary&label=Manual`
    );
    expect(response.status()).toBe(200);
    const html = await response.text();
    expect(html).toContain('button--secondary');
  });

  test('PHP story server returns 404 HTML for missing component', async ({ request }) => {
    const response = await request.get(
      `${PHP_SERVER_URL}/?id=nonexistent/thing`
    );
    expect(response.status()).toBe(404);
    const html = await response.text();
    expect(html).toContain('Component not found');
  });

  test('PHP story server validates component path', async ({ request }) => {
    const response = await request.get(
      `${PHP_SERVER_URL}/?id=../../../etc/passwd`
    );
    expect(response.status()).toBe(400);
  });

  test('button defaults from variants.php are applied when no props given', async ({ request }) => {
    const response = await request.get(
      `${PHP_SERVER_URL}/?id=atoms/button`
    );
    expect(response.status()).toBe(200);
    const html = await response.text();
    expect(html).toContain('button--primary');
    expect(html).toContain('Ich möchte eine Projektidee einbringen');
  });
});
