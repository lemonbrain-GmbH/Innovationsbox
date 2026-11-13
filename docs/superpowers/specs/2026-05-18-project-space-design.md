# Project Space Design Spec

**Date:** 2026-05-18
**Status:** Approved
**Scope:** Design and implement the `inno_project_space` package (backend) and project space UI in `inno_frontend` (frontend) — covering the full lifecycle from auto-creation through collaboration to completion.

---

## Table of Contents

1. [Overview](#1-overview)
2. [User Roles & Permissions](#2-user-roles--permissions)
3. [Domain Model](#3-domain-model)
4. [Use Cases & Ports](#4-use-cases--ports)
5. [Events](#5-events)
6. [REST API](#6-rest-api)
7. [Database Schema](#7-database-schema)
8. [Frontend — Pages & Components](#8-frontend--pages--components)
9. [Testing Strategy](#9-testing-strategy)

---

## 1. Overview

Project Spaces are the collaboration layer of the Innovationsbox. When a moderator approves a project idea, a space is auto-created (via the existing `ProjectIdeaApproved` event). The idea submitter becomes the Initiator(in), who then manages the space: adds Collaboard links for whiteboard collaboration, writes status updates (journal entries), and handles membership requests.

**Two public-facing views:**

- **Public project overview** (`/projektraeume`) — lists all Active and Completed project spaces as cards with status, stats, latest update, and a "Mitmachen" button for requesting to join.
- **Project space detail** (`/projektraum/{slug}`) — single page with collaboard links (rendered in an inline iframe), status update timeline, and a team sidebar.

**Key decisions made during brainstorming:**

- Collaboard links are specifically for collaboard.app (not generic external tools)
- Status updates are blog-like: title + WYSIWYG rich text body
- Membership: invite-only (Initiator invites) + request-to-join (Initiator approves/rejects)
- Lifecycle: Draft → Active → Completed → Archived
- Single aggregate root approach — `ProjectSpace` contains collaboard links, status updates, and membership requests as child entities/value objects
- Inline iframe for collaboard embedding with "open in new tab" fallback

---

## 2. User Roles & Permissions

| Role                                 | Can see                                                          | Can do                                                                                                                                       |
| ------------------------------------ | ---------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| **Public (anonymous)**               | Overview page (Active + Completed cards with public info)        | Nothing                                                                                                                                      |
| **Authenticated user**               | Same as public                                                   | Request to join a space ("Mitmachen")                                                                                                        |
| **Entwickler(in)** (accepted member) | Full space detail: collaboard links, status updates, member list | Use collaboard iframes                                                                                                                       |
| **Initiator(in)** (idea submitter)   | Everything                                                       | Add/remove collaboard links, write status updates, invite members, accept/reject join requests, change space status (Draft→Active→Completed) |
| **Admin**                            | Everything across all spaces                                     | All Initiator actions + archive spaces                                                                                                       |

**Permission keys** (registered in Concrete CMS):

- `space.view` — view space detail (members + admin)
- `space.manage` — manage collaboard links, write updates, handle requests (initiator + admin)
- `space.join` — request to join (any authenticated user)
- `space.admin` — archive, delete, override (admin only)

---

## 3. Domain Model

### 3.1 Aggregate Root: ProjectSpace

```
ProjectSpace
├── id: string                              -- e.g. 'space_6627a1d5e6f73'
├── projectIdeaId: string                   -- link to approved idea
├── title: string
├── description: string                     -- copied from idea on creation
├── status: SpaceStatus                     -- enum
├── initiatorId: string                     -- CMS user ID
├── memberIds: string[]                     -- accepted member user IDs
├── collaboardLinks: CollaboardLink[]
├── statusUpdates: StatusUpdate[]
├── membershipRequests: MembershipRequest[]
├── createdAt: DateTimeImmutable
└── updatedAt: DateTimeImmutable
```

### 3.2 Value Object: CollaboardLink

```
CollaboardLink
├── id: string
├── title: string                           -- e.g. "Brainstorming Board"
├── url: string                             -- must be collaboard.app domain
└── addedAt: DateTimeImmutable
```

### 3.3 Value Object: StatusUpdate

```
StatusUpdate
├── id: string
├── title: string
├── body: string                            -- sanitized HTML from WYSIWYG
├── authorId: string
└── createdAt: DateTimeImmutable
```

### 3.4 Value Object: MembershipRequest

```
MembershipRequest
├── id: string
├── userId: string
├── status: RequestStatus                   -- enum
├── requestedAt: DateTimeImmutable
└── decidedAt: ?DateTimeImmutable
```

### 3.5 Enums

```
SpaceStatus: Draft | Active | Completed | Archived

RequestStatus: Pending | Accepted | Rejected
```

### 3.6 Domain Rules

- Only Initiator(in) can add/remove collaboard links
- Only Initiator(in) can write status updates
- Only Initiator(in) can accept/reject membership requests
- Collaboard URL must match `web.collaboard.app` domain
- Status transitions: Draft→Active, Active→Completed, Completed→Archived (Admin only for archive)
- When a membership request is accepted, the user is added to `memberIds`
- A user cannot request to join a space they are already a member of
- A user cannot request to join a Draft or Archived space

---

## 4. Use Cases & Ports

Following the established pattern: each use case gets a readonly Command DTO, a Port/In interface, and a Handler that implements it.

### 4.1 Use Case Table

| Use Case               | Command Fields                 | Who                   | Description                        |
| ---------------------- | ------------------------------ | --------------------- | ---------------------------------- |
| `AddCollaboardLink`    | `{spaceId, title, url}`        | Initiator(in)         | Add a collaboard link to the space |
| `RemoveCollaboardLink` | `{spaceId, linkId}`            | Initiator(in)         | Remove a collaboard link           |
| `WriteStatusUpdate`    | `{spaceId, title, body}`       | Initiator(in)         | Publish a journal entry            |
| `RequestToJoin`        | `{spaceId}`                    | Any authenticated     | Submit a join request              |
| `HandleJoinRequest`    | `{spaceId, requestId, accept}` | Initiator(in)         | Accept or reject a pending request |
| `ChangeSpaceStatus`    | `{spaceId, newStatus}`         | Initiator(in) / Admin | Transition the space status        |
| `InviteMember`         | `{spaceId, userId}`            | Initiator(in)         | Directly invite a user             |

### 4.2 Port/Out — Repository

```php
interface ProjectSpaceRepositoryInterface
{
    public function findById(string $id): ?ProjectSpace;
    public function findByProjectIdeaId(string $ideaId): ?ProjectSpace;
    public function findByMemberId(string $userId): array;
    public function findPublic(?SpaceStatus $status, int $page, int $limit): PaginatedResult;
    public function save(ProjectSpace $space): void;
}
```

### 4.3 File Structure

```
inno_project_space/src/
  Domain/
    Model/
      ProjectSpace.php              ← aggregate root (extended)
      CollaboardLink.php            ← value object
      StatusUpdate.php              ← value object
      MembershipRequest.php         ← value object
      SpaceStatus.php               ← enum
      RequestStatus.php             ← enum
    Exception/
      ProjectSpaceNotFoundException.php  ← existing
      InvalidCollaboardUrlException.php  ← new
      InvalidStatusTransitionException.php ← new
  Application/
    UseCase/
      AddCollaboardLink/
        AddCollaboardLinkCommand.php
        AddCollaboardLinkHandler.php
      RemoveCollaboardLink/
        RemoveCollaboardLinkCommand.php
        RemoveCollaboardLinkHandler.php
      WriteStatusUpdate/
        WriteStatusUpdateCommand.php
        WriteStatusUpdateHandler.php
      RequestToJoin/
        RequestToJoinCommand.php
        RequestToJoinHandler.php
      HandleJoinRequest/
        HandleJoinRequestCommand.php
        HandleJoinRequestHandler.php
      ChangeSpaceStatus/
        ChangeSpaceStatusCommand.php
        ChangeSpaceStatusHandler.php
      InviteMember/
        InviteMemberCommand.php
        InviteMemberHandler.php
    EventHandler/
      OnProjectIdeaApproved.php     ← existing (updated)
  Port/
    In/
      AddCollaboardLinkPort.php
      RemoveCollaboardLinkPort.php
      WriteStatusUpdatePort.php
      RequestToJoinPort.php
      HandleJoinRequestPort.php
      ChangeSpaceStatusPort.php
      InviteMemberPort.php
    Out/
      ProjectSpaceRepositoryInterface.php  ← existing (extended)
  Adapter/
    In/
      Http/
        ProjectSpaceApiController.php      ← existing (extended)
    Out/
      Persistence/
        ConcreteProjectSpaceRepository.php ← existing (implemented)
```

---

## 5. Events

### 5.1 Existing Events (unchanged)

| Event                 | Source               | Consumers                            |
| --------------------- | -------------------- | ------------------------------------ |
| `ProjectIdeaApproved` | `inno_project_idea`  | `inno_project_space` (creates space) |
| `ProjectSpaceCreated` | `inno_project_space` | `inno_notification`                  |

### 5.2 New Events (in `inno_shared`)

| Event                   | Fired When                  | Properties                       | Consumer                                 |
| ----------------------- | --------------------------- | -------------------------------- | ---------------------------------------- |
| `StatusUpdatePublished` | Initiator writes an update  | `{spaceId, updateId, authorId}`  | `inno_notification` (notify members)     |
| `JoinRequestReceived`   | User requests to join       | `{spaceId, requestId, userId}`   | `inno_notification` (notify Initiator)   |
| `JoinRequestAccepted`   | Initiator accepts request   | `{spaceId, requestId, userId}`   | `inno_notification` (notify requester)   |
| `JoinRequestRejected`   | Initiator rejects request   | `{spaceId, requestId, userId}`   | `inno_notification` (notify requester)   |
| `ProjectSpaceActivated` | Status changed to Active    | `{spaceId, initiatorId}`         | `inno_notification` (notify Initiator)   |
| `ProjectSpaceCompleted` | Status changed to Completed | `{spaceId, initiatorId}`         | `inno_notification` (notify all members) |
| `MemberInvited`         | Initiator invites a user    | `{spaceId, userId, initiatorId}` | `inno_notification` (notify invitee)     |

All event classes live in `inno_shared/src/Domain/Event/` following the existing pattern: extend `Symfony\Contracts\EventDispatcher\Event`, define a `NAME` constant, immutable with getters.

---

## 6. REST API

All endpoints under `/api/project-space`. OpenAPI documented via PHP 8 attributes.

### 6.1 Public Endpoints

| Method | Path                      | Description                                                                                              |
| ------ | ------------------------- | -------------------------------------------------------------------------------------------------------- |
| `GET`  | `/api/project-space`      | List public spaces (Active + Completed), paginated. Query params: `status`, `page`, `limit` (default 12) |
| `GET`  | `/api/project-space/{id}` | Space detail — response shaped by role (see 6.3)                                                         |

### 6.2 Authenticated Endpoints

| Method   | Path                                               | Who                   | Description                                                        |
| -------- | -------------------------------------------------- | --------------------- | ------------------------------------------------------------------ |
| `POST`   | `/api/project-space/{id}/collaboard`               | Initiator(in)         | Add collaboard link. Body: `{title, url}`                          |
| `DELETE` | `/api/project-space/{id}/collaboard/{linkId}`      | Initiator(in)         | Remove collaboard link                                             |
| `POST`   | `/api/project-space/{id}/status-update`            | Initiator(in)         | Write status update. Body: `{title, body}`                         |
| `POST`   | `/api/project-space/{id}/join-request`             | Any authenticated     | Request to join                                                    |
| `PUT`    | `/api/project-space/{id}/join-request/{requestId}` | Initiator(in)         | Accept/reject. Body: `{accept: bool}`                              |
| `PUT`    | `/api/project-space/{id}/status`                   | Initiator(in) / Admin | Change status. Body: `{status: "active"\|"completed"\|"archived"}` |
| `POST`   | `/api/project-space/{id}/invite`                   | Initiator(in)         | Invite member. Body: `{userId}`                                    |

### 6.3 Response Shaping by Role

- **Public / non-member:** `{id, title, description, status, age, initiator: {name, avatar}, memberCount, latestUpdate: {title, createdAt}}`
- **Member (Entwickler):** Above + `{collaboardLinks[], statusUpdates[], members[]}`
- **Initiator(in):** Above + `{membershipRequests[]}`
- **Admin:** Full access, same as Initiator view

### 6.4 Sanitization

The `body` field of status updates (WYSIWYG HTML) is sanitized server-side before storage. Only safe HTML tags are allowed (headings, paragraphs, lists, links, bold/italic). Script tags and event handlers are stripped.

---

## 7. Database Schema

Three tables following the `ib_` prefix convention. Created in the package's `install()` hook.

### 7.1 ib_project_spaces

| Column            | Type                                            | Constraints               |
| ----------------- | ----------------------------------------------- | ------------------------- |
| `id`              | `VARCHAR(64)`                                   | PRIMARY KEY               |
| `project_idea_id` | `VARCHAR(64)`                                   | NOT NULL, UNIQUE          |
| `title`           | `VARCHAR(255)`                                  | NOT NULL                  |
| `description`     | `TEXT`                                          | NOT NULL                  |
| `status`          | `ENUM('draft','active','completed','archived')` | NOT NULL, DEFAULT 'draft' |
| `initiator_id`    | `INT UNSIGNED`                                  | NOT NULL                  |
| `created_at`      | `DATETIME`                                      | NOT NULL                  |
| `updated_at`      | `DATETIME`                                      | NOT NULL                  |

Indexes: `idx_status(status)`, `idx_initiator(initiator_id)`, `UNIQUE idx_idea(project_idea_id)`

### 7.2 ib_project_space_collaboards

| Column     | Type            | Constraints                                        |
| ---------- | --------------- | -------------------------------------------------- |
| `id`       | `VARCHAR(64)`   | PRIMARY KEY                                        |
| `space_id` | `VARCHAR(64)`   | NOT NULL, FK → ib_project_spaces ON DELETE CASCADE |
| `title`    | `VARCHAR(255)`  | NOT NULL                                           |
| `url`      | `VARCHAR(2048)` | NOT NULL                                           |
| `added_at` | `DATETIME`      | NOT NULL                                           |

Indexes: `idx_space(space_id)`

### 7.3 ib_project_space_updates

| Column       | Type           | Constraints                                        |
| ------------ | -------------- | -------------------------------------------------- |
| `id`         | `VARCHAR(64)`  | PRIMARY KEY                                        |
| `space_id`   | `VARCHAR(64)`  | NOT NULL, FK → ib_project_spaces ON DELETE CASCADE |
| `title`      | `VARCHAR(255)` | NOT NULL                                           |
| `body`       | `TEXT`         | NOT NULL                                           |
| `author_id`  | `INT UNSIGNED` | NOT NULL                                           |
| `created_at` | `DATETIME`     | NOT NULL                                           |

Indexes: `idx_space_date(space_id, created_at DESC)`

### 7.4 ib_project_space_members

| Column           | Type                                    | Constraints                                        |
| ---------------- | --------------------------------------- | -------------------------------------------------- |
| `id`             | `VARCHAR(64)`                           | PRIMARY KEY                                        |
| `space_id`       | `VARCHAR(64)`                           | NOT NULL, FK → ib_project_spaces ON DELETE CASCADE |
| `user_id`        | `INT UNSIGNED`                          | NOT NULL                                           |
| `role`           | `ENUM('initiator','developer')`         | NOT NULL                                           |
| `request_status` | `ENUM('pending','accepted','rejected')` | NOT NULL, DEFAULT 'accepted'                       |
| `requested_at`   | `DATETIME`                              | NOT NULL                                           |
| `decided_at`     | `DATETIME`                              | NULL                                               |

Indexes: `idx_space(space_id)`, `idx_user(user_id)`, `UNIQUE idx_space_user(space_id, user_id)`

**Design notes:**

- Members and requests share one table — `request_status` distinguishes pending from accepted
- Initiator stored both as `initiator_id` on the space (quick lookup) and as `role=initiator` row in members (consistent querying)
- `user_id` is `INT UNSIGNED` matching Concrete CMS user IDs
- Cascade deletes clean up all child data when a space is removed

---

## 8. Frontend — Pages & Components

### 8.1 New Single Pages

| Page                 | URL                 | File                                              |
| -------------------- | ------------------- | ------------------------------------------------- |
| Project Overview     | `/projektraeume`    | `inno_frontend/single_pages/projektraeume.php`    |
| Project Space Detail | `/projektraum/{id}` | `inno_frontend/single_pages/projektraum/view.php` |

The detail page uses the space `id` in the URL (e.g. `/projektraum/space_6627a1d5e6f73`). Navigation menu items appear based on role/permissions via the standard CMS navigation.

### 8.2 New Components

| Level    | Component                | Purpose                                                                                                             |
| -------- | ------------------------ | ------------------------------------------------------------------------------------------------------------------- |
| Molecule | `project-card`           | Public overview card — status badge, title, description, latest update, stats, Initiator avatar, "Mitmachen" button |
| Molecule | `collaboard-item`        | Clickable collaboard link row with icon, title, URL                                                                 |
| Molecule | `status-update-entry`    | Timeline entry — date, author, title, body                                                                          |
| Molecule | `join-request-card`      | Pending request with avatar, name, accept/reject buttons                                                            |
| Organism | `collaboard-panel`       | Collaboard list + inline iframe with toolbar (open in new tab, close)                                               |
| Organism | `status-update-timeline` | Timeline of status updates with "Write update" trigger                                                              |
| Organism | `project-space-sidebar`  | Team list (Initiator + Entwickler), pending requests (Initiator only), stats                                        |

Each new component follows the established pattern: `<name>.php` (partial), `<name>.variants.php` (props/defaults), `<name>.stories.json` (Storybook), `<name>.scss` (styles).

### 8.3 Reused Existing Components

- `card` molecule — base for `project-card`
- `member-card` molecule — team sidebar
- `badge` atom — status labels
- `button` atom — all actions
- `modal` molecule — write update form, add collaboard form, join confirmation
- `pagination` molecule — overview page
- `tabs` molecule — filter on overview (Alle / Aktiv / Abgeschlossen)
- `avatar` atom — user avatars
- `empty-state` molecule — no boards or updates yet

### 8.4 TypeScript & SCSS

- `resources/js/single_pages/projektraeume.ts` — overview page: fetch spaces, filter, paginate
- `resources/js/single_pages/projektraum.ts` — detail page: fetch space, manage collaboards, iframe handling, CRUD
- `resources/css/single_pages/projektraeume.scss`
- `resources/css/single_pages/projektraum.scss`
- Component-level SCSS per new component in the component library

### 8.5 Public Overview Page Layout

- Page header with title and subtitle
- Filter tabs: Alle / Aktiv / Abgeschlossen
- Responsive card grid (2 columns desktop, 1 column mobile)
- Each card shows: status badge, age, title, description snippet, latest status update title, Entwickler/supporter count, Initiator avatar + name, "Mitmachen" button (active projects only)
- Completed projects visually muted, no join button
- Pagination at bottom

### 8.6 Project Space Detail Page Layout

- **Header area:** Status badge, title, description, age, action buttons (Initiator only: "Status andern", "+ Update schreiben")
- **Main content (left):**
  - Collaboards section: list of clickable link items; clicking opens inline iframe below with toolbar (board title, "In neuem Tab offnen", "Schliessen"). "Board hinzufugen" button (Initiator only).
  - Status updates timeline: chronological journal entries with date, author, title, body. Latest first.
- **Sidebar (right):**
  - Team section: Initiator(in) highlighted, Entwickler list
  - Pending join requests with accept/reject (Initiator only)
  - Quick stats: created date, age, update count, collaboard count

### 8.7 Iframe Handling

Collaboard links are embedded via inline `<iframe>` within the collaboard panel. If Collaboard blocks iframe embedding (X-Frame-Options), fall back to opening the URL in a new browser tab. The iframe toolbar always includes an "open in new tab" link as an alternative.

---

## 9. Testing Strategy

### 9.1 Unit Tests (`tests/unit/`)

Mock `ProjectSpaceRepositoryInterface` via Mockery. Test:

- All 7 use case handlers (command → expected repository calls, event dispatches, permission checks)
- Domain model: status transitions, collaboard URL validation, membership request lifecycle, domain rule enforcement
- Value objects: CollaboardLink, StatusUpdate, MembershipRequest construction and validation

### 9.2 Integration Tests (`tests/integration/`)

Real database, CMS bootstrapped. Test:

- `ConcreteProjectSpaceRepository`: all CRUD operations, pagination, queries by member/status
- API controller: full request/response cycle per endpoint, permission enforcement, response shaping by role

### 9.3 E2E Tests (`tests/e2e/`)

Playwright against running CMS. Test:

- Public overview page loads, filters work, pagination works
- "Mitmachen" flow: request to join → Initiator sees request → accept → requester becomes member
- Collaboard management: add link → appears in list → click opens iframe → remove link
- Status update: write update → appears in timeline → appears on public overview card
- Status transitions: Draft → Active (space becomes public) → Completed

### 9.4 Coverage Target

80% line coverage on `inno_project_space/src/` (matching the project-wide CI threshold).
