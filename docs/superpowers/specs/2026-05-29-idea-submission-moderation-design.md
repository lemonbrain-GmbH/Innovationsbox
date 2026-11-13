# Idea Submission & Moderation — Design Spec

**Date:** 2026-05-29
**Status:** Approved
**Packages:** `inno_project_idea`, `inno_moderation`, `inno_frontend`

## Overview

Users (Teilnehmer) can submit project ideas via a form. Moderators review submissions and either approve (optionally editing title/description/category) or reject (with mandatory reason). Approved ideas auto-create a Projektraum. Users see their submissions and moderation status on a dedicated page.

## Domain Model Changes

### `inno_project_idea` — ProjectIdea Aggregate

Existing fields: id, title, description, category, status (IdeaStatus enum), submitterId, createdAt, updatedAt.

**Changes:**

- **description** — stored as sanitized HTML. Allowed tags: `<p><br><strong><em><ul><ol><li><a><h2><h3><h4>` (same as status updates).
- **attachments** — new collection of `IdeaAttachment` value objects on ProjectIdea. Max 5 files, max 10MB each.
- **edit(title, description, category)** — new method. Only allowed when status is `Submitted`. Throws `IdeaNotEditableException` otherwise. Attachments can also be added/removed while `Submitted`.
- **rejectionReason** — new nullable string field. Set when processing `ModerationDecisionMade` with a rejection. This keeps the idea package self-contained (no cross-package queries needed for the "Meine Ideen" page).
- **projectSpaceId** — new nullable string field. Set when `ProjectSpaceCreated` event is received, so "Meine Ideen" can link directly to the approved idea's Projektraum.
- **IdeaStatus enum** unchanged: `Submitted`, `UnderReview`, `Approved`, `Rejected`.

### IdeaAttachment Value Object (new)

Properties: id, filename, fileManagerId (int, CMS file reference), mimeType, size (bytes), addedAt.

Allowed MIME types:
- `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- `application/pdf`
- `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
- `application/vnd.oasis.opendocument.text`

### `inno_moderation` — ModerationReview Aggregate

Existing fields: id, ideaId, decision (ModerationDecision enum), reviewerId, notes, createdAt, reviewedAt.

**Changes:**

- **approve()** method gets optional `titleOverride`, `descriptionOverride`, `categoryOverride` parameters. Stored on the review and passed through the `ModerationDecisionMade` event so the idea package can apply them before the project space is created.

## Database Tables

### `ib_project_ideas` (existing — add columns)

| Column | Type | Notes |
|--------|------|-------|
| rejection_reason | TEXT NULL | Set on ModerationDecisionMade (rejected) |
| project_space_id | VARCHAR(64) NULL | Set on ProjectSpaceCreated |

### `ib_idea_attachments` (new)

| Column | Type | Notes |
|--------|------|-------|
| id | VARCHAR(64) PK | |
| idea_id | VARCHAR(64) FK | References ib_project_ideas.id |
| file_manager_id | INT | CMS File Manager reference |
| filename | VARCHAR(255) | Original filename |
| mime_type | VARCHAR(100) | |
| size | INT | Bytes |
| added_at | DATETIME | |

Index: `idx_idea_id` on `idea_id`.

### `ib_moderation_reviews` (missing — needs creation)

| Column | Type | Notes |
|--------|------|-------|
| id | VARCHAR(64) PK | |
| idea_id | VARCHAR(64) | References ib_project_ideas.id |
| decision | VARCHAR(20) | Pending, Approved, Rejected |
| reviewer_id | VARCHAR(64) NULL | CMS user ID |
| notes | TEXT NULL | |
| title_override | VARCHAR(255) NULL | Moderator-edited title |
| description_override | TEXT NULL | Moderator-edited description |
| category_override | VARCHAR(64) NULL | Moderator-edited category |
| created_at | DATETIME | |
| reviewed_at | DATETIME NULL | |

Index: `idx_idea_id` on `idea_id`, `idx_decision` on `decision`.

## API Endpoints

### `inno_project_idea` — `/api/project-idea`

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/project-idea` | idea.submit | Submit new idea (multipart: title, description, category, files[]) |
| `GET` | `/api/project-idea/mine` | Authenticated | List current user's submissions with status, attachments, rejection reason |
| `GET` | `/api/project-idea/{id}` | Exists | Get idea details |
| `PUT` | `/api/project-idea/{id}` | Owner, status=Submitted | Edit idea (multipart: title, description, category, files[], removeFiles[]) |
| `DELETE` | `/api/project-idea/{id}` | Owner, status=Submitted | Withdraw/delete a pending idea |

File uploads sent as `multipart/form-data`. Controller uses CMS File Manager to import files, validates type + size, stores references in `ib_idea_attachments`.

### `inno_moderation` — `/api/moderation`

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/moderation` | Exists | List pending reviews |
| `GET` | `/api/moderation/{id}` | moderation.review | Get full review details including idea data + attachments |
| `POST` | `/api/moderation/{id}/approve` | Exists | Approve with optional notes + title/description/category overrides |
| `POST` | `/api/moderation/{id}/reject` | Exists | Reject with mandatory reason |

Approve body: `{notes?: string, title?: string, description?: string, category?: string}`.

### Event Flow

1. `POST /api/project-idea` saves idea, dispatches `ProjectIdeaSubmitted`
2. `inno_moderation` listens, creates pending `ModerationReview`
3. Moderator approves/rejects, dispatches `ModerationDecisionMade` (includes overrides if present)
4. `inno_project_idea` listens, applies overrides, updates status, dispatches `ProjectIdeaApproved` or `ProjectIdeaRejected`
5. `inno_project_space` listens to `ProjectIdeaApproved`, creates Projektraum

## Frontend Pages

### Page 1: `/projektidee/einreichen` — Submission Form

- **Access:** Authenticated Teilnehmer only. Unauthenticated users see login prompt.
- **Layout:** Single-column form using existing component library.
- **Fields:**
  - Title — text input, required
  - Category — select dropdown from `GET /api/moderation/category`
  - Description — CKEditor rich text (same config as status updates), required
  - Attachments — file input with drag-and-drop zone, up to 5 files, max 10MB each. Thumbnails for images, file icons for docs/PDFs. Remove button per file.
- **Submit** → `POST /api/project-idea` as multipart/form-data
- **Success:** Redirect to `/projektidee/meine` with flash message ("Deine Projektidee wurde eingereicht und wird geprüft.")
- **Errors:** Inline alert above form footer (same pattern as Projektraum modals).

### Page 2: `/projektidee/meine` — My Submissions

- **Access:** Authenticated users only.
- **Layout:** Card list per submission:
  - Title, category badge, status badge with color:
    - Eingereicht — neutral/grey
    - In Prüfung — blue
    - Genehmigt — green, with link to created Projektraum
    - Abgelehnt — red, shows moderator's rejection reason
  - Truncated description (~150 chars), attachment count with file icons
  - Submission date
  - **Edit button** (pencil) — only when status is `Submitted`. Opens edit form (same layout as submission, pre-filled, add/remove attachments).
  - **Withdraw button** — only when status is `Submitted`. Confirmation modal.
- **Empty state:** "Du hast noch keine Projektideen eingereicht." with link to `/projektidee/einreichen`.
- **Data source:** `GET /api/project-idea/mine`

### Page 3: `/moderation` — Moderation Queue

- **Access:** Users with `moderation.review` permission only.
- **Layout:** Table/list of pending reviews: idea title, submitter name, category, submission date.
- **Click row** → navigates to `/moderation/{id}`.

### Page 4: `/moderation/{id}` — Review Detail

- **Layout:** Full idea display:
  - Title, submitter, category, submission date
  - Full description (rendered HTML)
  - Attachments (image thumbnails clickable for full size, download links for docs)
- **Decision section:**
  - **Approve form:** Editable fields for title, description (CKEditor), category (dropdown) — pre-filled with idea values. Optional notes textarea. "Genehmigen" button (green).
  - **Reject form:** Mandatory reason textarea. "Ablehnen" button (red). Confirmation modal.
- **Back link** to `/moderation`.

### TypeScript Files

Following existing `projektraum.ts` pattern — one TS file per single page:
- `resources/js/single_pages/projektidee-einreichen.ts`
- `resources/js/single_pages/projektidee-meine.ts`
- `resources/js/single_pages/moderation.ts`
- `resources/js/single_pages/moderation-detail.ts`

Auto-discovered and compiled via webpack from `resources/js/single_pages/`.

## File Upload Handling

### Strategy

Files uploaded via **Concrete CMS File Manager** — standard CMS approach providing:
- Automatic storage management
- Thumbnail generation for images
- Permission-based access control
- Existing file serving infrastructure

### Flow

1. User selects files in submission form
2. `POST /api/project-idea` sends `multipart/form-data`
3. Controller validates: max 5 files, max 10MB each, allowed MIME types
4. Controller imports files into CMS File Manager
5. `IdeaAttachment` value objects created with CMS `fileManagerId` reference
6. On edit: `removeFiles[]` deletes from `ib_idea_attachments` and File Manager
7. On idea withdrawal: all associated files cleaned up

### Security

- MIME type validated server-side (not just extension)
- File size enforced server-side
- Only idea owner can add/remove attachments
- Moderators can view attachments via review detail page

## Testing

### Unit Tests (Pest, `tests/unit/`)

**`inno_project_idea`:**
- `ProjectIdeaTest` — extend: edit while submitted, edit throws when not submitted, add/remove attachments, attachment limits (max 5, size, types)
- `IdeaAttachmentTest` — creation, validation (mime type, size)
- `SubmitProjectIdeaHandlerTest` — extend for attachments
- `EditProjectIdeaHandlerTest` — new: edit success, edit locked when under review, owner check
- `DeleteProjectIdeaHandlerTest` — new: delete success, delete locked when not submitted, owner check

**`inno_moderation`:**
- `ReviewProjectIdeaHandlerTest` — extend: approval with overrides, overrides passed through event
- `ModerationReviewTest` — extend: approve with overrides stored

### E2E Tests (Playwright)

- Idea submission flow (login, fill form, upload, submit, verify on "Meine Ideen")
- Edit flow (edit pending idea, verify update)
- Moderation approval flow (moderator approves with overrides, Projektraum created)
- Rejection flow (reject with reason, submitter sees reason)

### Coverage

80% line coverage on new code (matches CI threshold).
