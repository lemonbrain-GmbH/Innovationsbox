# Project Header Image — Design Spec

**Date:** 2026-07-28
**Approach:** Image on the idea (uploaded at submission), copied to project space on approval, moderator can change independently

## Summary

Add header image support to project spaces. Initiators upload a header image when submitting an idea. On approval, the image carries over to the project space. Moderators and admins can change the image from both the project space detail page and the moderation dashboard. The image displays in the project card on the Innovationen listing page and as a hero/banner on the project space detail page.

## 1. Database & Domain

### Schema Changes

**`ib_project_ideas`** — add column:
```sql
ALTER TABLE ib_project_ideas ADD COLUMN header_image_file_id INT NULL;
```

**`ib_project_spaces`** — add column:
```sql
ALTER TABLE ib_project_spaces ADD COLUMN header_image_file_id INT NULL;
```

Both columns store a Concrete CMS file manager ID (integer). Nullable — header image is optional.

### Domain Model Changes

**`ProjectIdea` entity** (`inno_project_idea/src/Domain/Model/ProjectIdea.php`):
- Add `private ?int $headerImageFileId = null`
- Add `getHeaderImageFileId(): ?int` and `setHeaderImageFileId(?int $fileId): void`

**`ProjectSpace` entity** (`inno_project_space/src/Domain/Model/ProjectSpace.php`):
- Add `private ?int $headerImageFileId = null`
- Add `getHeaderImageFileId(): ?int` and `setHeaderImage(?int $fileId): void`

### Approval Flow

**`OnProjectIdeaApproved`** event handler (`inno_project_space/src/Application/EventHandler/`):
- When creating the project space from the approved idea, copy `headerImageFileId` from the idea to the new space (alongside title, description, category, initiator)

## 2. Idea Submission (Upload)

### API

**`POST /api/project-idea`** — extend to accept `headerImage` field:
- Separate from existing `files[]` attachments
- Single file upload via multipart/form-data field `headerImage`
- Validation: JPEG, PNG, GIF, WebP only; max 10MB; minimum image width 800px
- Import via `Concrete\Core\File\Importer`, store the resulting file manager ID on the idea entity

### Frontend (`resources/js/single_pages/projektidee-einreichen.ts`)

- Add dedicated upload zone above the existing attachments section
- Label: "Titelbild hochladen"
- Single file only — re-uploading replaces the previous selection
- Image preview after selection: inline `<img>` with `object-fit: cover` in a 16:9 aspect ratio container (matches card display)
- Remove button to clear the selection
- Client-side validation before upload:
  - File type: JPEG, PNG, GIF, WebP
  - File size: max 10MB
  - Image width: minimum 800px (read via `new Image()` to check dimensions)
- Sent as `headerImage` field in FormData (not in `files[]` array)

### PHP Template (`inno_frontend/single_pages/projektidee/einreichen.php`)

- Add the upload field markup between the description editor and the attachments section

## 3. API Response & Display

### API Changes

**`GET /api/project-space`** (list endpoint):
- Add `headerImageUrl` string field to each card object
- Resolve via existing `resolveFileUrl(int $fileManagerId): string` pattern
- Empty string if no header image

**`GET /api/project-space/{id}`** (detail endpoint):
- Add `headerImageUrl` string field

**`GET /api/project-idea/{id}`** (if applicable):
- Add `headerImageUrl` string field for moderation review visibility

### Innovationen Page (`resources/js/single_pages/innovationen.ts`)

- Add `headerImageUrl: string` to the `ProjectSpaceCard` interface
- Update `renderCard()`: if `headerImageUrl` is non-empty, render `<img>` inside the existing `.project-card__image` div
- If no image: the gray placeholder area remains (already implemented)

### Project Space Detail Page

- Show header image as a hero/banner at the top of the detail view
- Full width within the content area, `object-fit: cover`, same 390:225 aspect ratio as cards
- If no image: no hero section shown (skip the element entirely)

## 4. Moderator Image Management

### Project Space Detail Page (Edit)

- Edit icon (pencil) overlaid on the header image, visible only to initiator, moderator, or admin
- Click opens a file picker with the same validation rules (JPEG/PNG/GIF/WebP, max 10MB, min 800px width)
- Shows preview before confirming the change
- On confirm: uploads via `POST /api/project-space/{id}/header-image`

### Moderation Dashboard

- When reviewing an idea: the header image is visible in the review panel
- When viewing a project space: "Titelbild andern" button to replace the image
- Same upload and validation flow

### API Endpoints

**`POST /api/project-space/{id}/header-image`**:
- Accepts single image file (multipart/form-data)
- Same validation: JPEG/PNG/GIF/WebP, max 10MB, min 800px width
- Imports via `FileImporter`, updates `header_image_file_id` on the project space
- Auth: initiator, moderator, or admin only
- Returns updated `headerImageUrl`

**`DELETE /api/project-space/{id}/header-image`**:
- Sets `header_image_file_id` to null on the project space
- Auth: initiator, moderator, or admin only
- Returns success

## 5. Constraints & Validation

| Rule | Value |
|---|---|
| Allowed MIME types | `image/jpeg`, `image/png`, `image/gif`, `image/webp` |
| Max file size | 10MB |
| Min image width | 800px |
| Max files | 1 (single header image) |
| Required | No — header image is optional |
| Display aspect ratio | 390:225 (~16:9), CSS `object-fit: cover` |

## 6. File Storage

Uses existing Concrete CMS file manager pattern:
1. Upload via `FileImporter::importUploadedFile()`
2. Store `file_manager_id` (integer) in the custom table column
3. Resolve URL via `File::getByID($id)->getApprovedVersion()->getURL()`
4. No custom storage paths — CMS manages file location and serving
