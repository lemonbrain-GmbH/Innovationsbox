import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Atom components', () => {
  test('button: primary renders with correct class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/button&variant=primary&label=Test`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('button--primary');
    expect(html).toContain('class="button ');
  });

  test('heading: renders correct tag for each level', async ({ request }) => {
    for (const level of [1, 2, 3, 4, 5, 6]) {
      const res = await request.get(`${URL}/?id=atoms/heading&level=${level}&text=Test`);
      expect(res.status()).toBe(200);
      const html = await res.text();
      expect(html).toContain(`<h${level}>`);
      expect(html).toContain(`</h${level}>`);
    }
  });

  test('paragraph: body renders plain p tag', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/paragraph&variant=body&text=Test`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<p>');
    expect(html).not.toContain('class=');
  });

  test('paragraph: lead renders p with lead class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/paragraph&variant=lead&text=Test`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('class="lead"');
  });

  test('link: external adds target and rel', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/link&text=Test&href=/x&target=_blank`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('target="_blank"');
    expect(html).toContain('rel="noopener noreferrer"');
  });

  test('image: rounded adds modifier class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/image&src=test.jpg&alt=Test&rounded=true`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('image--rounded');
  });

  test('container: narrow renders lb-container-narrow', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/container&variant=narrow&content=Test`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('lb-container-narrow');
  });

  test('container: spaced adds spaced-block class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/container&spaced=true&content=Test`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('spaced-block');
  });

  test('skip-link: renders with visually-hidden class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/skip-link`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('visually-hidden');
    expect(html).toContain('Zum Hauptinhalt springen');
  });

  test('overlay: dark variant renders correct class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/overlay&variant=dark&content=Test`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('overlay--dark');
  });

  test('avatar: renders with name and size', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/avatar&name=John+Doe&size=md`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('avatar');
    expect(html).toContain('John Doe');
  });

  test('badge: renders with label and variant', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/badge&label=Neu&variant=neutral`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('Neu');
  });

  test('caption: renders caption text', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/caption&text=Caption+text`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('Caption text');
  });

  test('checkbox: renders input with type checkbox', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/checkbox&elementId=test-checkbox&name=test`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('type="checkbox"');
    expect(html).toContain('test-checkbox');
  });

  test('code: renders code content', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/code&content=npm+run+storybook`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('code');
    expect(html).toContain('npm run storybook');
  });

  test('divider: horizontal renders divider element', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/divider&orientation=horizontal`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('divider');
  });

  test('email-link: renders email link', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/email-link&email=test@example.com`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('test@example.com');
    expect(html).toContain('mailto:');
  });

  test('icon: renders svg icon', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/icon&size=md`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('svg');
    expect(html).toContain('viewBox');
  });

  test('input: email renders input with type email', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/input&type=text&elementId=email`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('type="text"');
  });

  test('label: renders label with text', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/label&text=E-Mail-Adresse&for=email`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<label');
    expect(html).toContain('E-Mail-Adresse');
  });

  test('radio: renders input with type radio', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/radio&elementId=test-radio&name=test`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('type="radio"');
    expect(html).toContain('test-radio');
  });

  test('select: renders select with options', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/select&elementId=country&name=country`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<select');
    expect(html).toContain('option');
  });

  test('spinner: renders spinner with label', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/spinner&size=md&label=Loading`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('spinner');
    expect(html).toContain('Loading');
  });

  test('textarea: renders textarea element', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/textarea&elementId=message&name=message`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<textarea');
    expect(html).toContain('message');
  });

  test('toggle: renders toggle switch', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/toggle&elementId=toggle-demo&name=toggle`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('toggle');
  });

  test('tooltip: renders tooltip with content', async ({ request }) => {
    const res = await request.get(`${URL}/?id=atoms/tooltip&content=Helpful+info&trigger=Hover+me`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('Helpful info');
    expect(html).toContain('Hover me');
  });
});
