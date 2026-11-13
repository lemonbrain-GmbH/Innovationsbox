# Expert Request Feature — Design Spec

**Date:** 2026-05-29
**Status:** Approved
**Package:** `inno_project_space` (primary), `inno_shared` (events), `inno_frontend` (UI)

## Overview

Project space initiators can request experts from the expert pool to join their projects. Experts receive requests in a dedicated "Meine Anfragen" page and can accept or decline. Accepting adds the expert as a regular member of the project space.

## Domain Model

### ExpertRequest Value Object (new, in `inno_project_space`)

Properties:
- `id` — unique request identifier
- `expertUserId` — CMS user ID of the requested expert
- `requestedByUserId` — CMS user ID of the initiator who sent the request
- `message` — optional text message from the initiator explaining the request
- `status` — ExpertRequestStatus enum: `Pending`, `Accepted`, `Declined`
- `requestedAt` — DateTimeImmutable
- `decidedAt` — nullable DateTimeImmutable

### ExpertRequestStatus Enum (new)

Cases: `Pending = 'pending'`, `Accepted = 'accepted'`, `Declined = 'declined'`

### ProjectSpace Aggregate — Extensions

New collection `expertRequests` on ProjectSpace (same pattern as `membershipRequests`):
- `requestExpert(id, expertUserId, requestedByUserId, message)` — creates pending request. Throws if expert is already a member or already has a pending request for this space.
- `acceptExpertRequest(requestId)` — sets status to Accepted, adds expert to `memberIds`, sets `decidedAt`.
- `declineExpertRequest(requestId)` — sets status to Declined, sets `decidedAt`. Does NOT add to members.

## Database

### `ib_expert_requests` (new table)

| Column | Type | Notes |
|--------|------|-------|
| id | VARCHAR(64) PK | |
| space_id | VARCHAR(64) | FK to ib_project_spaces |
| expert_user_id | VARCHAR(64) | CMS user ID of expert |
| requested_by_user_id | VARCHAR(64) | CMS user ID of initiator |
| message | TEXT NULL | Optional message |
| status | VARCHAR(20) | pending, accepted, declined |
| requested_at | DATETIME | |
| decided_at | DATETIME NULL | |

Indices: `idx_space_id` on `space_id`, `idx_expert_user_id` on `expert_user_id`, `idx_status` on `status`.

## API Endpoints

### Project Space API — new endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/project-space/{id}/experts` | Initiator/Admin | List experts available to request (Experte group, excluding current members and pending requests) |
| `POST` | `/api/project-space/{id}/expert-request` | Initiator/Admin | Send expert request. Body: `{expertUserId: string, message?: string}` |
| `PUT` | `/api/project-space/{id}/expert-request/{requestId}` | The requested expert | Accept or decline. Body: `{accept: boolean}` |

### Expert's own requests

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/expert-request/mine` | Authenticated (Experte group) | List pending/recent requests for current user |

Response shape for `/api/expert-request/mine`:
```json
{
  "data": [{
    "id": "req_123",
    "spaceId": "space_456",
    "spaceTitle": "Solaranlage Schulhaus",
    "requestedByName": "Max Muster",
    "message": "We need solar panel expertise",
    "status": "pending",
    "requestedAt": "2026-05-29T10:00:00+00:00",
    "decidedAt": null
  }]
}
```

### Existing endpoint enrichment

`GET /api/project-space/{id}` response adds `expertRequests` array for initiator/admin:
```json
{
  "expertRequests": [{
    "id": "req_123",
    "expertUserId": "5",
    "expertName": "Dr. Solar",
    "expertExpertise": "Photovoltaik",
    "message": "We need your help",
    "status": "pending",
    "requestedAt": "2026-05-29T10:00:00+00:00",
    "decidedAt": null
  }]
}
```

### Expert list endpoint

`GET /api/project-space/{id}/experts` returns available experts:
```json
{
  "data": [{
    "userId": "5",
    "name": "Dr. Solar",
    "expertise": "Photovoltaik"
  }]
}
```

Filters out: users already members of the space, users with pending expert requests for this space.

## Use Cases

### RequestExpert (new)

- **Command:** `RequestExpertCommand(spaceId, expertUserId, message?)`
- **Handler:** Validates initiator is space owner or admin. Calls `space.requestExpert()`. Saves. Dispatches `ExpertRequested` event.
- **Port:** `RequestExpertPort`

### HandleExpertRequest (new)

- **Command:** `HandleExpertRequestCommand(spaceId, requestId, accept)`
- **Handler:** Validates current user is the requested expert. Calls `space.acceptExpertRequest()` or `space.declineExpertRequest()`. Saves. Dispatches `ExpertRequestAccepted` or `ExpertRequestDeclined` event.
- **Port:** `HandleExpertRequestPort`

## Events (new, in `inno_shared`)

| Event Class | NAME constant | Properties |
|------------|---------------|------------|
| `ExpertRequested` | `innovationsbox.expert.requested` | spaceId, expertUserId, requestedByUserId, message |
| `ExpertRequestAccepted` | `innovationsbox.expert.request_accepted` | spaceId, expertUserId, requestId |
| `ExpertRequestDeclined` | `innovationsbox.expert.request_declined` | spaceId, expertUserId, requestId |

Events are dispatched but no listeners implemented yet — `inno_notification` can consume them later for email notifications.

## Frontend

### Projektraum page — Expert request section (sidebar)

- Visible to **initiator and admin** only
- **Search/select dropdown:** Fetches from `GET /api/project-space/{id}/experts`. Shows expert name + expertise area. Initiator selects one.
- **Optional message textarea** below the dropdown
- **"Anfragen" button** sends `POST /api/project-space/{id}/expert-request`
- **Pending requests list** below the button: shows requested expert names with "Ausstehend" badge
- Accepted/declined requests not shown (accepted experts appear in normal member list)

### "Meine Anfragen" page (`/expert-anfragen`)

- **Access:** Users in "Experte" group only
- **User menu:** "Meine Anfragen" link shown for experts (same pattern as "Moderation" for moderators)
- **Layout:** Card list of requests:
  - Project space title (clickable link)
  - Initiator name
  - Message from initiator (if provided)
  - Request date
  - **Accept/Decline** buttons for pending requests
  - Status badge for decided: Angenommen (green) / Abgelehnt (grey)
- **Empty state:** "Keine offenen Anfragen vorhanden."

### TypeScript files

- Extend `resources/js/single_pages/projektraum.ts` — add expert request section in `renderSidebar()`
- Create `resources/js/single_pages/expert-anfragen.ts`
- Create `resources/css/single_pages/expert-anfragen.scss`

### Single page registration

- `/expert-anfragen` registered in `inno_frontend` controller with `excludeNav => true`
- Controller loads assets (`inno_frontend/expert-anfragen` asset group)
- User menu shows link for Experte group members

## Testing

### Unit Tests (Pest)

- `ExpertRequestTest` — create, accept, decline, duplicate prevention, already-member prevention
- `ProjectSpaceTest` — extend with requestExpert, acceptExpertRequest, declineExpertRequest
- `RequestExpertHandlerTest` — handler saves, dispatches event, validates permissions
- `HandleExpertRequestHandlerTest` — accept adds member, decline doesn't, validates expert identity

### Coverage

80% line coverage on new code (matching CI threshold).
