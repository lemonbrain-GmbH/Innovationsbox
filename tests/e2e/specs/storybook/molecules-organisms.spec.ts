import { test, expect } from '@playwright/test';

const URL = 'http://localhost:6007';

test.describe('Molecule components', () => {
  test('feature-button: renders with feature-button class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/feature-button&label=Test&href=/go`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('class="feature-button button"');
    expect(html).toContain('Test');
  });

  test('page-card: renders image and title', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/page-card&title=Solar&href=/s&imageSrc=img.jpg&imageAlt=Img`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('class="page-link"');
    expect(html).toContain('page-link-text');
    expect(html).toContain('Solar');
  });

  test('nav-item: active renders nav-selected class', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/nav-item&text=Home&active=true`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('nav-selected');
  });

  test('accent-text: renders accent span', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/accent-text&text=Quote`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('class="accent"');
  });

  test('question-text: renders question span', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/question-text&text=FAQ`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('class="question"');
  });

  test('partner-logo: renders logo-link with image', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/partner-logo&name=Test&href=/x&imageSrc=logo.png`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('class="logo-link"');
    expect(html).toContain('alt="Test"');
  });

  test('alert: info variant renders alert with icon', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/alert&variant=info&title=Notice&message=Info+message`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('alert');
    expect(html).toContain('Notice');
  });

  test('auth-card: renders card with title', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/auth-card&title=Login&subtitle=Welcome`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('auth-card');
    expect(html).toContain('Login');
  });

  test('avatar-upload: renders with avatar image', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/avatar-upload&userName=Test+User`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('avatar-upload');
  });

  test('breadcrumb: renders navigation breadcrumbs', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/breadcrumb`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('breadcrumb');
    expect(html).toContain('Start');
  });

  test('card: elevated variant renders card wrapper', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/card&variant=elevated&title=Card+Title&body=Content`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('card');
    expect(html).toContain('Card Title');
  });

  test('collaboard-item: renders collaboard link', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/collaboard-item&title=Board&elementId=link_123`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('collaboard');
    expect(html).toContain('Board');
  });

  test('dropdown: renders dropdown button with items', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/dropdown&triggerLabel=Actions&elementId=demo-dropdown`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('dropdown');
    expect(html).toContain('Actions');
  });

  test('empty-state: renders empty state illustration', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/empty-state&title=No+Ideas`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('empty-state');
    expect(html).toContain('No Ideas');
  });

  test('form-field: email renders with label and input', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/form-field&label=Email&elementId=email&type=email`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('form-field');
    expect(html).toContain('Email');
  });

  test('join-request-card: renders user join request', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/join-request-card&userName=John+Doe&requestId=req_123`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('join-request-card');
    expect(html).toContain('John Doe');
  });

  test('member-card: renders member profile card', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/member-card`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('member-card');
  });

  test('modal: renders modal dialog', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/modal&elementId=modal-demo&title=Confirm`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('modal');
    expect(html).toContain('Confirm');
  });

  test('pagination: renders page links', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/pagination&currentPage=5&totalPages=10`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('pagination');
  });

  test('password-field: renders password input with label', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/password-field&label=Password&elementId=password`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('password-field');
    expect(html).toContain('Password');
  });

  test('project-card: renders project with info', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/project-card&title=Solar+Project&elementId=space_1`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('project-card');
    expect(html).toContain('Solar Project');
  });

  test('search-bar: renders search input', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/search-bar&elementId=search&name=q`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('search-bar');
  });

  test('skeleton: text variant renders loading skeleton', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/skeleton&variant=text`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('skeleton');
  });

  test('status-update-entry: renders update with title and body', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/status-update-entry&title=Update+Title&authorName=John`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('status-update-entry');
    expect(html).toContain('Update Title');
  });

  test('tabs: renders tab navigation', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/tabs&elementId=demo-tabs`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('tabs');
    expect(html).toContain('Übersicht');
  });

  test('user-menu: renders user profile menu', async ({ request }) => {
    const res = await request.get(`${URL}/?id=molecules/user-menu`);
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('user-menu');
  });
});

test.describe('Organism components', () => {
  test('hero-image: renders wrapper with heading and image', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/hero-image&title=Hello&imageSrc=hero.jpg&imageAlt=Hero`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('hero-image-wrapper');
    expect(html).toContain('hero-image-text');
    expect(html).toContain('<h1>Hello</h1>');
  });

  test('page-list-grid: renders grid with page cards from JSON', async ({ request }) => {
    const pages = JSON.stringify([
      { title: 'A', href: '#', imageSrc: 'a.jpg', imageAlt: 'A' },
      { title: 'B', href: '#', imageSrc: 'b.jpg', imageAlt: 'B' },
    ]);
    const res = await request.get(`${URL}/?id=organisms/page-list-grid&pages=${encodeURIComponent(pages)}`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('page-list-pages');
    expect(html).toContain('page-link-text');
  });

  test('navigation-drawer: renders nav-container with drawer', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/navigation-drawer`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('nav-container');
    expect(html).toContain('nav-drawer');
    expect(html).toContain('nav-overlay');
    expect(html).toContain('nav-toggle');
  });

  test('header: renders page-header with skip-link and nav', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/header`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('page-header');
    expect(html).toContain('skip-link');
    expect(html).toContain('nav-container');
  });

  test('footer: renders footer with logos', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/footer`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('<footer>');
    expect(html).toContain('logo-link');
  });

  test('content-block: renders narrow container with content', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/content-block&content=<p>Test</p>`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('content-block');
    expect(html).toContain('lb-container-narrow');
    expect(html).toContain('<p>Test</p>');
  });

  test('collaboard-panel: renders collaboard links list', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/collaboard-panel`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('collaboard-panel');
    expect(html).toContain('Brainstorming Board');
  });

  test('project-space-sidebar: renders members and join requests', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/project-space-sidebar`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('project-space-sidebar');
    expect(html).toContain('Maria Keller');
  });

  test('status-update-timeline: renders list of updates', async ({ request }) => {
    const res = await request.get(`${URL}/?id=organisms/status-update-timeline`);
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('status-update-timeline');
    expect(html).toContain('Erstgespräche abgeschlossen');
  });
});
