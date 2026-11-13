# Categories & Search for Projekträume

## Overview

Add dynamic categories to project spaces and a search function to the Projekträume overview page. Categories are managed by moderators (CRUD in `inno_moderation`), read by other packages via `inno_shared`, and displayed/filterable on the frontend.

## Data Model & Ownership

### `inno_moderation` — Category Management

New DB table `ib_categories`:

| Column       | Type          | Constraints          |
|-------------|---------------|----------------------|
| `id`        | VARCHAR(64)   | PK                   |
| `name`      | VARCHAR(100)  | NOT NULL             |
| `slug`      | VARCHAR(100)  | NOT NULL, UNIQUE     |
| `sort_order`| INT           | NOT NULL, DEFAULT 0  |
| `created_at`| DATETIME      | NOT NULL             |
| `updated_at`| DATETIME      | NOT NULL             |

Domain entity `Category` in `inno_moderation/src/Domain/Model/` with name, slug, sort order.

Hexagonal structure:
- `Port/In/`: `ManageCategoryPort` (create, update, delete, list)
- `Port/Out/`: `CategoryRepositoryPort` (save, findById, findAll, findBySlug, delete)
- `Application/UseCase/ManageCategory/`: Command + Handler
- `Adapter/In/Http/`: `CategoryApiController`
- `Adapter/Out/Persistence/`: `ConcreteCategoryRepository`

Seeded with 5 initial categories on install: Bauen, Wohnen, Landwirtschaft, Tourismus, Mobilität.

### `inno_shared` — Read-Only Access

New interface `CategoryReaderPort` in `inno_shared/src/Port/`:

```php
interface CategoryReaderPort
{
    /** @return array<array{id: string, name: string, slug: string}> */
    public function findAll(): array;
}
```

`inno_moderation` registers the implementation via `$this->app->bind(CategoryReaderPort::class, ConcreteCategoryRepository::class)` in `on_start()`.

### `inno_project_idea` + `inno_project_space` — Category Reference

New column `category_id VARCHAR(64) NULL` added to:
- `ib_project_ideas` — replaces the existing `category` free-text string column. Migration: add `category_id`, map existing `category` values to matching category slugs, then drop the old `category` column.
- `ib_project_spaces` — new column, NULL for existing spaces without a category.

No FK constraint (cross-package). Reference by convention. The API controller resolves `categoryName`/`categorySlug` by reading `CategoryReaderPort` with the stored `category_id`.

## Event Propagation

`ProjectIdeaApproved` event in `inno_shared` gets a new field:

```php
public function __construct(
    private string $ideaId,
    private string $submitterId,
    private string $categoryId,  // NEW
) {}
```

`OnProjectIdeaApproved` handler in `inno_project_space` reads `$event->getCategoryId()` and passes it to `ProjectSpace::createFromApprovedIdea()`.

## API

### Category CRUD — Moderators only

| Method   | Endpoint                        | Body                          | Auth       |
|----------|---------------------------------|-------------------------------|------------|
| `GET`    | `/api/moderation/category`      | —                             | Moderator  |
| `POST`   | `/api/moderation/category`      | `{name, slug?}`               | Moderator  |
| `PUT`    | `/api/moderation/category/{id}` | `{name?, slug?, sort_order?}` | Moderator  |
| `DELETE` | `/api/moderation/category/{id}` | —                             | Moderator  |

Delete fails if any project spaces are assigned to the category.

### Public Category List

| Method | Endpoint                         | Response                        |
|--------|----------------------------------|---------------------------------|
| `GET`  | `/api/project-space/categories`  | `{data: [{id, name, slug}...]`  |

Reads via `CategoryReaderPort`. Used by the frontend to render filter buttons.

### Project Space List — Extended

`GET /api/project-space` — two new query parameters:

| Parameter  | Type   | Example              | Behavior                        |
|-----------|--------|----------------------|---------------------------------|
| `category`| string | `?category=bauen`    | Filter by category slug         |
| `search`  | string | `?search=solar`      | LIKE search on title + description |

Both combinable with existing `status` filter.

### Project Space Detail — Extended

`GET /api/project-space/{id}` response includes:
- `categoryId` (string)
- `categoryName` (string)
- `categorySlug` (string)

## Frontend — Projekträume Overview

### Layout (top to bottom)

1. Title + Subtitle (existing)
2. Search field — `<input>` with placeholder "Projekte durchsuchen...", 300ms debounce
3. Status filter pills (existing: Alle, Aktiv, Abgeschlossen, Archiviert)
4. Category filter pills — dynamically rendered from `/api/project-space/categories`, same visual style as status filters, with "Alle" default
5. Grid with project cards (existing)

### Filter Behavior

- Status + category + search are combinable
- Changing any filter resets to page 1
- Category filter buttons loaded on page init via API call
- Search triggers API call after 300ms of no typing

### Project Cards

- New category badge in card header, next to the status badge
- Neutral/grey styling — informative, not competing with status

## Frontend — Detail Page

- Category badge in header next to status badge (neutral/grey style)
- No other changes

## Frontend — Idea Submission

- New required `<select>` field "Kategorie" in the idea form
- Options loaded via `CategoryReaderPort` (through whichever API the idea form uses)

## Testing

- Unit tests for `Category` domain entity
- Unit tests for `ManageCategoryHandler` (CRUD use cases)
- Unit tests for `OnProjectIdeaApproved` — assert `categoryId` propagation
- Unit tests for `ProjectSpace` — assert category stored/retrieved
- Unit tests for repository filtering (category + search, mocked)
- Update existing tests that construct `ProjectSpace` or `ProjectIdeaApproved` to include category

## Out of Scope

- No admin UI for category management (API-only for now)
- No URL deep-linking for filters
- No multi-category per project (1:1 relationship)
- No full-text search over status updates
- No category icons or colors (plain text badges)
