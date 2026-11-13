# Leave / Remove Member — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow members to leave project spaces and admins/moderators to remove members, with initiator protection, confirmation dialogs, events, and notification emails.

**Architecture:** Two new use cases (`LeaveSpace`, `RemoveMember`) following the existing hexagonal pattern. Domain-level `removeMember()` on `ProjectSpace` with initiator guard. Two new events dispatched for notification. Frontend adds leave/remove buttons with `confirm()` dialogs to the sidebar member list.

**Tech Stack:** PHP 8.3 (Pest, Mockery), TypeScript (Playwright), Concrete CMS 9.5+

---

## File Structure

| File | Responsibility |
|------|---------------|
| `inno_shared/src/Domain/Event/MemberLeft.php` | Event: member voluntarily left |
| `inno_shared/src/Domain/Event/MemberRemoved.php` | Event: member removed by admin |
| `inno_project_space/src/Domain/Model/ProjectSpace.php` | Add `removeMember()` |
| `inno_project_space/src/Application/UseCase/LeaveSpace/` | Command + Handler |
| `inno_project_space/src/Port/In/LeaveSpacePort.php` | Port interface |
| `inno_project_space/src/Application/UseCase/RemoveMember/` | Command + Handler |
| `inno_project_space/src/Port/In/RemoveMemberPort.php` | Port interface |
| `inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php` | Handle member deletion in sync |
| `inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php` | Two new endpoints |
| `inno_project_space/controller.php` | Routes + DI bindings |
| `inno_notification/src/Application/EventHandler/OnMemberLeft.php` | Email to initiator |
| `inno_notification/src/Application/EventHandler/OnMemberRemoved.php` | Email to removed member |
| `inno_notification/controller.php` | Register 2 new listeners |
| `resources/js/single_pages/projektraum.ts` | Leave/remove buttons + confirm |

---

### Task 1: Domain Model + Events

**Files:**
- Create: `public/packages/inno_shared/src/Domain/Event/MemberLeft.php`
- Create: `public/packages/inno_shared/src/Domain/Event/MemberRemoved.php`
- Modify: `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php`
- Modify: `public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php`
- Test: `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php`

- [ ] **Step 1: Write domain test for removeMember**

Read `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php` first. Add these tests:

```php
test('removeMember removes a member', function () {
    $space = createActiveSpace(); // use existing factory helper in the test file
    $space->addMember('user_42');
    expect(in_array('user_42', $space->getMemberIds(), true))->toBeTrue();

    $space->removeMember('user_42');
    expect(in_array('user_42', $space->getMemberIds(), true))->toBeFalse();
});

test('removeMember throws when removing initiator', function () {
    $space = createActiveSpace(); // initiatorId is set in factory
    $space->removeMember($space->getInitiatorId());
})->throws(\DomainException::class, 'initiator cannot be removed');

test('removeMember throws when user is not a member', function () {
    $space = createActiveSpace();
    $space->removeMember('nonexistent_user');
})->throws(\DomainException::class, 'not a member');
```

- [ ] **Step 2: Implement removeMember on ProjectSpace**

In `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php`, add after `addMember()`:

```php
public function removeMember(string $userId): void
{
    if ($userId === $this->initiatorId) {
        throw new \DomainException('The initiator cannot be removed from the space.');
    }
    $index = array_search($userId, $this->memberIds, true);
    if ($index === false) {
        throw new \DomainException("User '{$userId}' is not a member of this space.");
    }
    array_splice($this->memberIds, $index, 1);
    $this->updatedAt = new \DateTimeImmutable();
}
```

- [ ] **Step 3: Fix syncMembers to handle deletions**

In `ConcreteProjectSpaceRepository::syncMembers()`, the current implementation only does `INSERT IGNORE` — it never deletes removed members. Add deletion logic at the start of `syncMembers()`:

```php
private function syncMembers(ProjectSpace $space): void
{
    // Delete members no longer in the domain model
    $existingUserIds = $this->db->fetchFirstColumn(
        'SELECT user_id FROM ib_project_space_members WHERE space_id = ? AND request_status = ?',
        [$space->getId(), RequestStatus::Accepted->value],
    );
    $currentUserIds = array_map(fn(string $id) => (int) $id, $space->getMemberIds());
    foreach ($existingUserIds as $existingUserId) {
        if (!in_array((int) $existingUserId, $currentUserIds, true)) {
            $this->db->executeStatement(
                'DELETE FROM ib_project_space_members WHERE space_id = ? AND user_id = ?',
                [$space->getId(), $existingUserId],
            );
        }
    }

    // Insert initiator + new members (existing INSERT IGNORE logic unchanged)
    $this->db->executeStatement(
        'INSERT IGNORE INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at) VALUES (?, ?, ?, ?, ?, ?)',
        [uniqid('mem_', true), $space->getId(), (int) $space->getInitiatorId(), 'initiator', RequestStatus::Accepted->value, $space->getCreatedAt()->format('Y-m-d H:i:s')],
    );

    foreach ($space->getMemberIds() as $userId) {
        if ($userId === $space->getInitiatorId()) {
            continue;
        }
        $this->db->executeStatement(
            'INSERT IGNORE INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at) VALUES (?, ?, ?, ?, ?, ?)',
            [uniqid('mem_', true), $space->getId(), (int) $userId, 'developer', RequestStatus::Accepted->value, (new \DateTimeImmutable())->format('Y-m-d H:i:s')],
        );
    }
}
```

- [ ] **Step 4: Create MemberLeft event**

```php
<?php
// public/packages/inno_shared/src/Domain/Event/MemberLeft.php
declare(strict_types=1);
namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MemberLeft extends Event
{
    public const NAME = 'innovationsbox.project_space.member_left';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $userId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getUserId(): string { return $this->userId; }
}
```

- [ ] **Step 5: Create MemberRemoved event**

```php
<?php
// public/packages/inno_shared/src/Domain/Event/MemberRemoved.php
declare(strict_types=1);
namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MemberRemoved extends Event
{
    public const NAME = 'innovationsbox.project_space.member_removed';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $userId,
        private readonly string $removedByUserId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getUserId(): string { return $this->userId; }
    public function getRemovedByUserId(): string { return $this->removedByUserId; }
}
```

- [ ] **Step 6: Run tests**

Run: `./vendor/bin/pest --testsuite=Unit`

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_shared/src/Domain/Event/MemberLeft.php \
        public/packages/inno_shared/src/Domain/Event/MemberRemoved.php \
        public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php \
        public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php \
        tests/unit/inno_project_space/Domain/ProjectSpaceTest.php
git commit -m "feat(space): add removeMember domain method + MemberLeft/MemberRemoved events"
```

---

### Task 2: Use Cases + Ports + API Endpoints + Routes

**Files:**
- Create: `public/packages/inno_project_space/src/Port/In/LeaveSpacePort.php`
- Create: `public/packages/inno_project_space/src/Application/UseCase/LeaveSpace/LeaveSpaceCommand.php`
- Create: `public/packages/inno_project_space/src/Application/UseCase/LeaveSpace/LeaveSpaceHandler.php`
- Create: `public/packages/inno_project_space/src/Port/In/RemoveMemberPort.php`
- Create: `public/packages/inno_project_space/src/Application/UseCase/RemoveMember/RemoveMemberCommand.php`
- Create: `public/packages/inno_project_space/src/Application/UseCase/RemoveMember/RemoveMemberHandler.php`
- Modify: `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`
- Modify: `public/packages/inno_project_space/controller.php`
- Test: `tests/unit/inno_project_space/Application/LeaveSpaceHandlerTest.php`
- Test: `tests/unit/inno_project_space/Application/RemoveMemberHandlerTest.php`

- [ ] **Step 1: Write tests for both handlers**

Follow the `HandleJoinRequestHandler` pattern (constructor takes: repository, auth, eventDispatcher, transaction, logger).

`LeaveSpaceHandlerTest.php`:
- Happy path: authenticated member leaves, `removeMember` called, `MemberLeft` event dispatched, repository saved
- Initiator guard: throws `DomainException` when initiator tries to leave
- Not a member: throws when non-member tries to leave

`RemoveMemberHandlerTest.php`:
- Happy path: admin removes member, `removeMember` called, `MemberRemoved` event dispatched
- Initiator guard: throws when trying to remove initiator
- Permission guard: throws `AuthException` when non-admin tries to remove

- [ ] **Step 2: Create LeaveSpace port + command + handler**

`LeaveSpacePort.php`:
```php
interface LeaveSpacePort
{
    public function handle(LeaveSpaceCommand $command): void;
}
```

`LeaveSpaceCommand.php`:
```php
class LeaveSpaceCommand
{
    public function __construct(public readonly string $spaceId) {}
}
```

`LeaveSpaceHandler.php` — follows `HandleJoinRequestHandler` pattern:
1. Check authenticated
2. In transaction: find space, get userId from auth, call `$space->removeMember($userId)`, dispatch `MemberLeft`, save
3. Log

- [ ] **Step 3: Create RemoveMember port + command + handler**

`RemoveMemberPort.php`:
```php
interface RemoveMemberPort
{
    public function handle(RemoveMemberCommand $command): void;
}
```

`RemoveMemberCommand.php`:
```php
class RemoveMemberCommand
{
    public function __construct(
        public readonly string $spaceId,
        public readonly string $userId,
    ) {}
}
```

`RemoveMemberHandler.php`:
1. Check `space.admin` permission
2. In transaction: find space, call `$space->removeMember($command->userId)`, dispatch `MemberRemoved` with removedByUserId from auth, save
3. Log

- [ ] **Step 4: Add API endpoints to controller**

In `ProjectSpaceApiController`, add:

```php
public function leaveSpace(Request $request, string $id): JsonResponse
{
    try {
        $this->validateCsrfToken($request);
        $this->leaveSpacePort->handle(new LeaveSpaceCommand(spaceId: $id));
        return new JsonResponse(['status' => 'left']);
    } catch (\Throwable $e) {
        return $this->exceptionHandler->toJsonResponse($e);
    }
}

public function removeMember(Request $request, string $id, string $userId): JsonResponse
{
    try {
        $this->validateCsrfToken($request);
        $this->removeMemberPort->handle(new RemoveMemberCommand(spaceId: $id, userId: $userId));
        return new JsonResponse(['status' => 'removed']);
    } catch (\Throwable $e) {
        return $this->exceptionHandler->toJsonResponse($e);
    }
}
```

Add `LeaveSpacePort` and `RemoveMemberPort` to the constructor. Add OA attributes.

- [ ] **Step 5: Register routes + DI bindings**

In `inno_project_space/controller.php` add routes:
```php
$router->register('/api/project-space/{id}/membership', "{$ctrl}::leaveSpace", 'api.project_space.leave', [], [], '', [], ['DELETE']);
$router->register('/api/project-space/{id}/member/{userId}', "{$ctrl}::removeMember", 'api.project_space.remove_member', [], [], '', [], ['DELETE']);
```

Add DI bindings:
```php
$this->app->bind(LeaveSpacePort::class, LeaveSpaceHandler::class);
$this->app->bind(RemoveMemberPort::class, RemoveMemberHandler::class);
```

- [ ] **Step 6: Run tests**

Run: `./vendor/bin/pest --testsuite=Unit`

- [ ] **Step 7: Commit**

```bash
git commit -m "feat(space): add LeaveSpace and RemoveMember use cases with API endpoints"
```

---

### Task 3: Notification Handlers

**Files:**
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnMemberLeft.php`
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnMemberRemoved.php`
- Modify: `public/packages/inno_notification/controller.php`
- Test: `tests/unit/inno_notification/Application/OnMemberLeftTest.php`
- Test: `tests/unit/inno_notification/Application/OnMemberRemovedTest.php`

- [ ] **Step 1: Write tests for both handlers**

Follow existing notification handler test pattern (Pest + Mockery).

`OnMemberLeftTest.php`: mock `UserResolverPort`, `ProjectSpaceReaderPort`, `EmailServiceInterface`, `LoggerInterface`. Verify email sent to initiator with subject containing "verlassen".

`OnMemberRemovedTest.php`: same mocks. Verify email sent to removed member with subject containing "entfernt".

- [ ] **Step 2: Implement OnMemberLeft**

Constructor: `EmailServiceInterface, UserResolverPort, ProjectSpaceReaderPort, LoggerInterface`

```php
public function handle(MemberLeft $event): void
{
    $initiatorId = $this->spaceReader->getInitiatorId($event->getSpaceId());
    $email = $this->userResolver->resolveEmail($initiatorId);
    if ($email === null) { $this->logger->warning(...); return; }

    $memberName = $this->userResolver->resolveName($event->getUserId());
    $spaceName = $this->spaceReader->getSpaceName($event->getSpaceId());

    $this->emailService->send(
        to: $email,
        subject: "{$memberName} hat den Projektraum verlassen",
        body: EmailTemplate::wrap("{$memberName} hat den Projektraum verlassen", "...{$memberName}...«{$spaceName}»..."),
    );
}
```

- [ ] **Step 3: Implement OnMemberRemoved**

Same constructor. Email to removed member:

```php
public function handle(MemberRemoved $event): void
{
    $email = $this->userResolver->resolveEmail($event->getUserId());
    if ($email === null) { ... return; }

    $spaceName = $this->spaceReader->getSpaceName($event->getSpaceId());

    $this->emailService->send(
        to: $email,
        subject: "Du wurdest aus einem Projektraum entfernt",
        body: EmailTemplate::wrap("Du wurdest aus einem Projektraum entfernt", "...«{$spaceName}»..."),
    );
}
```

- [ ] **Step 4: Register listeners in notification controller**

```php
Events::addListener('innovationsbox.project_space.member_left', [$this->app->make(OnMemberLeft::class), 'handle']);
Events::addListener('innovationsbox.project_space.member_removed', [$this->app->make(OnMemberRemoved::class), 'handle']);
```

- [ ] **Step 5: Run tests**

Run: `./vendor/bin/pest --testsuite=Unit`

- [ ] **Step 6: Commit**

```bash
git commit -m "feat(notification): add MemberLeft and MemberRemoved email handlers"
```

---

### Task 4: Frontend — Leave/Remove Buttons

**Files:**
- Modify: `resources/js/single_pages/projektraum.ts`

- [ ] **Step 1: Update member row rendering in renderSidebar**

In the `membersHtml` mapping (around line 433), add a conditional action button after the member name. The `show()` API response already includes `userRole` and each member has `role` and `id`. We also need the current user's ID — it's available from the space's `initiatorId` check or we can add it.

The space detail response includes `userRole` (initiator/member/admin/visitor). We need to know the current user's ID to show "Verlassen" on their own row. Check if `show()` returns `currentUserId` — it doesn't currently. Add it to the response.

In `ProjectSpaceApiController::show()`, add to `$data`:
```php
'currentUserId' => $this->auth->isAuthenticated() ? (string) $this->auth->getUserId() : null,
```

Add `currentUserId` to the `SpaceDetail` TypeScript interface.

- [ ] **Step 2: Render buttons**

Update the member row template:

```typescript
const membersHtml = members.map((member) => {
  const isInitiator = member.role === 'initiator';
  const isSelf = member.id === space.currentUserId;
  const isAdmin = space.userRole === 'admin' || space.userRole === 'initiator';

  let actionBtn = '';
  if (!isInitiator && isSelf) {
    actionBtn = `<button class="unstyled-button project-space-sidebar__leave-btn" data-leave-space="${space.id}" title="Projektraum verlassen">Verlassen</button>`;
  } else if (!isInitiator && isAdmin) {
    actionBtn = `<button class="unstyled-button project-space-sidebar__remove-btn" data-remove-member="${member.id}" data-member-name="${escapeHtml(member.name)}" title="${escapeHtml(member.name)} entfernen">Entfernen</button>`;
  }

  return `
    <div class="project-space-sidebar__member-row">
      ${avatarHtml}
      <span class="project-space-sidebar__member-name">${escapeHtml(member.name)}${isInitiator ? ' (Initiator)' : ''}</span>
      ${actionBtn}
    </div>`;
}).join('');
```

- [ ] **Step 3: Add click handlers with confirmation**

After `renderSidebar` populates the DOM, add event listeners:

```typescript
// Leave space
container.querySelectorAll('[data-leave-space]').forEach((btn) => {
  btn.addEventListener('click', () => {
    if (confirm('Möchtest du den Projektraum wirklich verlassen?')) {
      void apiFetch(`${API_BASE}/${space.id}/membership`, { method: 'DELETE' })
        .then((res) => { if (res.ok) window.location.href = '/projektraeume'; else void handleApiError(res); });
    }
  });
});

// Remove member
container.querySelectorAll('[data-remove-member]').forEach((btn) => {
  const memberName = (btn as HTMLElement).dataset.memberName ?? '';
  const memberId = (btn as HTMLElement).dataset.removeMember ?? '';
  btn.addEventListener('click', () => {
    if (confirm(`Möchtest du ${memberName} wirklich aus dem Projektraum entfernen?`)) {
      void apiFetch(`${API_BASE}/${space.id}/member/${memberId}`, { method: 'DELETE' })
        .then((res) => { if (res.ok) window.location.reload(); else void handleApiError(res); });
    }
  });
});
```

- [ ] **Step 4: Add minimal CSS for the buttons**

In `resources/css/single_pages/projektraum.scss`, add:

```scss
.project-space-sidebar__leave-btn,
.project-space-sidebar__remove-btn {
  margin-left: auto;
  font-size: 13px;
  color: var(--color-action-danger);
  cursor: pointer;

  &:hover {
    text-decoration: underline;
  }
}

.project-space-sidebar__member-row {
  display: flex;
  align-items: center;
}
```

- [ ] **Step 5: Run typecheck + build**

Run: `npm run typecheck && npm run production`

- [ ] **Step 6: Commit**

```bash
git commit -m "feat(ui): add leave/remove member buttons with confirmation dialogs"
```

---

### Task 5: E2E Tests

**Files:**
- Create: `tests/e2e/specs/leave-remove-member.spec.ts`

- [ ] **Step 1: Write E2E test spec**

```typescript
import { test, expect } from '@playwright/test';
import { type AuthedApi, loginAs } from '../fixtures/helpers';

const SPACE_ID = 'test_space_1'; // members: 1(initiator), 3, 4
const API = `/api/project-space/${SPACE_ID}`;

test.describe.serial('Leave / Remove Member', () => {
  let adminApi: AuthedApi;

  test.beforeAll(async ({ browser }) => {
    const ctx = await browser.newContext({ extraHTTPHeaders: {} });
    const page = await ctx.newPage();
    adminApi = await loginAs(page, 'admin@lemonbrain.ch', '123456');
  });

  test('member leaves space', async ({ browser }) => {
    // Login as user 4 (Entwickler, member of space 1)
    const ctx = await browser.newContext({ extraHTTPHeaders: {} });
    const page = await ctx.newPage();
    const memberApi = await loginAs(page, 'entwickler@lemonbrain.ch', '123456');

    const res = await memberApi.delete(`${API}/membership`);
    expect(res.ok()).toBe(true);
    const body = await res.json();
    expect(body.status).toBe('left');

    // Verify member is gone
    const spaceRes = await adminApi.get(API);
    const spaceBody = await spaceRes.json();
    const memberIds = (spaceBody.data.members as Array<{ id: string }>).map(m => m.id);
    expect(memberIds).not.toContain('4');
  });

  test('admin removes member', async () => {
    // Remove user 3 (Initiator user, but developer role in this space)
    const res = await adminApi.delete(`${API}/member/3`);
    expect(res.ok()).toBe(true);
    const body = await res.json();
    expect(body.status).toBe('removed');

    // Verify member is gone
    const spaceRes = await adminApi.get(API);
    const spaceBody = await spaceRes.json();
    const memberIds = (spaceBody.data.members as Array<{ id: string }>).map(m => m.id);
    expect(memberIds).not.toContain('3');
  });

  test('cannot remove initiator', async () => {
    const res = await adminApi.delete(`${API}/member/1`);
    expect(res.ok()).toBe(false);
    expect(res.status()).toBeGreaterThanOrEqual(400);
  });
});
```

- [ ] **Step 2: Run typecheck**

Run: `npm run typecheck`

- [ ] **Step 3: Commit**

```bash
git commit -m "test(e2e): add leave/remove member spec"
```
