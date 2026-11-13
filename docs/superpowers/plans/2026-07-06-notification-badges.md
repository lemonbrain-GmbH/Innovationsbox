# Notification Badge Counts Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show notification badge counts on the user menu avatar and next to relevant menu items (Moderation, Meine Anfragen, Meine Projekträume) so users see pending actions at a glance.

**Architecture:** A new `GET /api/user/counts` endpoint returns pending counts. The header element queries counts server-side and passes them to the user-menu component. The component renders badge spans inline. The user-menu JS reads a data attribute to inject the total badge on the avatar trigger. No polling — fresh on each page load.

**Tech Stack:** PHP 8.3, Concrete CMS 9.x, TypeScript, SCSS

## Global Constraints

- Badge counts rendered server-side (no flash of empty badges on page load)
- Counts are 0 for users who don't have the relevant role — not hidden, just 0
- Badge hidden when count is 0 (CSS `display: none` via `:empty` or conditional PHP)
- Badges must work in both desktop dropdown and mobile nav drawer (same DOM, moved by existing JS)
- No polling — counts are per page load only
- Follow existing hexagonal architecture for the API endpoint

---

### Task 1: API Endpoint + Server-Side Count Queries

Add `GET /api/user/counts` to `UserApiController` in `inno_moderation`. The endpoint runs three COUNT queries against `ib_moderation_reviews`, `ib_expert_requests`, and `ib_project_space_members`, gated by the user's role.

Also create a standalone helper class that the header PHP can call directly (without going through HTTP) to get the same counts for server-side rendering.

**Files:**
- Modify: `public/packages/inno_moderation/src/Adapter/In/Http/UserApiController.php`
- Modify: `public/packages/inno_moderation/controller.php` (register route)
- Create: `public/packages/inno_moderation/src/Adapter/Out/Persistence/UserCountsQuery.php`
- Create: `tests/unit/inno_moderation/Adapter/UserCountsQueryTest.php`

**Interfaces:**
- Produces: `UserCountsQuery::getCounts(int $userId, bool $isModerator, bool $isExpert): array` returning `['moderationReviews' => int, 'expertRequests' => int, 'joinRequests' => int, 'total' => int]`
- Produces: `GET /api/user/counts` returning the same JSON

- [ ] **Step 1: Create UserCountsQuery class**

```php
<?php
// public/packages/inno_moderation/src/Adapter/Out/Persistence/UserCountsQuery.php
declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;

class UserCountsQuery
{
    public function __construct(
        private readonly Connection $db,
    ) {}

    /**
     * @return array{moderationReviews: int, expertRequests: int, joinRequests: int, total: int}
     */
    public function getCounts(int $userId, bool $isModerator, bool $isExpert): array
    {
        $moderationReviews = 0;
        $expertRequests = 0;
        $joinRequests = 0;

        if ($isModerator) {
            $moderationReviews = (int) $this->db->fetchOne(
                "SELECT COUNT(*) FROM ib_moderation_reviews WHERE decision = 'pending'"
            );
        }

        if ($isExpert) {
            $expertRequests = (int) $this->db->fetchOne(
                "SELECT COUNT(*) FROM ib_expert_requests WHERE expert_user_id = ? AND status = 'pending'",
                [(string) $userId]
            );
        }

        // Join requests: count pending requests in spaces where this user is the initiator
        $joinRequests = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM ib_project_space_members m
             INNER JOIN ib_project_spaces s ON m.space_id = s.id
             WHERE s.initiator_id = ? AND m.request_status = 'pending' AND m.role != 'initiator'",
            [$userId]
        );

        $total = $moderationReviews + $expertRequests + $joinRequests;

        return compact('moderationReviews', 'expertRequests', 'joinRequests', 'total');
    }
}
```

- [ ] **Step 2: Add counts() endpoint to UserApiController**

In `public/packages/inno_moderation/src/Adapter/In/Http/UserApiController.php`, add this method before `requirePermission()`:

```php
    public function counts(): JsonResponse
    {
        try {
            if (!$this->auth->isAuthenticated()) {
                throw new AuthException('Not authenticated', [], false);
            }

            $userId = (int) $this->auth->getUserId();
            $countsQuery = $this->app->make(\Concrete\Package\InnoModeration\Adapter\Out\Persistence\UserCountsQuery::class);

            $user = User::getByUserID($userId);
            $isModerator = $this->auth->hasPermission('moderation.review');
            $isExpert = false;
            $experteGroup = Group::getByName('Experte');
            if ($experteGroup && $user) {
                $isExpert = $user->inGroup($experteGroup);
            }

            $counts = $countsQuery->getCounts($userId, $isModerator, $isExpert);

            return new JsonResponse($counts);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }
```

Add `$this->app` access by adding to the constructor — but wait, `AbstractController` already has `$this->app`. No change needed.

- [ ] **Step 3: Register route in controller.php**

In `public/packages/inno_moderation/controller.php`, after the user routes (around line 66), add:

```php
        $router->register('/api/user/counts', "{$userCtrl}::counts", 'api.user.counts', [], [], '', [], ['GET']);
```

- [ ] **Step 4: Write unit test for UserCountsQuery**

```php
<?php
// tests/unit/inno_moderation/Adapter/UserCountsQueryTest.php
declare(strict_types=1);

use Concrete\Package\InnoModeration\Adapter\Out\Persistence\UserCountsQuery;

afterEach(fn() => Mockery::close());

test('getCounts() returns zeros for non-moderator non-expert with no spaces', function () {
    $db = Mockery::mock(\Concrete\Core\Database\Connection\Connection::class);

    // Join requests query (always runs)
    $db->shouldReceive('fetchOne')
        ->once()
        ->with(Mockery::on(fn($sql) => str_contains($sql, 'ib_project_space_members')), [99])
        ->andReturn('0');

    $query = new UserCountsQuery($db);
    $result = $query->getCounts(99, false, false);

    expect($result)->toBe([
        'moderationReviews' => 0,
        'expertRequests' => 0,
        'joinRequests' => 0,
        'total' => 0,
    ]);
});

test('getCounts() queries moderation reviews for moderator', function () {
    $db = Mockery::mock(\Concrete\Core\Database\Connection\Connection::class);

    $db->shouldReceive('fetchOne')
        ->once()
        ->with(Mockery::on(fn($sql) => str_contains($sql, 'ib_moderation_reviews')))
        ->andReturn('5');

    $db->shouldReceive('fetchOne')
        ->once()
        ->with(Mockery::on(fn($sql) => str_contains($sql, 'ib_project_space_members')), [1])
        ->andReturn('2');

    $query = new UserCountsQuery($db);
    $result = $query->getCounts(1, true, false);

    expect($result['moderationReviews'])->toBe(5)
        ->and($result['joinRequests'])->toBe(2)
        ->and($result['total'])->toBe(7);
});

test('getCounts() queries expert requests for expert', function () {
    $db = Mockery::mock(\Concrete\Core\Database\Connection\Connection::class);

    $db->shouldReceive('fetchOne')
        ->once()
        ->with(Mockery::on(fn($sql) => str_contains($sql, 'ib_expert_requests')), ['42'])
        ->andReturn('3');

    $db->shouldReceive('fetchOne')
        ->once()
        ->with(Mockery::on(fn($sql) => str_contains($sql, 'ib_project_space_members')), [42])
        ->andReturn('0');

    $query = new UserCountsQuery($db);
    $result = $query->getCounts(42, false, true);

    expect($result['expertRequests'])->toBe(3)
        ->and($result['total'])->toBe(3);
});
```

- [ ] **Step 5: Run tests**

Run: `composer test -- --filter=UserCountsQueryTest`
Expected: All 3 tests pass

Run: `composer test`
Expected: All tests pass

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_moderation/src/Adapter/In/Http/UserApiController.php public/packages/inno_moderation/src/Adapter/Out/Persistence/UserCountsQuery.php public/packages/inno_moderation/controller.php tests/unit/inno_moderation/Adapter/UserCountsQueryTest.php
git commit -m "feat: add /api/user/counts endpoint for notification badges"
```

---

### Task 2: Server-Side Badge Rendering in Header + User Menu

Modify the header element to query counts and pass them to user-menu. Modify user-menu to render badge spans. Add "Meine Projekträume" menu item.

**Files:**
- Modify: `public/packages/inno_frontend/themes/innovationsbox/elements/header.php`
- Modify: `public/packages/inno_frontend/components/molecules/user-menu/user-menu.php`

**Interfaces:**
- Consumes: `UserCountsQuery::getCounts()` from Task 1
- Produces: `data-badge-total="N"` attribute on `.user-menu__trigger` (used by TS in Task 3), badge `<span>` elements on menu items

- [ ] **Step 1: Query counts in header.php**

In `public/packages/inno_frontend/themes/innovationsbox/elements/header.php`, add after the `$logoutUrl` assignment (around line 55, inside the `if ($u->isRegistered())` block), before the closing `}`:

```php
    // Query notification badge counts
    $badgeCounts = ['moderationReviews' => 0, 'expertRequests' => 0, 'joinRequests' => 0, 'total' => 0];
    try {
        $countsQuery = app(\Concrete\Package\InnoModeration\Adapter\Out\Persistence\UserCountsQuery::class);

        $isMod = false;
        $isExp = false;
        if ($u->isSuperUser()) {
            $isMod = true;
        } else {
            foreach ($u->getUserGroups() as $gID) {
                $g = \Concrete\Core\User\Group\Group::getByID($gID);
                if ($g) {
                    $n = mb_strtolower($g->getGroupName());
                    if ($n === 'moderator' || $n === 'administrator' || $n === 'administratoren' || $n === 'admins') {
                        $isMod = true;
                    }
                    if ($n === 'experte') {
                        $isExp = true;
                    }
                }
            }
        }

        $badgeCounts = $countsQuery->getCounts((int) $u->getUserID(), $isMod, $isExp);
    } catch (\Throwable $e) {
        // Silently fail — badge counts are non-critical
    }
```

Then update the `$props` array passed to user-menu (around line 77-84) to include the counts:

```php
            $props = [
                'userName' => $userName,
                'userEmail' => $userEmail,
                'userInitials' => $userInitials,
                'avatarUrl' => $avatarUrl,
                'editProfileUrl' => $editProfileUrl,
                'logoutUrl' => $logoutUrl,
                'moderationCount' => $badgeCounts['moderationReviews'],
                'expertCount' => $badgeCounts['expertRequests'],
                'joinCount' => $badgeCounts['joinRequests'],
                'totalCount' => $badgeCounts['total'],
            ];
```

- [ ] **Step 2: Add badges and new menu item to user-menu.php**

In `public/packages/inno_frontend/components/molecules/user-menu/user-menu.php`:

Add new prop extraction after the existing props (around line 20):

```php
/** @var int $moderationCount */
$moderationCount = (int) ($props['moderationCount'] ?? 0);
/** @var int $expertCount */
$expertCount = (int) ($props['expertCount'] ?? 0);
/** @var int $joinCount */
$joinCount = (int) ($props['joinCount'] ?? 0);
/** @var int $totalCount */
$totalCount = (int) ($props['totalCount'] ?? 0);
```

Add `data-badge-total` attribute to the trigger button (line 26-31). After the avatar include (line 41), add the total badge:

```php
    </button>
```

becomes:

```php
        <?php if ($totalCount > 0): ?>
            <span class="user-menu__badge" aria-label="<?= $totalCount ?> offene Aufgaben"><?= $totalCount ?></span>
        <?php endif; ?>
    </button>
```

Add "Meine Projekträume" item after "Meine Projektideen" (after line 80):

```php
        <a href="/projektraeume" class="user-menu__item" role="menuitem">
            Meine Projekträume
            <?php if ($joinCount > 0): ?>
                <span class="user-menu__item-badge"><?= $joinCount ?></span>
            <?php endif; ?>
        </a>
```

Add badge to "Moderation" item (modify line 104-106):

```php
        <?php if ($isModerator): ?>
            <a href="/moderation" class="user-menu__item" role="menuitem">
                Moderation
                <?php if ($moderationCount > 0): ?>
                    <span class="user-menu__item-badge"><?= $moderationCount ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
```

Add badge to "Meine Anfragen" item (modify line 121-124):

```php
        <?php if ($isExperte): ?>
            <a href="/expertanfragen" class="user-menu__item" role="menuitem">
                Meine Anfragen
                <?php if ($expertCount > 0): ?>
                    <span class="user-menu__item-badge"><?= $expertCount ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
```

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/themes/innovationsbox/elements/header.php public/packages/inno_frontend/components/molecules/user-menu/user-menu.php
git commit -m "feat: render notification badge counts server-side in user menu"
```

---

### Task 3: Badge Styles (SCSS)

Add CSS for the notification bubble on the avatar and the inline item badges.

**Files:**
- Modify: `public/packages/inno_frontend/components/molecules/user-menu/user-menu.scss`

**Interfaces:**
- Consumes: `.user-menu__badge` and `.user-menu__item-badge` class names from Task 2

- [ ] **Step 1: Add badge styles to user-menu.scss**

Add inside the `.user-menu` block, after `&__trigger` (around line 28):

```scss
  &__badge {
    position: absolute;
    top: 0;
    right: 0;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: var(--radius-full);
    background: var(--color-status-error-600);
    color: var(--color-white);
    font-size: 11px;
    font-weight: var(--font-weight-bold);
    line-height: 18px;
    text-align: center;
    pointer-events: none;
  }

  &__item-badge {
    margin-left: auto;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: var(--radius-full);
    background: var(--color-status-error-600);
    color: var(--color-white);
    font-size: 11px;
    font-weight: var(--font-weight-bold);
    line-height: 20px;
    text-align: center;
  }
```

Also update `&__trigger` to add `position: relative` (it already has it implicitly, but make it explicit for the absolute badge positioning):

The trigger already has no `position` set. Add `position: relative;` to `&__trigger`.

- [ ] **Step 2: Build and verify**

Run: `npm run development`
Expected: Builds without errors

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/user-menu/user-menu.scss
git commit -m "feat: add notification badge styles for user menu"
```

---

### Task 4: Build Assets + E2E Test Verification

Build the frontend, rebuild the Docker container, and verify badges show in E2E tests.

**Files:**
- Modify: `tests/e2e/specs/00-smoke.spec.ts` (add badge verification tests)

- [ ] **Step 1: Build frontend assets**

Run: `npm run development`

- [ ] **Step 2: Add badge smoke tests**

In `tests/e2e/specs/00-smoke.spec.ts`, add after the existing tests:

```typescript
  test('moderator sees badge count on user menu', async ({ page }) => {
    await loginViaUI(page, 'moderator@e2e.test', 'E2eTest!2026');
    // Navigate to a themed page to see the user menu
    await page.goto('/projektraeume');

    // Open the nav drawer (mobile) to see the user menu
    const navToggle = page.locator('.nav-toggle');
    if (await navToggle.isVisible()) {
      await navToggle.click();
      await page.waitForTimeout(500);
    }

    // The moderator has seeded pending reviews — badge should be visible
    const badge = page.locator('.user-menu__badge');
    await expect(badge).toBeVisible({ timeout: 5_000 });
    const count = await badge.textContent();
    expect(Number(count)).toBeGreaterThan(0);

    // Moderation item should have an item badge
    const modBadge = page.locator('.user-menu__item:has-text("Moderation") .user-menu__item-badge');
    await expect(modBadge).toBeVisible({ timeout: 5_000 });
  });

  test('user menu has Meine Projekträume item', async ({ page }) => {
    await loginViaUI(page, 'initiator@e2e.test', 'E2eTest!2026');
    await page.goto('/projektraeume');

    const navToggle = page.locator('.nav-toggle');
    if (await navToggle.isVisible()) {
      await navToggle.click();
      await page.waitForTimeout(500);
    }

    const item = page.locator('.user-menu__item:has-text("Meine Projekträume")');
    await expect(item).toBeVisible({ timeout: 5_000 });
  });
```

- [ ] **Step 3: Rebuild Docker container and run tests**

```bash
docker compose -f docker-compose.test.yml down -v
docker compose -f docker-compose.test.yml up -d --build --wait
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1 tests/e2e/specs/00-smoke.spec.ts
```

Expected: All tests pass

- [ ] **Step 4: Run full E2E suite**

```bash
E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e --workers=1
```

Expected: All tests pass

- [ ] **Step 5: Commit**

```bash
git add tests/e2e/specs/00-smoke.spec.ts public/packages/inno_frontend/themes/ public/packages/inno_frontend/storybook/
git commit -m "test: add E2E tests for notification badge counts"
```
