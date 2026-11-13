# Leave / Remove Member — Design Spec

**Date:** 2026-06-02
**Goal:** Allow members to leave a project space and admins/moderators to remove members. The initiator is protected from both actions.

---

## Backend

### Use Cases

| Use Case | Actor | Endpoint | Method |
|---|---|---|---|
| LeaveSpace | Authenticated member (not initiator) | `DELETE /api/project-space/{id}/membership` | `LeaveSpaceHandler` |
| RemoveMember | Admin or moderator (`space.admin` permission) | `DELETE /api/project-space/{id}/member/{userId}` | `RemoveMemberHandler` |

### Domain Model

Add `removeMember(string $userId): void` to `ProjectSpace`:
- Throws `DomainException` if `$userId === $this->initiatorId` ("The initiator cannot be removed")
- Removes `$userId` from `$memberIds` array
- Throws if `$userId` is not a member

### Ports

**New in `inno_project_space`:**
- `Port/In/LeaveSpacePort` — interface with `handle(LeaveSpaceCommand): void`
- `Port/In/RemoveMemberPort` — interface with `handle(RemoveMemberCommand): void`

**Commands:**
- `LeaveSpaceCommand(spaceId: string)` — userId comes from `AuthInterface`
- `RemoveMemberCommand(spaceId: string, userId: string)` — target user to remove

### Persistence

In `ConcreteProjectSpaceRepository::save()`, the member removal is already handled — it syncs `$memberIds` to the DB. The `removeMember()` domain method removes from the in-memory array, then `save()` deletes the DB row.

Verify: the repository's save method needs to handle member deletions (currently it only inserts). Add a DELETE for members no longer in `$memberIds`.

### Events (in `inno_shared`)

- `MemberLeft` — `spaceId: string, userId: string`
- `MemberRemoved` — `spaceId: string, userId: string, removedByUserId: string`

### Notification Handlers (in `inno_notification`)

- `OnMemberLeft` → email to space initiator: "{memberName} hat den Projektraum «{spaceName}» verlassen."
- `OnMemberRemoved` → email to removed member: "Du wurdest aus dem Projektraum «{spaceName}» entfernt."

### API Controller

Add two methods to `ProjectSpaceApiController`:

**`leaveSpace(Request, string $id)`:**
1. Validate CSRF
2. Check authenticated
3. Get userId from `$this->auth->getUserId()`
4. Call `LeaveSpacePort->handle(new LeaveSpaceCommand($id))`
5. Return `{ status: 'left' }`

**`removeMember(Request, string $id, string $userId)`:**
1. Validate CSRF
2. Check `space.admin` permission
3. Call `RemoveMemberPort->handle(new RemoveMemberCommand($id, $userId))`
4. Return `{ status: 'removed' }`

### Routes

```
DELETE /api/project-space/{id}/membership → leaveSpace
DELETE /api/project-space/{id}/member/{userId} → removeMember
```

---

## Frontend

### Member list in sidebar (`projektraum.ts`)

Each member row in `renderSidebar()` gets a conditional action button:

- **Member viewing their own row** (not initiator) → "Verlassen" button
- **Admin/moderator viewing any non-initiator row** → "Entfernen" button
- **Initiator row** → no button

### Confirmation

Both use native `confirm()`:
- Leave: "Möchtest du den Projektraum wirklich verlassen?"
- Remove: "Möchtest du {memberName} wirklich aus dem Projektraum entfernen?"

### API calls

- Leave: `DELETE /api/project-space/{id}/membership` → reload on success
- Remove: `DELETE /api/project-space/{id}/member/{userId}` → reload on success

### Space detail response

The `show()` endpoint must include `currentUserId` or `isAdmin` flag so the frontend knows which buttons to show. Check if this is already returned.

---

## Tests

### Unit tests
- `LeaveSpaceHandlerTest` — happy path + initiator guard + non-member guard
- `RemoveMemberHandlerTest` — happy path + initiator guard + permission guard
- `OnMemberLeftTest` — email sent to initiator
- `OnMemberRemovedTest` — email sent to removed member
- `ProjectSpace::removeMember()` domain test

### E2E tests
- Member leaves a space
- Admin removes a member

---

## Files to Create/Modify

### New in `inno_shared`
- `Domain/Event/MemberLeft.php`
- `Domain/Event/MemberRemoved.php`

### New in `inno_project_space`
- `Application/UseCase/LeaveSpace/LeaveSpaceCommand.php`
- `Application/UseCase/LeaveSpace/LeaveSpaceHandler.php`
- `Port/In/LeaveSpacePort.php`
- `Application/UseCase/RemoveMember/RemoveMemberCommand.php`
- `Application/UseCase/RemoveMember/RemoveMemberHandler.php`
- `Port/In/RemoveMemberPort.php`

### New in `inno_notification`
- `Application/EventHandler/OnMemberLeft.php`
- `Application/EventHandler/OnMemberRemoved.php`

### Modify
- `inno_project_space/src/Domain/Model/ProjectSpace.php` — add `removeMember()`
- `inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php` — add `leaveSpace()`, `removeMember()`
- `inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php` — handle member deletion in `save()`
- `inno_project_space/controller.php` — register routes + bind ports
- `inno_notification/controller.php` — register 2 new event listeners
- `resources/js/single_pages/projektraum.ts` — add leave/remove buttons + confirm dialogs

### Tests
- `tests/unit/inno_project_space/Application/LeaveSpaceHandlerTest.php`
- `tests/unit/inno_project_space/Application/RemoveMemberHandlerTest.php`
- `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php` (add removeMember tests)
- `tests/unit/inno_notification/Application/OnMemberLeftTest.php`
- `tests/unit/inno_notification/Application/OnMemberRemovedTest.php`
- `tests/e2e/specs/leave-remove-member.spec.ts`
