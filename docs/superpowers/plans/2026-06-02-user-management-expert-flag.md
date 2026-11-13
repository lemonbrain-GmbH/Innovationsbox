# User Management + Expert Flag + Notifications — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a moderation subpage to list users and toggle expert flag, notify moderators on new registrations, and notify users when their expert status changes.

**Architecture:** New `UserApiController` in `inno_moderation` with two endpoints (list users, toggle expert). Two new events in `inno_shared` for expert flag changes. Three new notification handlers. New single page + TS + CSS in `inno_frontend` following the `kategorien` pattern exactly.

**Tech Stack:** PHP 8.3, Pest + Mockery, TypeScript, Concrete CMS User/Group API

---

## File Structure

| File | Responsibility |
|------|---------------|
| `inno_shared/src/Domain/Event/UserFlaggedAsExpert.php` | Event |
| `inno_shared/src/Domain/Event/UserUnflaggedAsExpert.php` | Event |
| `inno_moderation/src/Adapter/In/Http/UserApiController.php` | List users + toggle expert |
| `inno_moderation/controller.php` | Routes for user API |
| `inno_notification/src/Application/EventHandler/OnUserRegistered.php` | Email moderators |
| `inno_notification/src/Application/EventHandler/OnUserFlaggedAsExpert.php` | Email user |
| `inno_notification/src/Application/EventHandler/OnUserUnflaggedAsExpert.php` | Email user |
| `inno_notification/controller.php` | Register 3 listeners |
| `inno_frontend/single_pages/moderation/benutzer.php` | Page template |
| `resources/js/single_pages/moderation-benutzer.ts` | Frontend logic |
| `resources/css/single_pages/moderation-benutzer.scss` | Styles |
| `inno_frontend/controller.php` | Register page + assets |
| `inno_frontend/single_pages/moderation/view.php` | Add nav link |

---

### Task 1: Events + UserApiController

**Files:**
- Create: `public/packages/inno_shared/src/Domain/Event/UserFlaggedAsExpert.php`
- Create: `public/packages/inno_shared/src/Domain/Event/UserUnflaggedAsExpert.php`
- Create: `public/packages/inno_moderation/src/Adapter/In/Http/UserApiController.php`
- Modify: `public/packages/inno_moderation/controller.php`

- [ ] **Step 1: Create UserFlaggedAsExpert event**

```php
<?php
// public/packages/inno_shared/src/Domain/Event/UserFlaggedAsExpert.php
declare(strict_types=1);
namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UserFlaggedAsExpert extends Event
{
    public const NAME = 'innovationsbox.user.flagged_as_expert';

    public function __construct(
        private readonly string $userId,
        private readonly string $flaggedByUserId,
    ) {}

    public function getUserId(): string { return $this->userId; }
    public function getFlaggedByUserId(): string { return $this->flaggedByUserId; }
}
```

- [ ] **Step 2: Create UserUnflaggedAsExpert event**

```php
<?php
// public/packages/inno_shared/src/Domain/Event/UserUnflaggedAsExpert.php
declare(strict_types=1);
namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UserUnflaggedAsExpert extends Event
{
    public const NAME = 'innovationsbox.user.unflagged_as_expert';

    public function __construct(
        private readonly string $userId,
        private readonly string $unflaggedByUserId,
    ) {}

    public function getUserId(): string { return $this->userId; }
    public function getUnflaggedByUserId(): string { return $this->unflaggedByUserId; }
}
```

- [ ] **Step 3: Create UserApiController**

Read `CategoryApiController` for the pattern. The user controller is a thin adapter — no domain use case needed, it directly manages CMS groups.

```php
<?php
// public/packages/inno_moderation/src/Adapter/In/Http/UserApiController.php
declare(strict_types=1);
namespace Concrete\Package\InnoModeration\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Core\User\Group\Group;
use Concrete\Core\User\User;
use Concrete\Core\User\UserInfo;
use Concrete\Core\User\UserList;
use Concrete\Package\InnoShared\Domain\Event\UserFlaggedAsExpert;
use Concrete\Package\InnoShared\Domain\Event\UserUnflaggedAsExpert;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Concrete\Package\InnoShared\Infrastructure\Http\CsrfValidationTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UserApiController extends AbstractController
{
    use CsrfValidationTrait;

    public function __construct(
        private readonly AuthInterface $auth,
        private readonly ExceptionHandlerInterface $exceptionHandler,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $this->requirePermission('moderation.review');

            $experteGroup = Group::getByName('Experte');
            $expertUserIds = [];
            if ($experteGroup) {
                foreach ($experteGroup->getGroupMembers() as $member) {
                    $expertUserIds[] = (string) $member->getUserID();
                }
            }

            $userList = new UserList();
            $userList->filterByIsActive(1);
            $userList->sortByUserName();
            $users = [];

            foreach ($userList->getResults() as $ui) {
                $userId = (string) $ui->getUserID();
                $users[] = [
                    'id' => $userId,
                    'name' => $ui->getAttribute('display_name') ?: $ui->getUserName(),
                    'email' => $ui->getUserEmail(),
                    'isExpert' => in_array($userId, $expertUserIds, true),
                ];
            }

            return new JsonResponse(['data' => $users]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    public function toggleExpert(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->requirePermission('moderation.review');

            $body = json_decode($request->getContent(), true) ?? [];
            $makeExpert = (bool) ($body['expert'] ?? false);

            $ui = UserInfo::getByID((int) $id);
            if ($ui === null) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            $experteGroup = Group::getByName('Experte');
            if ($experteGroup === null) {
                return new JsonResponse(['error' => 'Experte group not found'], 500);
            }

            $user = User::getByUserID((int) $id);
            $currentUserId = (string) $this->auth->getUserId();

            if ($makeExpert) {
                $user->enterGroup($experteGroup);
                $this->eventDispatcher->dispatch(
                    new UserFlaggedAsExpert($id, $currentUserId),
                    UserFlaggedAsExpert::NAME,
                );
            } else {
                $user->exitGroup($experteGroup);
                $this->eventDispatcher->dispatch(
                    new UserUnflaggedAsExpert($id, $currentUserId),
                    UserUnflaggedAsExpert::NAME,
                );
            }

            return new JsonResponse(['status' => 'updated', 'isExpert' => $makeExpert]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    private function requirePermission(string $permission): void
    {
        if (!$this->auth->hasPermission($permission)) {
            throw new AuthException(
                "Permission denied for {$permission}",
                ['permission' => $permission],
                $this->auth->isAuthenticated(),
            );
        }
    }
}
```

- [ ] **Step 4: Register routes in moderation controller**

In `public/packages/inno_moderation/controller.php`, add to `on_start()`:

```php
$router->register('/api/moderation/user', [UserApiController::class, 'index'], 'api.moderation.user.index', ['GET']);
$router->register('/api/moderation/user/{id}/expert', [UserApiController::class, 'toggleExpert'], 'api.moderation.user.toggle_expert', ['PUT']);
```

Add the import: `use Concrete\Package\InnoModeration\Adapter\In\Http\UserApiController;`

- [ ] **Step 5: Run tests**

Run: `./vendor/bin/pest --testsuite=Unit`

- [ ] **Step 6: Commit**

```bash
git commit -m "feat(moderation): add UserApiController with list users + toggle expert"
```

---

### Task 2: Notification Handlers

**Files:**
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnUserRegistered.php`
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnUserFlaggedAsExpert.php`
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnUserUnflaggedAsExpert.php`
- Create: `tests/unit/inno_notification/Application/OnUserRegisteredTest.php`
- Create: `tests/unit/inno_notification/Application/OnUserFlaggedAsExpertTest.php`
- Create: `tests/unit/inno_notification/Application/OnUserUnflaggedAsExpertTest.php`
- Modify: `public/packages/inno_notification/controller.php`

- [ ] **Step 1: Write tests for all 3 handlers**

Each test file follows the Pest + Mockery pattern. Two tests per handler: happy path + null email guard.

**OnUserRegisteredTest.php:** The handler receives CMS's `UserInfoWithPassword` event (not our custom event). It needs to iterate over all moderator group members and send email to each. Mock a `ModeratorResolverPort` or use the CMS Group API directly. Since this handler uses CMS APIs, mock at the Group level.

Actually, for testability, `OnUserRegistered` should take `UserResolverPort`, `EmailServiceInterface`, `LoggerInterface`, and a callable/port to get moderator emails. The simplest approach: inject a `\Closure` that returns moderator emails, defaulting to CMS Group lookup. Or use a new `ModeratorResolverPort`.

Simplest: add a `getModeratorEmails(): array` method to `ProjectSpaceReaderPort` — but that doesn't fit. Better: the handler itself resolves moderators via CMS Group directly, and we test it with a mock Group. But CMS Group is static.

For pragmatism: the handler receives the CMS event object which has `getUserInfoObject()`. It uses `Group::getByName('Moderator')` to find moderators. For testing, accept an optional `\Closure $moderatorEmailsLoader` in the constructor (same pattern as `ConcreteCmsUserResolver`).

```php
// tests/unit/inno_notification/Application/OnUserRegisteredTest.php
test('handle sends email to all moderators', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    // Mock moderator loader: returns 2 moderator emails
    $moderatorLoader = fn() => ['mod1@example.com', 'mod2@example.com'];

    $emailService->shouldReceive('send')->twice();
    $logger->shouldReceive('info')->once();

    $handler = new OnUserRegistered($emailService, $logger, $moderatorLoader);

    // CMS event passes UserInfoWithPassword, mock it
    $userInfo = Mockery::mock(\Concrete\Core\User\UserInfo::class);
    $userInfo->shouldReceive('getUserName')->andReturn('NewUser');
    $userInfo->shouldReceive('getUserEmail')->andReturn('newuser@example.com');

    $event = Mockery::mock(\Concrete\Core\User\Event\UserInfoWithPassword::class);
    $event->shouldReceive('getUserInfoObject')->andReturn($userInfo);

    $handler->handle($event);
});
```

**OnUserFlaggedAsExpertTest.php:** Standard pattern — mock `UserResolverPort`, `EmailServiceInterface`, `LoggerInterface`. Verify email to user contains "Expert·in markiert".

**OnUserUnflaggedAsExpertTest.php:** Same — verify email contains "Status wurde entfernt".

- [ ] **Step 2: Implement OnUserRegistered**

```php
<?php
// public/packages/inno_notification/src/Application/EventHandler/OnUserRegistered.php
declare(strict_types=1);
namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class OnUserRegistered
{
    /** @var \Closure(): list<string> */
    private \Closure $moderatorEmailsLoader;

    /**
     * @param null|\Closure(): list<string> $moderatorEmailsLoader
     */
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly LoggerInterface $logger,
        ?\Closure $moderatorEmailsLoader = null,
    ) {
        $this->moderatorEmailsLoader = $moderatorEmailsLoader ?? static function (): array {
            $group = \Concrete\Core\User\Group\Group::getByName('Moderator');
            if ($group === null) return [];
            $emails = [];
            foreach ($group->getGroupMembers() as $member) {
                $ui = \Concrete\Core\User\UserInfo::getByID($member->getUserID());
                if ($ui && $ui->getUserEmail()) {
                    $emails[] = $ui->getUserEmail();
                }
            }
            return $emails;
        };
    }

    public function handle(object $event): void
    {
        $userInfo = $event->getUserInfoObject();
        $userName = $userInfo->getUserName();
        $userEmail = $userInfo->getUserEmail();

        $moderatorEmails = ($this->moderatorEmailsLoader)();
        $heading = 'Neue Registrierung auf der Innovationsbox';

        foreach ($moderatorEmails as $email) {
            $this->emailService->send(
                to: $email,
                subject: $heading,
                body: EmailTemplate::wrap($heading, "<p><strong>{$userName}</strong> ({$userEmail}) hat sich auf der Plattform registriert.</p>"),
            );
        }

        $this->logger->info('Moderators notified about new registration', [
            'userName' => $userName,
            'notified' => count($moderatorEmails),
        ]);
    }
}
```

- [ ] **Step 3: Implement OnUserFlaggedAsExpert**

```php
<?php
// public/packages/inno_notification/src/Application/EventHandler/OnUserFlaggedAsExpert.php
declare(strict_types=1);
namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\UserFlaggedAsExpert;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnUserFlaggedAsExpert
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(UserFlaggedAsExpert $event): void
    {
        $email = $this->userResolver->resolveEmail($event->getUserId());
        if ($email === null) {
            $this->logger->warning('Cannot resolve email for expert flag notification', ['userId' => $event->getUserId()]);
            return;
        }

        $heading = 'Du wurdest als Expert·in markiert';
        $this->emailService->send(
            to: $email,
            subject: $heading,
            body: EmailTemplate::wrap($heading, '<p>Du wurdest als Expert·in auf der Innovationsbox markiert.</p><p>Du kannst jetzt von Projekträumen als Expert·in angefragt werden.</p>'),
        );

        $this->logger->info('User notified about expert flag', ['userId' => $event->getUserId()]);
    }
}
```

- [ ] **Step 4: Implement OnUserUnflaggedAsExpert**

```php
<?php
// public/packages/inno_notification/src/Application/EventHandler/OnUserUnflaggedAsExpert.php
declare(strict_types=1);
namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\UserUnflaggedAsExpert;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnUserUnflaggedAsExpert
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(UserUnflaggedAsExpert $event): void
    {
        $email = $this->userResolver->resolveEmail($event->getUserId());
        if ($email === null) {
            $this->logger->warning('Cannot resolve email for expert unflag notification', ['userId' => $event->getUserId()]);
            return;
        }

        $heading = 'Dein Expert·in-Status wurde entfernt';
        $this->emailService->send(
            to: $email,
            subject: $heading,
            body: EmailTemplate::wrap($heading, '<p>Dein Expert·in-Status auf der Innovationsbox wurde entfernt.</p>'),
        );

        $this->logger->info('User notified about expert unflag', ['userId' => $event->getUserId()]);
    }
}
```

- [ ] **Step 5: Register 3 listeners in notification controller**

In `public/packages/inno_notification/controller.php`, add:

```php
use Concrete\Package\InnoNotification\Application\EventHandler\OnUserRegistered;
use Concrete\Package\InnoNotification\Application\EventHandler\OnUserFlaggedAsExpert;
use Concrete\Package\InnoNotification\Application\EventHandler\OnUserUnflaggedAsExpert;

// In on_start():
Events::addListener('on_user_add', [$this->app->make(OnUserRegistered::class), 'handle']);
Events::addListener('innovationsbox.user.flagged_as_expert', [$this->app->make(OnUserFlaggedAsExpert::class), 'handle']);
Events::addListener('innovationsbox.user.unflagged_as_expert', [$this->app->make(OnUserUnflaggedAsExpert::class), 'handle']);
```

- [ ] **Step 6: Run tests**

Run: `./vendor/bin/pest --testsuite=Unit`

- [ ] **Step 7: Commit**

```bash
git commit -m "feat(notification): add user registration + expert flag email handlers"
```

---

### Task 3: Frontend — Single Page + TS + CSS + Registration

**Files:**
- Create: `public/packages/inno_frontend/single_pages/moderation/benutzer.php`
- Create: `resources/js/single_pages/moderation-benutzer.ts`
- Create: `resources/css/single_pages/moderation-benutzer.scss`
- Modify: `public/packages/inno_frontend/single_pages/moderation/view.php`
- Modify: `public/packages/inno_frontend/controller.php`

- [ ] **Step 1: Create the PHP page template**

```php
<?php
// public/packages/inno_frontend/single_pages/moderation/benutzer.php
declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');
?>

<div class="moderation-benutzer-page">
    <a href="/moderation" class="moderation-benutzer-page__back"><?= t('← Zurück zur Moderation') ?></a>

    <header class="moderation-benutzer-page__header">
        <h1><?= t('Benutzer verwalten') ?></h1>
    </header>

    <div id="users-container"></div>
</div>
```

- [ ] **Step 2: Create the TypeScript**

```typescript
// resources/js/single_pages/moderation-benutzer.ts
import { apiFetch } from '../api-client';
import { escapeHtml } from '../utils/escape-html';

export {};

const API_BASE = '/api/moderation/user';

interface UserEntry {
  id: string;
  name: string;
  email: string;
  isExpert: boolean;
}

async function fetchUsers(): Promise<UserEntry[]> {
  const res = await fetch(API_BASE);
  const json = (await res.json()) as { data: UserEntry[] };
  return json.data;
}

function renderUsers(users: UserEntry[]): void {
  const container = document.getElementById('users-container');
  if (!container) return;

  if (users.length === 0) {
    container.innerHTML = '<p class="moderation-benutzer-page__empty">Keine Benutzer vorhanden.</p>';
    return;
  }

  container.innerHTML = `
    <div class="user-list">
      <div class="user-list__header">
        <span class="user-list__col user-list__col--name">Name</span>
        <span class="user-list__col user-list__col--email">E-Mail</span>
        <span class="user-list__col user-list__col--expert">Expert·in</span>
      </div>
      ${users
        .map(
          (user) => `
        <div class="user-list__item" data-id="${escapeHtml(user.id)}">
          <span class="user-list__col user-list__col--name">${escapeHtml(user.name)}</span>
          <span class="user-list__col user-list__col--email">${escapeHtml(user.email)}</span>
          <span class="user-list__col user-list__col--expert">
            <button type="button" class="btn btn--sm ${user.isExpert ? 'btn--primary' : 'btn--secondary'}" data-toggle-expert="${escapeHtml(user.id)}" data-is-expert="${user.isExpert ? 'true' : 'false'}">
              ${user.isExpert ? 'Expert·in ✓' : 'Markieren'}
            </button>
          </span>
        </div>`,
        )
        .join('')}
    </div>`;

  // Attach toggle handlers
  container.querySelectorAll('[data-toggle-expert]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const userId = (btn as HTMLElement).dataset.toggleExpert ?? '';
      const currentlyExpert = (btn as HTMLElement).dataset.isExpert === 'true';

      const res = await apiFetch(`${API_BASE}/${userId}/expert`, {
        method: 'PUT',
        body: JSON.stringify({ expert: !currentlyExpert }),
      });

      if (res.ok) {
        window.location.reload();
      }
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  void fetchUsers().then(renderUsers);
});
```

- [ ] **Step 3: Create the SCSS**

```scss
// resources/css/single_pages/moderation-benutzer.scss
.moderation-benutzer-page {
  max-width: 900px;
  margin: 0 auto;
  padding: var(--space-6) var(--space-4);

  &__back {
    display: inline-block;
    margin-bottom: var(--space-4);
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-5);
  }

  &__empty {
    color: var(--color-text-muted);
    font-size: 0.9375rem;
  }
}

.user-list {
  &__header {
    display: flex;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-4);
    font-size: 0.75rem;
    font-weight: var(--font-weight-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-text-muted);
    border-bottom: 1px solid var(--color-border-default);
  }

  &__item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-4);
    border-bottom: 1px solid var(--color-border-subtle, var(--color-neutral-100));
  }

  &__col {
    &--name { flex: 2; }
    &--email { flex: 2; }
    &--expert { flex: 1; display: flex; justify-content: flex-end; }
  }
}
```

- [ ] **Step 4: Add nav link to moderation view**

In `public/packages/inno_frontend/single_pages/moderation/view.php`, add a second link in the nav:

```php
<nav class="moderation-page__nav" style="margin-bottom: var(--space-4);">
    <a href="/moderation/kategorien" class="btn btn--secondary btn--sm"><?= t('Kategorien verwalten') ?></a>
    <a href="/moderation/benutzer" class="btn btn--secondary btn--sm"><?= t('Benutzer verwalten') ?></a>
</nav>
```

- [ ] **Step 5: Register single page + assets in inno_frontend controller**

In `public/packages/inno_frontend/controller.php`:

Add to `on_start()` after the moderation-kategorien asset group:
```php
// Single page assets — moderation-benutzer
$al->register('css', 'inno_frontend.moderation-benutzer', 'themes/innovationsbox/css/single_pages/moderation-benutzer.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
$al->register('javascript', 'inno_frontend.moderation-benutzer', 'themes/innovationsbox/js/single_pages/moderation-benutzer.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
$al->registerGroup('inno_frontend/moderation-benutzer', [
    ['javascript', 'inno_frontend.manifest'],
    ['javascript', 'inno_frontend.vendor'],
    ['javascript', 'inno_frontend.main'],
    ['css', 'inno_frontend.moderation-benutzer'],
    ['javascript', 'inno_frontend.moderation-benutzer'],
]);
```

Add to `installSinglePages()`:
```php
'/moderation/benutzer' => ['name' => 'Benutzer verwalten', 'excludeNav' => true],
```

- [ ] **Step 6: Build and verify**

Run: `npm run typecheck && npm run production`

- [ ] **Step 7: Commit**

```bash
git commit -m "feat(ui): add user management page with expert toggle"
```

---

### Task 4: E2E Tests

**Files:**
- Create: `tests/e2e/specs/user-management.spec.ts`

- [ ] **Step 1: Write E2E test**

```typescript
// tests/e2e/specs/user-management.spec.ts
import { test, expect } from '@playwright/test';
import { type AuthedApi, loginAs } from '../fixtures/helpers';

const API = '/api/moderation/user';

test.describe('User Management', () => {
  let api: AuthedApi;

  test.beforeAll(async ({ browser }) => {
    const ctx = await browser.newContext({ extraHTTPHeaders: {} });
    const page = await ctx.newPage();
    api = await loginAs(page, 'admin@lemonbrain.ch', '123456');
  });

  test('list users', async () => {
    const res = await api.get(API);
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(Array.isArray(body.data)).toBe(true);
    expect(body.data.length).toBeGreaterThan(0);
    expect(body.data[0]).toHaveProperty('isExpert');
  });

  test('toggle expert flag on', async () => {
    // Find a non-expert user
    const listRes = await api.get(API);
    const listBody = await listRes.json();
    const nonExpert = (listBody.data as Array<{ id: string; isExpert: boolean }>)
      .find((u) => !u.isExpert);
    test.skip(!nonExpert, 'All users are experts');

    const res = await api.put(`${API}/${nonExpert!.id}/expert`, {
      data: { expert: true },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.isExpert).toBe(true);

    // Revert
    await api.put(`${API}/${nonExpert!.id}/expert`, { data: { expert: false } });
  });

  test('toggle expert flag off', async () => {
    const listRes = await api.get(API);
    const listBody = await listRes.json();
    const expert = (listBody.data as Array<{ id: string; isExpert: boolean }>)
      .find((u) => u.isExpert);
    test.skip(!expert, 'No experts to unflag');

    const res = await api.put(`${API}/${expert!.id}/expert`, {
      data: { expert: false },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.isExpert).toBe(false);

    // Revert
    await api.put(`${API}/${expert!.id}/expert`, { data: { expert: true } });
  });
});
```

- [ ] **Step 2: Verify TypeScript**

Run: `npm run typecheck`

- [ ] **Step 3: Commit**

```bash
git commit -m "test(e2e): add user management + expert toggle spec"
```
