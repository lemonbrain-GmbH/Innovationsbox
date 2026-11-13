# Auth Tests & Documentation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract and unit-test the profile completion logic, add E2E tests for auth flows and expert pool visibility, and update auth documentation.

**Architecture:** The profile completion closure is extracted into `ProfileCompletionChecker` in `inno_frontend/src/Service/`. E2E tests use Playwright with browser context (not API-only). Documentation extends the existing `docs/auth-pages.md`.

**Tech Stack:** PHP 8.2, Pest, Mockery, Playwright, Concrete CMS 9.4+

**Spec:** `docs/superpowers/specs/2026-04-29-auth-tests-docs-design.md`

---

### Task 1: Extract ProfileCompletionChecker class

**Files:**
- Create: `public/packages/inno_frontend/src/Service/ProfileCompletionChecker.php`
- Modify: `public/packages/inno_frontend/controller.php:70-106`

- [ ] **Step 1: Create the ProfileCompletionChecker class**

Create `public/packages/inno_frontend/src/Service/ProfileCompletionChecker.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Service;

use Concrete\Core\Attribute\Key\UserKey;
use Concrete\Core\User\UserInfo;

class ProfileCompletionChecker
{
    private const ALLOWED_PREFIXES = ['/account', '/login', '/register', '/dashboard'];

    public function needsCompletion(UserInfo $userInfo, string $pagePath): bool
    {
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if ($pagePath !== '' && str_starts_with($pagePath, $prefix)) {
                return false;
            }
        }

        $aks = UserKey::getRegistrationList();
        foreach ($aks as $ak) {
            if (($ak->isAttributeKeyRequiredOnRegister() || $ak->isAttributeKeyRequiredOnProfile())
                && empty($userInfo->getAttribute($ak))) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 2: Update controller.php to use the new class**

In `public/packages/inno_frontend/controller.php`, replace the closure body (lines 73-106) with:

```php
$director->addListener('on_page_view', function ($event) use ($app) {
    $u = $app->make(\Concrete\Core\User\User::class);
    if (!$u->isRegistered()) {
        return;
    }

    $page = $event->getPageObject();
    if (!$page || $page->isError() || $page->isAdminArea()) {
        return;
    }

    $ui = \Concrete\Core\User\UserInfo::getByID($u->getUserID());
    if (!$ui) {
        return;
    }

    $checker = new \Concrete\Package\InnoFrontend\Service\ProfileCompletionChecker();
    $path = (string) ($page->getCollectionPath() ?? '');
    if ($checker->needsCompletion($ui, $path)) {
        $editUrl = (string) \Concrete\Core\Support\Facade\Url::to('/account/edit_profile') . '?complete=1';
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('HTTP/1.1 302 Found');
        header('Location: ' . $editUrl);
        exit;
    }
});
```

- [ ] **Step 3: Verify lint passes**

Run: `PATH="./vendor/bin:$PATH" composer lint:php`
Expected: No errors

- [ ] **Step 4: Commit**

```bash
PATH="./vendor/bin:$PATH" git add public/packages/inno_frontend/src/Service/ProfileCompletionChecker.php \
    public/packages/inno_frontend/controller.php
PATH="./vendor/bin:$PATH" git commit -m "refactor(frontend): extract ProfileCompletionChecker from controller closure"
```

---

### Task 2: Unit tests for ProfileCompletionChecker

**Files:**
- Create: `tests/unit/inno_frontend/Service/ProfileCompletionCheckerTest.php`

- [ ] **Step 1: Write tests**

Create `tests/unit/inno_frontend/Service/ProfileCompletionCheckerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Core\Attribute\Key\UserKey;
use Concrete\Core\User\UserInfo;
use Concrete\Package\InnoFrontend\Service\ProfileCompletionChecker;

afterEach(fn () => Mockery::close());

test('returns false for allowed path /account', function () {
    $ui = Mockery::mock(UserInfo::class);
    $checker = new ProfileCompletionChecker();
    expect($checker->needsCompletion($ui, '/account/edit_profile'))->toBeFalse();
});

test('returns false for allowed path /login', function () {
    $ui = Mockery::mock(UserInfo::class);
    $checker = new ProfileCompletionChecker();
    expect($checker->needsCompletion($ui, '/login'))->toBeFalse();
});

test('returns false for allowed path /register', function () {
    $ui = Mockery::mock(UserInfo::class);
    $checker = new ProfileCompletionChecker();
    expect($checker->needsCompletion($ui, '/register'))->toBeFalse();
});

test('returns false for allowed path /dashboard', function () {
    $ui = Mockery::mock(UserInfo::class);
    $checker = new ProfileCompletionChecker();
    expect($checker->needsCompletion($ui, '/dashboard/sitemap'))->toBeFalse();
});

test('returns false when all required attributes are filled', function () {
    $ak = Mockery::mock(\Concrete\Core\Entity\Attribute\Key\UserKey::class);
    $ak->shouldReceive('isAttributeKeyRequiredOnRegister')->andReturn(true);
    $ak->shouldReceive('isAttributeKeyRequiredOnProfile')->andReturn(false);

    $ui = Mockery::mock(UserInfo::class);
    $ui->shouldReceive('getAttribute')->with($ak)->andReturn('filled value');

    // Mock the static call
    Mockery::mock('alias:' . UserKey::class)
        ->shouldReceive('getRegistrationList')
        ->andReturn([$ak]);

    $checker = new ProfileCompletionChecker();
    expect($checker->needsCompletion($ui, '/some-page'))->toBeFalse();
});

test('returns true when required attribute is empty', function () {
    $ak = Mockery::mock(\Concrete\Core\Entity\Attribute\Key\UserKey::class);
    $ak->shouldReceive('isAttributeKeyRequiredOnRegister')->andReturn(false);
    $ak->shouldReceive('isAttributeKeyRequiredOnProfile')->andReturn(true);

    $ui = Mockery::mock(UserInfo::class);
    $ui->shouldReceive('getAttribute')->with($ak)->andReturn('');

    Mockery::mock('alias:' . UserKey::class)
        ->shouldReceive('getRegistrationList')
        ->andReturn([$ak]);

    $checker = new ProfileCompletionChecker();
    expect($checker->needsCompletion($ui, '/some-page'))->toBeTrue();
});

test('returns false for empty path', function () {
    $ui = Mockery::mock(UserInfo::class);

    Mockery::mock('alias:' . UserKey::class)
        ->shouldReceive('getRegistrationList')
        ->andReturn([]);

    $checker = new ProfileCompletionChecker();
    expect($checker->needsCompletion($ui, ''))->toBeFalse();
});
```

Note: The static mock `alias:UserKey` may require `@runInSeparateProcess` or Pest's `uses()` config. If tests conflict, the path-based tests (first 4) don't need the static mock and will always work.

- [ ] **Step 2: Run tests**

Run: `PATH="./vendor/bin:$PATH" ./vendor/bin/pest tests/unit/inno_frontend/Service/ProfileCompletionCheckerTest.php --verbose`
Expected: 7 tests PASS

If the static mock tests fail due to Mockery alias conflicts, split into two test files: one for path checks (no mock needed), one for attribute checks (with alias mock).

- [ ] **Step 3: Run full test suite**

Run: `PATH="./vendor/bin:$PATH" composer test`
Expected: All tests pass (82 existing + 7 new = 89)

- [ ] **Step 4: Commit**

```bash
PATH="./vendor/bin:$PATH" git add tests/unit/inno_frontend/Service/ProfileCompletionCheckerTest.php
PATH="./vendor/bin:$PATH" git commit -m "test(frontend): add unit tests for ProfileCompletionChecker"
```

---

### Task 3: E2E test — auth flow

**Files:**
- Create: `tests/e2e/specs/auth-flow.spec.ts`

- [ ] **Step 1: Create the auth flow E2E test**

Create `tests/e2e/specs/auth-flow.spec.ts`:

```typescript
import { test, expect } from '@playwright/test';

const TEST_USER = {
  email: 'marco@lemonbrain.ch',
  password: '123456',
};

test.describe('Authentication flow', () => {
  test('login, edit profile display name, and logout', async ({ page }) => {
    // 1. Visit login page
    await page.goto('/login');
    await expect(page.locator('h1, .auth-card__title')).toBeVisible();

    // 2. Fill login form and submit
    await page.fill('input[name="uName"]', TEST_USER.email);
    await page.fill('input[name="uPassword"]', TEST_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/(account|index\.php).*|\/$/);

    // 3. Navigate to edit profile
    await page.goto('/account/edit_profile');
    await expect(page.locator('h1')).toContainText('Edit Profile');

    // 4. Check that display_name field exists (rendered by CMS attribute system)
    const displayNameField = page.locator('input[name*="display_name"], [data-attribute-key-handle="display_name"] input');
    await expect(displayNameField.first()).toBeVisible({ timeout: 5000 });

    // 5. Logout via URL
    await page.goto('/login/do_logout');
    await page.goto('/login');
    await expect(page.locator('input[name="uName"]')).toBeVisible();
  });

  test('avatar page renders cropper for logged-in user', async ({ page }) => {
    // Login
    await page.goto('/login');
    await page.fill('input[name="uName"]', TEST_USER.email);
    await page.fill('input[name="uPassword"]', TEST_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/(account|index\.php).*|\/$/);

    // Visit avatar page
    await page.goto('/account/avatar');
    await expect(page.locator('[data-view="account"]')).toBeVisible();

    // The avatar-cropper Vue component should be rendered
    await expect(page.locator('avatar-cropper, .ccm-account-avatar-cropper')).toBeVisible({ timeout: 10000 });
  });
});
```

- [ ] **Step 2: Run E2E tests** (requires running CMS)

Run: `APP_PORT=80 npx playwright test tests/e2e/specs/auth-flow.spec.ts --project=chromium`

If tests fail due to URL mismatch, adjust `APP_PORT` in `.env` or pass it inline.

- [ ] **Step 3: Commit**

```bash
PATH="./vendor/bin:$PATH" git add tests/e2e/specs/auth-flow.spec.ts
PATH="./vendor/bin:$PATH" git commit -m "test(e2e): add auth flow tests for login, profile, and avatar"
```

---

### Task 4: E2E test — expert pool visibility

**Files:**
- Create: `tests/e2e/specs/expert-pool.spec.ts`

- [ ] **Step 1: Create the expert pool E2E test**

Create `tests/e2e/specs/expert-pool.spec.ts`:

```typescript
import { test, expect } from '@playwright/test';

const TEST_USER = {
  email: 'marco@lemonbrain.ch',
  password: '123456',
};

test.describe('Expert pool visibility', () => {
  test('anonymous user sees expert pool without emails or profile links', async ({ page }) => {
    await page.goto('/members/directory');

    // Title should be Expertinnen-Pool
    await expect(page.locator('h1')).toContainText('Expertinnen-Pool');

    // If there are member cards, they should NOT have email or profile links
    const cards = page.locator('.member-card');
    const cardCount = await cards.count();

    if (cardCount > 0) {
      // Cards should be <div> not <a> (no links for anonymous)
      const firstCard = cards.first();
      const tagName = await firstCard.evaluate(el => el.tagName.toLowerCase());
      expect(tagName).toBe('div');

      // No email should be visible
      await expect(firstCard.locator('.member-card__email')).toHaveCount(0);
    }
  });

  test('logged-in user sees expert pool with emails and profile links', async ({ page }) => {
    // Login
    await page.goto('/login');
    await page.fill('input[name="uName"]', TEST_USER.email);
    await page.fill('input[name="uPassword"]', TEST_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/(account|index\.php).*|\/$/);

    // Visit expert pool
    await page.goto('/members/directory');
    await expect(page.locator('h1')).toContainText('Expertinnen-Pool');

    // If there are member cards, they should be links with email
    const cards = page.locator('.member-card');
    const cardCount = await cards.count();

    if (cardCount > 0) {
      const firstCard = cards.first();
      const tagName = await firstCard.evaluate(el => el.tagName.toLowerCase());
      expect(tagName).toBe('a');
    }
  });
});
```

- [ ] **Step 2: Run E2E tests**

Run: `APP_PORT=80 npx playwright test tests/e2e/specs/expert-pool.spec.ts --project=chromium`

- [ ] **Step 3: Commit**

```bash
PATH="./vendor/bin:$PATH" git add tests/e2e/specs/expert-pool.spec.ts
PATH="./vendor/bin:$PATH" git commit -m "test(e2e): add expert pool visibility tests for anonymous vs logged-in"
```

---

### Task 5: Update auth documentation

**Files:**
- Modify: `docs/auth-pages.md`

- [ ] **Step 1: Extend docs/auth-pages.md**

Add the following sections before "## How to Add or Modify an Auth Page":

```markdown
## User Attributes

| Handle | Label | Type | Required on Register | Required on Profile | Public (Member List) | Editable on Profile |
|---|---|---|---|---|---|---|
| `display_name` | Anzeigename | Text | no | yes | yes | yes |
| `expertise_area` | Fachgebiet | Text | no | no | yes | yes |
| `profile_private_messages_enabled` | Private Messages | Boolean | no | no | no | yes |

The `display_name` attribute triggers forced profile completion — users with an empty display name are redirected to `/account/edit_profile?complete=1` on every page view.

Display name is shown with a fallback: `getAttribute('display_name') ?: getUserName()`. This is used in the header user menu, member profile, and expert pool.

## Roles & Permissions

### CMS Groups → Application Roles

Defined in `inno_shared/.../ConcreteCmsAuth.php` (`GROUP_TO_ROLE`):

| CMS Group | App Role |
|---|---|
| Mitglied | member |
| Entwickler | developer |
| Initiator | initiator |
| Moderator | moderator |
| Administrator | administrator |
| Experte | expert |

### Permission Map

Defined in `ConcreteCmsAuth.php` (`PERMISSION_MAP`):

| Permission | Allowed Roles |
|---|---|
| `idea.submit` | initiator, administrator |
| `idea.view` | all roles + anonymous |
| `idea.vote` | member, developer, initiator, moderator, administrator |
| `moderation.review` | moderator, administrator |
| `space.join` | developer, initiator, administrator |
| `space.manage` | administrator |
| `expert.manage` | moderator, administrator |

Anonymous users can only access `idea.view` (hardcoded in `hasPermission()`).

## Expert Pool (Expertinnen-Pool)

The members directory at `/members/directory` is filtered to show only users in the CMS group "Experte".

**How it works:**
- `Directory` controller (`controllers/single_page/members/directory.php`) calls `$this->userList->filterByGroup('Experte')` — filtering happens at DB level
- Template receives `$isLoggedIn` from the controller
- Anonymous visitors see: avatar, display name, expertise area — no email, no profile links
- Logged-in users see: all of the above + email + clickable profile links

**Setting expert status:** Moderators assign users to the "Experte" group via CMS Dashboard (*Members → Search Users → [User] → Groups*).

## Forced Profile Completion

Registered users with empty required attributes are redirected to `/account/edit_profile?complete=1` on every page view.

**Mechanism:** `ProfileCompletionChecker` (in `inno_frontend/src/Service/`) checks all attributes from `UserKey::getRegistrationList()` where `isAttributeKeyRequiredOnRegister()` or `isAttributeKeyRequiredOnProfile()` is true.

**Allowed paths (no redirect):** `/account/*`, `/login`, `/register`, `/dashboard/*`

**Currently triggers for:** `display_name` (required on profile).
```

- [ ] **Step 2: Verify no formatting issues**

Read the file to make sure the markdown renders correctly.

- [ ] **Step 3: Commit**

```bash
PATH="./vendor/bin:$PATH" git add docs/auth-pages.md
PATH="./vendor/bin:$PATH" git commit -m "docs: expand auth documentation with attributes, permissions, expert pool, and profile completion"
```
