# Project Header Image Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add header image support to project ideas and spaces — initiators upload during idea submission, image carries over to the project space on approval, moderators can change it later.

**Architecture:** Add `headerImageFileId` to both `ProjectIdea` and `ProjectSpace` domain entities. Use the existing CMS `FileImporter` pattern for uploads. The `ProjectIdeaApproved` event carries the file ID to the space. New API endpoint for changing the image on a space. Frontend gets a dedicated upload zone with preview on the idea submission form.

**Tech Stack:** PHP (Concrete CMS, hexagonal architecture), TypeScript, SCSS, Pest/Mockery for tests

## Global Constraints

- Hexagonal architecture: Domain → Application → Ports → Adapters
- File uploads use `Concrete\Core\File\Importer` and store `file_manager_id` (int) in custom tables
- File URLs resolved via `File::getByID($id)->getApprovedVersion()->getURL()`
- Allowed MIME types for header image: `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- Max file size: 10MB, minimum image width: 800px
- Header image is optional (nullable field)
- Always use design tokens for any new CSS — never hardcode values
- Run `composer test` for unit tests, `npm run lint:all` and `composer lint:php` before committing

---

### Task 1: Add Header Image to ProjectIdea Domain & Persistence

**Files:**
- Modify: `public/packages/inno_project_idea/src/Domain/Model/ProjectIdea.php`
- Modify: `public/packages/inno_project_idea/src/Adapter/Out/Persistence/ConcreteProjectIdeaRepository.php`
- Test: `tests/unit/inno_project_idea/Domain/Model/ProjectIdeaHeaderImageTest.php`

**Interfaces:**
- Consumes: nothing (first task)
- Produces: `ProjectIdea::getHeaderImageFileId(): ?int`, `ProjectIdea::setHeaderImageFileId(?int $fileId): void`

- [ ] **Step 1: Write failing test**

Create `tests/unit/inno_project_idea/Domain/Model/ProjectIdeaHeaderImageTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;

test('header image file id is null by default', function () {
    $idea = ProjectIdea::submit('idea_1', 'Title', 'Desc', 'cat_1', '42');
    expect($idea->getHeaderImageFileId())->toBeNull();
});

test('can set and get header image file id', function () {
    $idea = ProjectIdea::submit('idea_1', 'Title', 'Desc', 'cat_1', '42');
    $idea->setHeaderImageFileId(123);
    expect($idea->getHeaderImageFileId())->toBe(123);
});

test('can clear header image file id', function () {
    $idea = ProjectIdea::submit('idea_1', 'Title', 'Desc', 'cat_1', '42');
    $idea->setHeaderImageFileId(123);
    $idea->setHeaderImageFileId(null);
    expect($idea->getHeaderImageFileId())->toBeNull();
});

test('reconstitute preserves header image file id', function () {
    $idea = ProjectIdea::reconstitute(
        id: 'idea_1',
        title: 'Title',
        description: 'Desc',
        category: 'cat_1',
        status: IdeaStatus::Submitted,
        submitterId: 42,
        createdAt: new DateTimeImmutable(),
        updatedAt: new DateTimeImmutable(),
        headerImageFileId: 456,
    );
    expect($idea->getHeaderImageFileId())->toBe(456);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
composer test -- tests/unit/inno_project_idea/Domain/Model/ProjectIdeaHeaderImageTest.php
```

Expected: FAIL — `getHeaderImageFileId` method not found.

- [ ] **Step 3: Add headerImageFileId to ProjectIdea entity**

In `public/packages/inno_project_idea/src/Domain/Model/ProjectIdea.php`:

Add to the constructor parameter list (after `feedbackDeadline`):
```php
        private ?int $headerImageFileId = null,
```

Add getter and setter:
```php
    public function getHeaderImageFileId(): ?int
    {
        return $this->headerImageFileId;
    }

    public function setHeaderImageFileId(?int $fileId): void
    {
        $this->headerImageFileId = $fileId;
        $this->updatedAt = new \DateTimeImmutable();
    }
```

Add `headerImageFileId` parameter to `reconstitute()` method (after `feedbackDeadline`):
```php
        ?int $headerImageFileId = null,
```

And pass it through in the `reconstitute()` body:
```php
        return new self(
            // ... existing params ...
            feedbackDeadline: $feedbackDeadline,
            headerImageFileId: $headerImageFileId,
        );
```

- [ ] **Step 4: Run test to verify it passes**

```bash
composer test -- tests/unit/inno_project_idea/Domain/Model/ProjectIdeaHeaderImageTest.php
```

Expected: PASS (4 tests)

- [ ] **Step 5: Update ConcreteProjectIdeaRepository persistence**

In `public/packages/inno_project_idea/src/Adapter/Out/Persistence/ConcreteProjectIdeaRepository.php`:

**INSERT** — add `header_image_file_id` column and value:
```sql
INSERT INTO ... (..., feedback_deadline, header_image_file_id, created_at, updated_at)
VALUES (..., ?, ?, ?, ?)
```
Add `$idea->getHeaderImageFileId()` to the values array (after feedback_deadline, before created_at).

**UPDATE** — add `header_image_file_id = ?` to the SET clause:
```sql
UPDATE ... SET ..., feedback_deadline = ?, header_image_file_id = ?, updated_at = ? WHERE id = ?
```
Add `$idea->getHeaderImageFileId()` to the values array.

**hydrate()** — pass `headerImageFileId` to `reconstitute()`:
```php
headerImageFileId: $row['header_image_file_id'] !== null ? (int) $row['header_image_file_id'] : null,
```

- [ ] **Step 6: Add database column**

Add migration in the package's `install()` and `upgrade()` methods, or use direct SQL. The column:
```sql
ALTER TABLE ib_project_ideas ADD COLUMN header_image_file_id INT NULL AFTER feedback_deadline;
```

Add this to `public/packages/inno_project_idea/controller.php` in the `upgrade()` method.

- [ ] **Step 7: Run all unit tests**

```bash
composer test
```

Expected: All tests pass.

- [ ] **Step 8: Commit**

```bash
git add public/packages/inno_project_idea/ tests/unit/inno_project_idea/
git commit -m "feat: add headerImageFileId to ProjectIdea domain and persistence"
```

---

### Task 2: Add Header Image to ProjectSpace Domain & Persistence

**Files:**
- Modify: `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php`
- Modify: `public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php`
- Test: `tests/unit/inno_project_space/Domain/Model/ProjectSpaceHeaderImageTest.php`

**Interfaces:**
- Consumes: nothing (independent from Task 1)
- Produces: `ProjectSpace::getHeaderImageFileId(): ?int`, `ProjectSpace::setHeaderImage(?int $fileId): void`

- [ ] **Step 1: Write failing test**

Create `tests/unit/inno_project_space/Domain/Model/ProjectSpaceHeaderImageTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;

test('header image file id is null by default', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1');
    expect($space->getHeaderImageFileId())->toBeNull();
});

test('can set header image', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1');
    $space->setHeaderImage(789);
    expect($space->getHeaderImageFileId())->toBe(789);
});

test('can clear header image', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1');
    $space->setHeaderImage(789);
    $space->setHeaderImage(null);
    expect($space->getHeaderImageFileId())->toBeNull();
});

test('createFromApprovedIdea accepts header image file id', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1', 555);
    expect($space->getHeaderImageFileId())->toBe(555);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
composer test -- tests/unit/inno_project_space/Domain/Model/ProjectSpaceHeaderImageTest.php
```

- [ ] **Step 3: Add headerImageFileId to ProjectSpace entity**

In `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php`:

Add property to the class:
```php
    private ?int $headerImageFileId = null;
```

Add to `createFromApprovedIdea()` — new optional parameter:
```php
    public static function createFromApprovedIdea(
        string $id,
        string $projectIdeaId,
        string $title,
        string $description,
        string $initiatorId,
        string $categoryId = '',
        ?int $headerImageFileId = null,
    ): self {
        $now = new \DateTimeImmutable();
        $space = new self($id, $projectIdeaId, $title, $description, SpaceStatus::Active, $initiatorId, $categoryId, $now, $now);
        $space->memberIds[] = $initiatorId;
        $space->headerImageFileId = $headerImageFileId;
        return $space;
    }
```

Add to `fromPersistence()` — new optional parameter (after `expertRequests`):
```php
        ?int $headerImageFileId = null,
```
And in the body: `$space->headerImageFileId = $headerImageFileId;`

Add getter and setter:
```php
    public function getHeaderImageFileId(): ?int
    {
        return $this->headerImageFileId;
    }

    public function setHeaderImage(?int $fileId): void
    {
        $this->headerImageFileId = $fileId;
        $this->updatedAt = new \DateTimeImmutable();
    }
```

- [ ] **Step 4: Run test to verify it passes**

```bash
composer test -- tests/unit/inno_project_space/Domain/Model/ProjectSpaceHeaderImageTest.php
```

- [ ] **Step 5: Update ConcreteProjectSpaceRepository persistence**

In `public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php`:

**INSERT** (in `save()`): add `header_image_file_id` column and `$space->getHeaderImageFileId()` value.

**UPDATE** (in `save()`): add `header_image_file_id = ?` to the SET clause and `$space->getHeaderImageFileId()` to values.

**hydrate()**: pass to `fromPersistence()`:
```php
headerImageFileId: $row['header_image_file_id'] !== null ? (int) $row['header_image_file_id'] : null,
```

- [ ] **Step 6: Add database column**

```sql
ALTER TABLE ib_project_spaces ADD COLUMN header_image_file_id INT NULL AFTER category_id;
```

Add to `public/packages/inno_project_space/controller.php` `upgrade()` method.

- [ ] **Step 7: Run all unit tests**

```bash
composer test
```

- [ ] **Step 8: Commit**

```bash
git add public/packages/inno_project_space/ tests/unit/inno_project_space/
git commit -m "feat: add headerImageFileId to ProjectSpace domain and persistence"
```

---

### Task 3: Wire Approval Flow and Shared Event

**Files:**
- Modify: `public/packages/inno_shared/src/Domain/Event/ProjectIdeaApproved.php`
- Modify: `public/packages/inno_project_space/src/Application/EventHandler/OnProjectIdeaApproved.php`
- Modify: moderation controller that dispatches the `ProjectIdeaApproved` event (find via `grep ProjectIdeaApproved`)
- Test: `tests/unit/inno_project_space/Application/OnProjectIdeaApprovedHeaderImageTest.php`

**Interfaces:**
- Consumes: `ProjectIdea::getHeaderImageFileId()` from Task 1, `ProjectSpace::createFromApprovedIdea(..., ?int $headerImageFileId)` from Task 2
- Produces: Header image file ID flows from idea → event → space on approval

- [ ] **Step 1: Add headerImageFileId to ProjectIdeaApproved event**

In `public/packages/inno_shared/src/Domain/Event/ProjectIdeaApproved.php`:

Add constructor parameter:
```php
        private readonly ?int $headerImageFileId = null,
```

Add getter:
```php
    public function getHeaderImageFileId(): ?int
    {
        return $this->headerImageFileId;
    }
```

- [ ] **Step 2: Update event dispatch to include headerImageFileId**

Find where `ProjectIdeaApproved` is dispatched (in `inno_moderation` package — the moderation decision handler). Add `headerImageFileId` to the constructor call. The idea's `getHeaderImageFileId()` value should be passed through.

Search for: `new ProjectIdeaApproved(` and add the parameter.

- [ ] **Step 3: Update OnProjectIdeaApproved handler**

In `public/packages/inno_project_space/src/Application/EventHandler/OnProjectIdeaApproved.php`:

Pass header image to space creation:
```php
            $space = ProjectSpace::createFromApprovedIdea(
                id: uniqid('space_', true),
                projectIdeaId: $event->getIdeaId(),
                title: $event->getTitle() ?: "Projektraum – {$event->getIdeaId()}",
                description: $event->getDescription(),
                initiatorId: $event->getSubmitterId(),
                categoryId: $event->getCategoryId(),
                headerImageFileId: $event->getHeaderImageFileId(),
            );
```

- [ ] **Step 4: Write test for the approval flow**

Create `tests/unit/inno_project_space/Application/OnProjectIdeaApprovedHeaderImageTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\EventHandler\OnProjectIdeaApproved;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn () => Mockery::close());

test('header image file id is passed from event to project space', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);

    $savedSpace = null;
    $repo->shouldReceive('save')->once()->withArgs(function (ProjectSpace $space) use (&$savedSpace) {
        $savedSpace = $space;
        return true;
    });
    $dispatcher->shouldReceive('dispatch')->once();
    $logger->shouldReceive('info')->once();
    $transaction->shouldReceive('transactional')->once()->andReturnUsing(fn (callable $fn) => $fn());

    $event = new ProjectIdeaApproved(
        ideaId: 'idea_1',
        submitterId: '42',
        categoryId: 'cat_1',
        title: 'Test',
        description: 'Desc',
        headerImageFileId: 999,
    );

    $handler = new OnProjectIdeaApproved($repo, $dispatcher, $logger, $transaction);
    $handler->handle($event);

    expect($savedSpace)->not->toBeNull();
    expect($savedSpace->getHeaderImageFileId())->toBe(999);
});
```

- [ ] **Step 5: Run tests**

```bash
composer test
```

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_shared/ public/packages/inno_project_space/ public/packages/inno_moderation/ tests/unit/
git commit -m "feat: pass header image through approval flow (event → space)"
```

---

### Task 4: API — Return headerImageUrl in Responses

**Files:**
- Modify: `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`
- Modify: `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php`
- Modify: `resources/js/single_pages/innovationen.ts`

**Interfaces:**
- Consumes: `ProjectSpace::getHeaderImageFileId()` from Task 2, `ProjectIdea::getHeaderImageFileId()` from Task 1
- Produces: `headerImageUrl` string field in API JSON responses

- [ ] **Step 1: Add headerImageUrl to project space list response**

In `ProjectSpaceApiController.php`, find the method that builds the list card data (the array with `id`, `title`, `description`, etc. returned by the `GET /api/project-space` endpoint). Add:

```php
'headerImageUrl' => $space->getHeaderImageFileId() !== null
    ? $this->resolveFileUrl($space->getHeaderImageFileId())
    : '',
```

- [ ] **Step 2: Add headerImageUrl to project space detail response**

In the same controller, find the detail method (`GET /api/project-space/{id}`). Add the same `headerImageUrl` field to the response array.

- [ ] **Step 3: Add headerImageUrl to project idea responses**

In `ProjectIdeaApiController.php`, find where idea data is serialized for API responses (list and detail). Add:

```php
'headerImageUrl' => $idea->getHeaderImageFileId() !== null
    ? $this->resolveFileUrl($idea->getHeaderImageFileId())
    : '',
```

- [ ] **Step 4: Update TypeScript interface and renderCard**

In `resources/js/single_pages/innovationen.ts`:

Add to `ProjectSpaceCard` interface:
```typescript
  headerImageUrl: string;
```

Update `renderCard()` — replace the empty image div with conditional image:
```typescript
      <div class="project-card__image">
        ${space.headerImageUrl ? `<img src="${escapeHtml(space.headerImageUrl)}" alt="" loading="lazy">` : ''}
      </div>
```

- [ ] **Step 5: Build and verify**

```bash
npm run development
npm run lint:all
composer lint:php
```

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_project_space/ public/packages/inno_project_idea/ resources/js/single_pages/innovationen.ts
git commit -m "feat: return headerImageUrl in API responses and display in cards"
```

---

### Task 5: Header Image Upload on Idea Submission Form

**Files:**
- Modify: `public/packages/inno_frontend/single_pages/projektidee/einreichen.php`
- Modify: `resources/js/single_pages/projektidee-einreichen.ts`
- Modify: `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php`
- Create: `resources/css/single_pages/_header-image-upload.scss` (or add styles to existing einreichen scss)

**Interfaces:**
- Consumes: `ProjectIdea::setHeaderImageFileId()` from Task 1
- Produces: `headerImage` field in multipart form submission, stored on idea

- [ ] **Step 1: Add upload field to PHP template**

In `public/packages/inno_frontend/single_pages/projektidee/einreichen.php`, add between the description editor and the attachments section:

```php
<div class="form-section">
    <label class="form-label"><?= t('Titelbild') ?></label>
    <p class="form-hint"><?= t('Fügen Sie ein Titelbild hinzu (min. 800px breit, max. 10MB). JPEG, PNG, GIF oder WebP.') ?></p>
    <div class="header-image-upload" id="header-image-upload">
        <div class="header-image-upload__preview" id="header-image-preview" hidden>
            <img id="header-image-preview-img" alt="" loading="lazy">
            <button type="button" class="header-image-upload__remove" id="header-image-remove" aria-label="<?= t('Titelbild entfernen') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="header-image-upload__dropzone" id="header-image-dropzone">
            <span><?= t('Bild hierher ziehen oder klicken zum Auswählen') ?></span>
            <input type="file" id="header-image-input" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Add TypeScript for header image upload**

In `resources/js/single_pages/projektidee-einreichen.ts`, add:

1. A `headerImageFile` variable to hold the selected file
2. Click handler on the dropzone to trigger the hidden input
3. Change handler on the input to validate (type, size, width) and show preview
4. Drag-and-drop handlers on the dropzone
5. Remove button handler to clear the selection
6. Width validation via `new Image()` — check `naturalWidth >= 800`
7. On form submit: add `headerImage` field to the FormData (separate from `files[]`)

Key validation logic:
```typescript
function validateHeaderImage(file: File): Promise<string | null> {
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (!allowedTypes.includes(file.type)) {
    return Promise.resolve('Nur JPEG, PNG, GIF oder WebP erlaubt.');
  }
  if (file.size > 10 * 1024 * 1024) {
    return Promise.resolve('Maximale Dateigrösse: 10 MB.');
  }
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      resolve(img.naturalWidth < 800 ? 'Mindestbreite: 800 Pixel.' : null);
      URL.revokeObjectURL(img.src);
    };
    img.onerror = () => resolve('Bild konnte nicht gelesen werden.');
    img.src = URL.createObjectURL(file);
  });
}
```

- [ ] **Step 3: Handle headerImage in the API controller**

In `ProjectIdeaApiController.php`, in the submit method, after processing regular attachments:

```php
// Header image (single file, separate from attachments)
$headerImageFile = $request->files->get('headerImage');
if ($headerImageFile instanceof UploadedFile && $headerImageFile->isValid()) {
    $importer = new \Concrete\Core\File\Importer();
    $result = $importer->importUploadedFile($headerImageFile);
    if ($result instanceof \Concrete\Core\Entity\File\Version) {
        $idea->setHeaderImageFileId((int) $result->getFile()->getFileID());
    }
}
```

Also add minimum width validation server-side:
```php
if ($headerImageFile instanceof UploadedFile && $headerImageFile->isValid()) {
    $imageSize = @getimagesize($headerImageFile->getPathname());
    if ($imageSize === false || $imageSize[0] < 800) {
        return $this->json(['error' => 'Header image must be at least 800px wide.'], 422);
    }
    // ... import
}
```

- [ ] **Step 4: Add SCSS for the upload component**

Add styles for `.header-image-upload` — either in the existing einreichen SCSS or a shared file. Key styles:

```scss
.header-image-upload {
  &__dropzone {
    border: 2px dashed var(--card-border, #e4e4e4);
    border-radius: var(--radius-btn, 8px);
    padding: var(--space-8);
    text-align: center;
    cursor: pointer;
    color: var(--text-muted-color, #666);
    transition: border-color 150ms ease;

    &:hover, &--active {
      border-color: var(--color-action-primary);
    }
  }

  &__preview {
    position: relative;
    border-radius: var(--radius-btn, 8px);
    overflow: hidden;
    aspect-ratio: 390 / 225;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }

  &__remove {
    position: absolute;
    top: var(--space-2);
    right: var(--space-2);
    // ... button reset + white bg circle styling
  }
}
```

- [ ] **Step 5: Build and verify**

```bash
npm run development
npm run lint:all
composer lint:php
```

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_frontend/single_pages/projektidee/ resources/js/single_pages/ resources/css/single_pages/ public/packages/inno_project_idea/
git commit -m "feat: header image upload on idea submission form with preview"
```

---

### Task 6: Header Image Management API for Project Spaces

**Files:**
- Modify: `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`
- Create: `public/packages/inno_project_space/src/Application/UseCase/UpdateHeaderImageHandler.php`
- Create: `public/packages/inno_project_space/src/Port/In/UpdateHeaderImagePort.php`
- Test: `tests/unit/inno_project_space/Application/UseCase/UpdateHeaderImageHandlerTest.php`

**Interfaces:**
- Consumes: `ProjectSpace::setHeaderImage(?int)` from Task 2, `ProjectSpaceRepositoryInterface::save()` / `findById()`
- Produces: `POST /api/project-space/{id}/header-image` and `DELETE /api/project-space/{id}/header-image` endpoints

- [ ] **Step 1: Create port interface**

Create `public/packages/inno_project_space/src/Port/In/UpdateHeaderImagePort.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

interface UpdateHeaderImagePort
{
    public function updateHeaderImage(string $spaceId, ?int $fileId): void;
}
```

- [ ] **Step 2: Write failing test for handler**

Create `tests/unit/inno_project_space/Application/UseCase/UpdateHeaderImageHandlerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\UpdateHeaderImageHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;

afterEach(fn () => Mockery::close());

test('sets header image on project space', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1');
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $repo->shouldReceive('findById')->with('s1')->once()->andReturn($space);
    $repo->shouldReceive('save')->once()->withArgs(fn (ProjectSpace $s) => $s->getHeaderImageFileId() === 123);

    $handler = new UpdateHeaderImageHandler($repo);
    $handler->updateHeaderImage('s1', 123);

    expect($space->getHeaderImageFileId())->toBe(123);
});

test('clears header image when file id is null', function () {
    $space = ProjectSpace::createFromApprovedIdea('s1', 'idea_1', 'Title', 'Desc', '42', 'cat_1', 123);
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $repo->shouldReceive('findById')->with('s1')->once()->andReturn($space);
    $repo->shouldReceive('save')->once()->withArgs(fn (ProjectSpace $s) => $s->getHeaderImageFileId() === null);

    $handler = new UpdateHeaderImageHandler($repo);
    $handler->updateHeaderImage('s1', null);

    expect($space->getHeaderImageFileId())->toBeNull();
});

test('throws when space not found', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $repo->shouldReceive('findById')->with('s1')->once()->andReturn(null);

    $handler = new UpdateHeaderImageHandler($repo);
    $handler->updateHeaderImage('s1', 123);
})->throws(\RuntimeException::class);
```

- [ ] **Step 3: Implement handler**

Create `public/packages/inno_project_space/src/Application/UseCase/UpdateHeaderImageHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase;

use Concrete\Package\InnoProjectSpace\Port\In\UpdateHeaderImagePort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;

class UpdateHeaderImageHandler implements UpdateHeaderImagePort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
    ) {}

    public function updateHeaderImage(string $spaceId, ?int $fileId): void
    {
        $space = $this->repository->findById($spaceId);
        if ($space === null) {
            throw new \RuntimeException("Project space not found: {$spaceId}");
        }
        $space->setHeaderImage($fileId);
        $this->repository->save($space);
    }
}
```

- [ ] **Step 4: Add API endpoints to controller**

In `ProjectSpaceApiController.php`, add two new methods:

**POST /api/project-space/{id}/header-image:**
```php
public function uploadHeaderImage(string $id): JsonResponse
{
    // Auth check: initiator, moderator, or admin
    // Validate uploaded file (type, size, width >= 800)
    // Import via FileImporter
    // Call UpdateHeaderImagePort::updateHeaderImage($id, $fileId)
    // Return { headerImageUrl: '...' }
}
```

**DELETE /api/project-space/{id}/header-image:**
```php
public function deleteHeaderImage(string $id): JsonResponse
{
    // Auth check: initiator, moderator, or admin
    // Call UpdateHeaderImagePort::updateHeaderImage($id, null)
    // Return { success: true }
}
```

- [ ] **Step 5: Register routes and DI bindings**

In `public/packages/inno_project_space/controller.php`:
- Add route for `POST /api/project-space/{id}/header-image`
- Add route for `DELETE /api/project-space/{id}/header-image`
- Add DI binding: `UpdateHeaderImagePort::class => UpdateHeaderImageHandler::class`

- [ ] **Step 6: Run tests and lint**

```bash
composer test
composer lint:php
```

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_project_space/ tests/unit/inno_project_space/
git commit -m "feat: API endpoints for uploading/deleting project space header image"
```

---

### Task 7: Frontend — Edit Header Image on Detail Page & Moderation

**Files:**
- Modify: `public/packages/inno_frontend/single_pages/projektraum/view.php`
- Modify: `resources/js/single_pages/projektraum.ts`
- Modify: moderation view templates (find via `grep` for the project space detail in moderation)

**Interfaces:**
- Consumes: `POST /api/project-space/{id}/header-image`, `DELETE /api/project-space/{id}/header-image` from Task 6, `headerImageUrl` from Task 4

- [ ] **Step 1: Add hero image section to project space detail page**

In `public/packages/inno_frontend/single_pages/projektraum/view.php`, add a hero image banner at the top of the detail view. If the user is the initiator/moderator/admin, show an edit pencil overlay.

```php
<?php if (!empty($headerImageUrl)): ?>
    <div class="projektraum-hero">
        <img src="<?= htmlspecialchars($headerImageUrl) ?>" alt="" class="projektraum-hero__image">
        <?php if ($canManage): ?>
            <button class="projektraum-hero__edit" id="header-image-edit" aria-label="<?= t('Titelbild ändern') ?>">
                <!-- pencil SVG icon -->
            </button>
        <?php endif; ?>
    </div>
<?php elseif ($canManage): ?>
    <button class="projektraum-hero__add" id="header-image-add"><?= t('Titelbild hinzufügen') ?></button>
<?php endif; ?>
```

- [ ] **Step 2: Add TypeScript for editing the header image**

In `resources/js/single_pages/projektraum.ts`, add:
- Click handler on edit/add button → opens file picker
- Same validation as idea form (type, size, width)
- Upload via `POST /api/project-space/{id}/header-image` with FormData
- Update the hero image `src` on success
- Option to remove via `DELETE /api/project-space/{id}/header-image`

- [ ] **Step 3: Add hero image section to moderation space view**

Find the moderation template that shows project space details. Add a "Titelbild ändern" button that uses the same upload flow.

- [ ] **Step 4: Add SCSS for the hero image**

```scss
.projektraum-hero {
  position: relative;
  border-radius: var(--radius-lg, 20px);
  overflow: hidden;
  aspect-ratio: 390 / 225;
  margin-bottom: var(--space-6);

  &__image {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  &__edit {
    position: absolute;
    top: var(--space-3);
    right: var(--space-3);
    // white circle with pencil icon
  }
}
```

- [ ] **Step 5: Build and verify**

```bash
npm run development
npm run lint:all
composer lint:php
```

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_frontend/ resources/js/single_pages/ resources/css/single_pages/
git commit -m "feat: header image display and editing on detail page and moderation"
```
