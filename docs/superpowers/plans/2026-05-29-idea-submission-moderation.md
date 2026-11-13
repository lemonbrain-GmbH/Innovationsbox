# Idea Submission & Moderation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enable users to submit project ideas with rich text and file attachments, see their submission status, and allow moderators to review/approve/reject ideas through a dedicated UI.

**Architecture:** Extends existing hexagonal `inno_project_idea` and `inno_moderation` packages. Fills critical gaps (stub repository, missing DB table), adds new domain behavior (edit, delete, attachments, overrides), and builds 4 frontend single pages following the `projektraum.ts` pattern.

**Tech Stack:** PHP 8.1+ (Concrete CMS 9.4), TypeScript, Pest (unit tests), Doctrine DBAL, CKEditor 4, CMS File Manager, Laravel Mix/webpack

---

## File Map

### Backend — `inno_project_idea`

| Action | File |
|--------|------|
| Modify | `public/packages/inno_project_idea/src/Domain/Model/ProjectIdea.php` — add edit(), reject(reason), attachments, rejectionReason, projectSpaceId |
| Create | `public/packages/inno_project_idea/src/Domain/Model/IdeaAttachment.php` — value object |
| Create | `public/packages/inno_project_idea/src/Domain/Exception/IdeaNotEditableException.php` |
| Create | `public/packages/inno_project_idea/src/Domain/Exception/InvalidAttachmentException.php` |
| Create | `public/packages/inno_project_idea/src/Application/UseCase/EditProjectIdea/EditProjectIdeaCommand.php` |
| Create | `public/packages/inno_project_idea/src/Application/UseCase/EditProjectIdea/EditProjectIdeaHandler.php` |
| Create | `public/packages/inno_project_idea/src/Port/In/EditProjectIdeaPort.php` |
| Create | `public/packages/inno_project_idea/src/Application/UseCase/DeleteProjectIdea/DeleteProjectIdeaCommand.php` |
| Create | `public/packages/inno_project_idea/src/Application/UseCase/DeleteProjectIdea/DeleteProjectIdeaHandler.php` |
| Create | `public/packages/inno_project_idea/src/Port/In/DeleteProjectIdeaPort.php` |
| Create | `public/packages/inno_project_idea/src/Application/EventHandler/OnProjectSpaceCreated.php` |
| Modify | `public/packages/inno_project_idea/src/Application/EventHandler/OnModerationDecisionMade.php` — apply overrides + rejection reason |
| Modify | `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php` — mine/update/delete endpoints, file uploads, exception handler |
| Modify | `public/packages/inno_project_idea/src/Adapter/Out/Persistence/ConcreteProjectIdeaRepository.php` — new columns + attachments |
| Modify | `public/packages/inno_project_idea/src/Port/Out/ProjectIdeaRepositoryInterface.php` — add findBySubmitterId, delete |
| Modify | `public/packages/inno_project_idea/controller.php` — new routes, DI, DB migration, event listener |

### Backend — `inno_moderation`

| Action | File |
|--------|------|
| Modify | `public/packages/inno_moderation/src/Domain/Model/ModerationReview.php` — add overrides, reconstitute |
| Modify | `public/packages/inno_moderation/src/Adapter/Out/Persistence/ConcreteModerationReviewRepository.php` — full implementation |
| Modify | `public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php` — show endpoint, overrides, exception handler |
| Modify | `public/packages/inno_moderation/src/Application/UseCase/ReviewProjectIdea/ReviewProjectIdeaCommand.php` — add override fields |
| Modify | `public/packages/inno_moderation/src/Application/UseCase/ReviewProjectIdea/ReviewProjectIdeaHandler.php` — pass overrides to event |
| Modify | `public/packages/inno_moderation/controller.php` — create ib_moderation_reviews table, new route |

### Shared Events

| Action | File |
|--------|------|
| Modify | `public/packages/inno_shared/src/Event/ModerationDecisionMade.php` — add override fields |
| Modify | `public/packages/inno_shared/src/Event/ProjectIdeaApproved.php` — add title, description |

### Frontend

| Action | File |
|--------|------|
| Create | `public/packages/inno_frontend/single_pages/projektidee/einreichen.php` |
| Create | `public/packages/inno_frontend/single_pages/projektidee/meine.php` |
| Create | `public/packages/inno_frontend/single_pages/moderation/view.php` |
| Create | `public/packages/inno_frontend/single_pages/moderation/detail.php` |
| Create | `resources/js/single_pages/projektidee-einreichen.ts` |
| Create | `resources/js/single_pages/projektidee-meine.ts` |
| Create | `resources/js/single_pages/moderation.ts` |
| Create | `resources/js/single_pages/moderation-detail.ts` |
| Create | `resources/css/single_pages/projektidee-einreichen.scss` |
| Create | `resources/css/single_pages/projektidee-meine.scss` |
| Create | `resources/css/single_pages/moderation.scss` |
| Create | `resources/css/single_pages/moderation-detail.scss` |

### Tests

| Action | File |
|--------|------|
| Modify | `tests/unit/inno_project_idea/Domain/ProjectIdeaTest.php` — edit, attachments, rejectionReason, projectSpaceId |
| Create | `tests/unit/inno_project_idea/Domain/IdeaAttachmentTest.php` |
| Create | `tests/unit/inno_project_idea/Application/EditProjectIdeaHandlerTest.php` |
| Create | `tests/unit/inno_project_idea/Application/DeleteProjectIdeaHandlerTest.php` |
| Modify | `tests/unit/inno_moderation/Domain/ModerationReviewTest.php` — overrides |
| Modify | `tests/unit/inno_moderation/Application/ReviewProjectIdeaHandlerTest.php` — overrides |

---

## Task 1: IdeaAttachment Value Object + Tests

**Files:**
- Create: `public/packages/inno_project_idea/src/Domain/Model/IdeaAttachment.php`
- Create: `public/packages/inno_project_idea/src/Domain/Exception/InvalidAttachmentException.php`
- Create: `tests/unit/inno_project_idea/Domain/IdeaAttachmentTest.php`

- [ ] **Step 1: Write IdeaAttachment tests**

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Domain\Exception\InvalidAttachmentException;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaAttachment;

test('create() builds attachment with valid image', function () {
    $att = IdeaAttachment::create('att_1', 'photo.jpg', 42, 'image/jpeg', 2_000_000);
    expect($att->getId())->toBe('att_1')
        ->and($att->getFilename())->toBe('photo.jpg')
        ->and($att->getFileManagerId())->toBe(42)
        ->and($att->getMimeType())->toBe('image/jpeg')
        ->and($att->getSize())->toBe(2_000_000)
        ->and($att->getAddedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('create() accepts PDF', function () {
    $att = IdeaAttachment::create('att_2', 'doc.pdf', 43, 'application/pdf', 1_000_000);
    expect($att->getMimeType())->toBe('application/pdf');
});

test('create() accepts docx', function () {
    $att = IdeaAttachment::create('att_3', 'file.docx', 44, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 500_000);
    expect($att->getMimeType())->toBe('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
});

test('create() rejects invalid MIME type', function () {
    IdeaAttachment::create('att_4', 'virus.exe', 45, 'application/x-msdownload', 100);
})->throws(InvalidAttachmentException::class);

test('create() rejects file exceeding 10MB', function () {
    IdeaAttachment::create('att_5', 'huge.jpg', 46, 'image/jpeg', 11_000_000);
})->throws(InvalidAttachmentException::class);

test('fromPersistence() reconstructs without validation', function () {
    $att = IdeaAttachment::fromPersistence('att_1', 'photo.jpg', 42, 'image/jpeg', 2_000_000, new DateTimeImmutable('2026-01-01'));
    expect($att->getId())->toBe('att_1')
        ->and($att->getAddedAt()->format('Y-m-d'))->toBe('2026-01-01');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/unit/inno_project_idea/Domain/IdeaAttachmentTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Write InvalidAttachmentException**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InvalidAttachmentException extends ValidationException
{
    public static function invalidMimeType(string $mimeType): self
    {
        return new self("Unsupported file type: '{$mimeType}'.", ['field' => 'attachment', 'mimeType' => $mimeType]);
    }

    public static function fileTooLarge(int $size, int $maxSize): self
    {
        $maxMb = $maxSize / 1_000_000;

        return new self("File exceeds maximum size of {$maxMb}MB.", ['field' => 'attachment', 'size' => $size]);
    }

    public static function tooManyAttachments(int $max): self
    {
        return new self("Maximum of {$max} attachments allowed.", ['field' => 'attachment']);
    }

    public function getUserMessage(): string
    {
        return "Die Datei ist ungültig. Erlaubt sind Bilder (JPG, PNG, GIF, WebP), PDF und Office-Dokumente, maximal 10\u{00A0}MB.";
    }
}
```

- [ ] **Step 4: Write IdeaAttachment value object**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Domain\Model;

use Concrete\Package\InnoProjectIdea\Domain\Exception\InvalidAttachmentException;

class IdeaAttachment
{
    private const MAX_SIZE = 10_000_000; // 10MB

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
    ];

    private function __construct(
        private readonly string $id,
        private readonly string $filename,
        private readonly int $fileManagerId,
        private readonly string $mimeType,
        private readonly int $size,
        private readonly \DateTimeImmutable $addedAt,
    ) {}

    public static function create(string $id, string $filename, int $fileManagerId, string $mimeType, int $size): self
    {
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw InvalidAttachmentException::invalidMimeType($mimeType);
        }
        if ($size > self::MAX_SIZE) {
            throw InvalidAttachmentException::fileTooLarge($size, self::MAX_SIZE);
        }

        return new self($id, $filename, $fileManagerId, $mimeType, $size, new \DateTimeImmutable());
    }

    public static function fromPersistence(
        string $id,
        string $filename,
        int $fileManagerId,
        string $mimeType,
        int $size,
        \DateTimeImmutable $addedAt,
    ): self {
        return new self($id, $filename, $fileManagerId, $mimeType, $size, $addedAt);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getFileManagerId(): int
    {
        return $this->fileManagerId;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/unit/inno_project_idea/Domain/IdeaAttachmentTest.php`
Expected: 6 tests PASS

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_project_idea/src/Domain/Model/IdeaAttachment.php \
        public/packages/inno_project_idea/src/Domain/Exception/InvalidAttachmentException.php \
        tests/unit/inno_project_idea/Domain/IdeaAttachmentTest.php
git commit -m "feat(project-idea): add IdeaAttachment value object with MIME/size validation"
```

---

## Task 2: Extend ProjectIdea Domain — Edit, Attachments, Rejection Reason, Space ID

**Files:**
- Modify: `public/packages/inno_project_idea/src/Domain/Model/ProjectIdea.php`
- Create: `public/packages/inno_project_idea/src/Domain/Exception/IdeaNotEditableException.php`
- Modify: `tests/unit/inno_project_idea/Domain/ProjectIdeaTest.php`

- [ ] **Step 1: Write new domain tests**

Append to `tests/unit/inno_project_idea/Domain/ProjectIdeaTest.php`:

```php
use Concrete\Package\InnoProjectIdea\Domain\Exception\IdeaNotEditableException;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaAttachment;

test('edit() updates title, description, category when status is Submitted', function () {
    $idea = ProjectIdea::submit('id_1', 'Old Title', 'Old Desc', 'cat_1', 'user_1');
    $idea->edit('New Title', 'New Desc', 'cat_2');
    expect($idea->getTitle())->toBe('New Title')
        ->and($idea->getDescription())->toBe('New Desc')
        ->and($idea->getCategory())->toBe('cat_2');
});

test('edit() throws IdeaNotEditableException when status is Approved', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->approve();
    $idea->edit('X', 'Y', 'cat_2');
})->throws(IdeaNotEditableException::class);

test('edit() throws IdeaNotEditableException when status is Rejected', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->reject('Not relevant');
    $idea->edit('X', 'Y', 'cat_2');
})->throws(IdeaNotEditableException::class);

test('addAttachment() adds to collection', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $att = IdeaAttachment::create('att_1', 'photo.jpg', 42, 'image/jpeg', 1_000_000);
    $idea->addAttachment($att);
    expect($idea->getAttachments())->toHaveCount(1)
        ->and($idea->getAttachments()[0]->getId())->toBe('att_1');
});

test('addAttachment() throws when 5 attachments reached', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    for ($i = 0; $i < 5; $i++) {
        $idea->addAttachment(IdeaAttachment::create("att_{$i}", "f{$i}.jpg", $i + 1, 'image/jpeg', 100));
    }
    $idea->addAttachment(IdeaAttachment::create('att_6', 'extra.jpg', 99, 'image/jpeg', 100));
})->throws(\Concrete\Package\InnoProjectIdea\Domain\Exception\InvalidAttachmentException::class);

test('addAttachment() throws when idea is not editable', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->approve();
    $att = IdeaAttachment::create('att_1', 'photo.jpg', 42, 'image/jpeg', 100);
    $idea->addAttachment($att);
})->throws(IdeaNotEditableException::class);

test('removeAttachment() removes by id', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->addAttachment(IdeaAttachment::create('att_1', 'a.jpg', 1, 'image/jpeg', 100));
    $idea->addAttachment(IdeaAttachment::create('att_2', 'b.jpg', 2, 'image/jpeg', 100));
    $idea->removeAttachment('att_1');
    expect($idea->getAttachments())->toHaveCount(1)
        ->and($idea->getAttachments()[0]->getId())->toBe('att_2');
});

test('reject() stores rejection reason', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->reject('Does not meet criteria');
    expect($idea->getStatus())->toBe(IdeaStatus::Rejected)
        ->and($idea->getRejectionReason())->toBe('Does not meet criteria');
});

test('setProjectSpaceId() stores the space reference', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Desc', 'cat_1', 'user_1');
    $idea->approve();
    $idea->setProjectSpaceId('space_123');
    expect($idea->getProjectSpaceId())->toBe('space_123');
});

test('reconstitute() restores all fields including new ones', function () {
    $att = IdeaAttachment::fromPersistence('att_1', 'f.jpg', 1, 'image/jpeg', 100, new DateTimeImmutable());
    $idea = ProjectIdea::reconstitute(
        id: 'id_1',
        title: 'T',
        description: 'D',
        category: 'c',
        status: IdeaStatus::Rejected,
        submitterId: 1,
        createdAt: new DateTimeImmutable(),
        updatedAt: new DateTimeImmutable(),
        rejectionReason: 'Bad idea',
        projectSpaceId: null,
        attachments: [$att],
    );
    expect($idea->getRejectionReason())->toBe('Bad idea')
        ->and($idea->getProjectSpaceId())->toBeNull()
        ->and($idea->getAttachments())->toHaveCount(1);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/unit/inno_project_idea/Domain/ProjectIdeaTest.php`
Expected: FAIL — methods not found

- [ ] **Step 3: Write IdeaNotEditableException**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class IdeaNotEditableException extends ValidationException
{
    public static function withStatus(string $status): self
    {
        return new self("Idea cannot be edited in status '{$status}'.", ['status' => $status]);
    }

    public function getUserMessage(): string
    {
        return 'Diese Projektidee kann nicht mehr bearbeitet werden.';
    }
}
```

- [ ] **Step 4: Extend ProjectIdea model**

Replace `public/packages/inno_project_idea/src/Domain/Model/ProjectIdea.php` with:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Domain\Model;

use Concrete\Package\InnoProjectIdea\Domain\Exception\IdeaNotEditableException;
use Concrete\Package\InnoProjectIdea\Domain\Exception\InvalidAttachmentException;

class ProjectIdea
{
    private const MAX_ATTACHMENTS = 5;

    /** @var IdeaAttachment[] */
    private array $attachments;

    /**
     * @param IdeaAttachment[] $attachments
     */
    private function __construct(
        private readonly string $id,
        private string $title,
        private string $description,
        private string $category,
        private IdeaStatus $status,
        private readonly string $submitterId,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        private ?string $rejectionReason = null,
        private ?string $projectSpaceId = null,
        array $attachments = [],
    ) {
        $this->attachments = $attachments;
    }

    public static function submit(
        string $id,
        string $title,
        string $description,
        string $category,
        string $submitterId,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            category: $category,
            status: IdeaStatus::Submitted,
            submitterId: $submitterId,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function edit(string $title, string $description, string $category): void
    {
        $this->guardEditable();
        $this->title = $title;
        $this->description = $description;
        $this->category = $category;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function approve(): void
    {
        $this->status = IdeaStatus::Approved;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function reject(string $reason = ''): void
    {
        $this->status = IdeaStatus::Rejected;
        $this->rejectionReason = $reason;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addAttachment(IdeaAttachment $attachment): void
    {
        $this->guardEditable();
        if (count($this->attachments) >= self::MAX_ATTACHMENTS) {
            throw InvalidAttachmentException::tooManyAttachments(self::MAX_ATTACHMENTS);
        }
        $this->attachments[] = $attachment;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function removeAttachment(string $attachmentId): void
    {
        $this->guardEditable();
        $this->attachments = array_values(array_filter(
            $this->attachments,
            fn(IdeaAttachment $a) => $a->getId() !== $attachmentId,
        ));
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setProjectSpaceId(string $spaceId): void
    {
        $this->projectSpaceId = $spaceId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function guardEditable(): void
    {
        if ($this->status !== IdeaStatus::Submitted) {
            throw IdeaNotEditableException::withStatus($this->status->value);
        }
    }

    // --- Getters ---

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getStatus(): IdeaStatus
    {
        return $this->status;
    }

    public function getSubmitterId(): string
    {
        return $this->submitterId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getProjectSpaceId(): ?string
    {
        return $this->projectSpaceId;
    }

    /** @return IdeaAttachment[] */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @param IdeaAttachment[] $attachments
     */
    public static function reconstitute(
        string $id,
        string $title,
        ?string $description,
        string $category,
        IdeaStatus $status,
        ?int $submitterId,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?string $rejectionReason = null,
        ?string $projectSpaceId = null,
        array $attachments = [],
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description ?? '',
            category: $category,
            status: $status,
            submitterId: $submitterId !== null ? (string) $submitterId : '',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            rejectionReason: $rejectionReason,
            projectSpaceId: $projectSpaceId,
            attachments: $attachments,
        );
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/unit/inno_project_idea/Domain/`
Expected: All PASS (original 3 + new 10)

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_project_idea/src/Domain/ \
        tests/unit/inno_project_idea/Domain/
git commit -m "feat(project-idea): extend ProjectIdea with edit, attachments, rejectionReason, projectSpaceId"
```

---

## Task 3: Extend ModerationReview Domain — Overrides + Reconstitute

**Files:**
- Modify: `public/packages/inno_moderation/src/Domain/Model/ModerationReview.php`
- Modify: `tests/unit/inno_moderation/Domain/ModerationReviewTest.php`

- [ ] **Step 1: Write new tests**

Append to `tests/unit/inno_moderation/Domain/ModerationReviewTest.php`:

```php
test('approve() with overrides stores them', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $review->approve('reviewer_1', 'Looks great', 'Better Title', 'Better description', 'cat_2');
    expect($review->getDecision())->toBe(ModerationDecision::Approved)
        ->and($review->getTitleOverride())->toBe('Better Title')
        ->and($review->getDescriptionOverride())->toBe('Better description')
        ->and($review->getCategoryOverride())->toBe('cat_2');
});

test('approve() without overrides leaves them null', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $review->approve('reviewer_1', 'OK');
    expect($review->getTitleOverride())->toBeNull()
        ->and($review->getDescriptionOverride())->toBeNull()
        ->and($review->getCategoryOverride())->toBeNull();
});

test('reconstitute() restores all fields', function () {
    $review = ModerationReview::reconstitute(
        id: 'review_1',
        ideaId: 'idea_1',
        decision: ModerationDecision::Approved,
        reviewerId: 'mod_1',
        notes: 'Good',
        createdAt: new DateTimeImmutable('2026-01-01'),
        reviewedAt: new DateTimeImmutable('2026-01-02'),
        titleOverride: 'New Title',
        descriptionOverride: 'New Desc',
        categoryOverride: 'cat_2',
    );
    expect($review->getId())->toBe('review_1')
        ->and($review->getTitleOverride())->toBe('New Title')
        ->and($review->getDescriptionOverride())->toBe('New Desc')
        ->and($review->getCategoryOverride())->toBe('cat_2');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/unit/inno_moderation/Domain/ModerationReviewTest.php`
Expected: FAIL

- [ ] **Step 3: Extend ModerationReview**

Replace `public/packages/inno_moderation/src/Domain/Model/ModerationReview.php` with:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Domain\Model;

class ModerationReview
{
    private function __construct(
        private readonly string $id,
        private readonly string $ideaId,
        private ModerationDecision $decision,
        private ?string $reviewerId,
        private string $notes,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $reviewedAt,
        private ?string $titleOverride = null,
        private ?string $descriptionOverride = null,
        private ?string $categoryOverride = null,
    ) {}

    public static function createPending(string $id, string $ideaId): self
    {
        return new self(
            id: $id,
            ideaId: $ideaId,
            decision: ModerationDecision::Pending,
            reviewerId: null,
            notes: '',
            createdAt: new \DateTimeImmutable(),
            reviewedAt: null,
        );
    }

    public static function reconstitute(
        string $id,
        string $ideaId,
        ModerationDecision $decision,
        ?string $reviewerId,
        string $notes,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $reviewedAt,
        ?string $titleOverride = null,
        ?string $descriptionOverride = null,
        ?string $categoryOverride = null,
    ): self {
        return new self($id, $ideaId, $decision, $reviewerId, $notes, $createdAt, $reviewedAt, $titleOverride, $descriptionOverride, $categoryOverride);
    }

    public function approve(
        string $reviewerId,
        string $notes = '',
        ?string $titleOverride = null,
        ?string $descriptionOverride = null,
        ?string $categoryOverride = null,
    ): void {
        $this->decision = ModerationDecision::Approved;
        $this->reviewerId = $reviewerId;
        $this->notes = $notes;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->titleOverride = $titleOverride;
        $this->descriptionOverride = $descriptionOverride;
        $this->categoryOverride = $categoryOverride;
    }

    public function reject(string $reviewerId, string $notes): void
    {
        $this->decision = ModerationDecision::Rejected;
        $this->reviewerId = $reviewerId;
        $this->notes = $notes;
        $this->reviewedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getIdeaId(): string
    {
        return $this->ideaId;
    }

    public function getDecision(): ModerationDecision
    {
        return $this->decision;
    }

    public function getReviewerId(): ?string
    {
        return $this->reviewerId;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function getTitleOverride(): ?string
    {
        return $this->titleOverride;
    }

    public function getDescriptionOverride(): ?string
    {
        return $this->descriptionOverride;
    }

    public function getCategoryOverride(): ?string
    {
        return $this->categoryOverride;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/unit/inno_moderation/Domain/ModerationReviewTest.php`
Expected: 6 tests PASS

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_moderation/src/Domain/Model/ModerationReview.php \
        tests/unit/inno_moderation/Domain/ModerationReviewTest.php
git commit -m "feat(moderation): extend ModerationReview with approval overrides and reconstitute"
```

---

## Task 4: Extend Shared Events — Overrides on ModerationDecisionMade + title/description on ProjectIdeaApproved

**Files:**
- Modify: `public/packages/inno_shared/src/Event/ModerationDecisionMade.php`
- Modify: `public/packages/inno_shared/src/Event/ProjectIdeaApproved.php`

- [ ] **Step 1: Extend ModerationDecisionMade**

Replace `public/packages/inno_shared/src/Event/ModerationDecisionMade.php` with:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a moderator approves or rejects a project idea.
 * Consumer: project_idea (updates idea status and re-publishes Approved/Rejected event).
 */
class ModerationDecisionMade extends Event
{
    public const NAME = 'innovationsbox.moderation.decision_made';

    public function __construct(
        private readonly string $ideaId,
        private readonly string $reviewerId,
        private readonly bool $approved,
        private readonly string $reason,
        private readonly ?string $titleOverride = null,
        private readonly ?string $descriptionOverride = null,
        private readonly ?string $categoryOverride = null,
    ) {}

    public function getIdeaId(): string
    {
        return $this->ideaId;
    }

    public function getReviewerId(): string
    {
        return $this->reviewerId;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getTitleOverride(): ?string
    {
        return $this->titleOverride;
    }

    public function getDescriptionOverride(): ?string
    {
        return $this->descriptionOverride;
    }

    public function getCategoryOverride(): ?string
    {
        return $this->categoryOverride;
    }
}
```

- [ ] **Step 2: Extend ProjectIdeaApproved**

Replace `public/packages/inno_shared/src/Event/ProjectIdeaApproved.php` with:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a moderation decision approves a project idea.
 * Consumers: project_space (creates a space), notification (notifies submitter).
 */
class ProjectIdeaApproved extends Event
{
    public const NAME = 'innovationsbox.project_idea.approved';

    public function __construct(
        private readonly string $ideaId,
        private readonly string $submitterId,
        private readonly string $categoryId = '',
        private readonly string $title = '',
        private readonly string $description = '',
    ) {}

    public function getIdeaId(): string
    {
        return $this->ideaId;
    }

    public function getSubmitterId(): string
    {
        return $this->submitterId;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
```

- [ ] **Step 3: Run all tests to verify nothing broke**

Run: `./vendor/bin/pest`
Expected: All PASS (existing event constructors are backwards-compatible via defaults)

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_shared/src/Event/ModerationDecisionMade.php \
        public/packages/inno_shared/src/Event/ProjectIdeaApproved.php
git commit -m "feat(shared): extend events with moderator overrides and idea title/description"
```

---

## Task 5: Implement ConcreteModerationReviewRepository + Create DB Table

**Files:**
- Modify: `public/packages/inno_moderation/src/Adapter/Out/Persistence/ConcreteModerationReviewRepository.php`
- Modify: `public/packages/inno_moderation/controller.php`

- [ ] **Step 1: Implement the repository**

Replace `public/packages/inno_moderation/src/Adapter/Out/Persistence/ConcreteModerationReviewRepository.php` with:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;

class ConcreteModerationReviewRepository implements ModerationReviewRepositoryInterface
{
    private const TABLE = 'ib_moderation_reviews';

    public function __construct(private readonly Connection $db) {}

    public function findById(string $id): ?ModerationReview
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE . ' WHERE id = ?', [$id]);

        return $row !== false ? $this->hydrate($row) : null;
    }

    public function findByIdeaId(string $ideaId): ?ModerationReview
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE . ' WHERE idea_id = ?', [$ideaId]);

        return $row !== false ? $this->hydrate($row) : null;
    }

    /** @return ModerationReview[] */
    public function findByDecision(ModerationDecision $decision): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . self::TABLE . ' WHERE decision = ? ORDER BY created_at ASC',
            [$decision->value],
        );

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function save(ModerationReview $review): void
    {
        $data = [
            'id' => $review->getId(),
            'idea_id' => $review->getIdeaId(),
            'decision' => $review->getDecision()->value,
            'reviewer_id' => $review->getReviewerId(),
            'notes' => $review->getNotes(),
            'title_override' => $review->getTitleOverride(),
            'description_override' => $review->getDescriptionOverride(),
            'category_override' => $review->getCategoryOverride(),
            'created_at' => $review->getCreatedAt()->format('Y-m-d H:i:s'),
            'reviewed_at' => $review->getReviewedAt()?->format('Y-m-d H:i:s'),
        ];

        $exists = $this->db->fetchOne('SELECT 1 FROM ' . self::TABLE . ' WHERE id = ?', [$review->getId()]);
        if ($exists !== false) {
            $this->db->update(self::TABLE, $data, ['id' => $review->getId()]);
        } else {
            $this->db->insert(self::TABLE, $data);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ModerationReview
    {
        return ModerationReview::reconstitute(
            id: $row['id'],
            ideaId: $row['idea_id'],
            decision: ModerationDecision::from($row['decision']),
            reviewerId: $row['reviewer_id'],
            notes: $row['notes'] ?? '',
            createdAt: new \DateTimeImmutable($row['created_at']),
            reviewedAt: $row['reviewed_at'] !== null ? new \DateTimeImmutable($row['reviewed_at']) : null,
            titleOverride: $row['title_override'],
            descriptionOverride: $row['description_override'],
            categoryOverride: $row['category_override'],
        );
    }
}
```

- [ ] **Step 2: Add ib_moderation_reviews table creation to controller.php install()**

In `public/packages/inno_moderation/controller.php`, replace the `// TODO: install database tables for ModerationReview` comment with:

```php
        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_moderation_reviews (
                id VARCHAR(64) NOT NULL,
                idea_id VARCHAR(64) NOT NULL,
                decision VARCHAR(20) NOT NULL DEFAULT \'pending\',
                reviewer_id VARCHAR(64) NULL,
                notes TEXT NULL,
                title_override VARCHAR(255) NULL,
                description_override TEXT NULL,
                category_override VARCHAR(64) NULL,
                created_at DATETIME NOT NULL,
                reviewed_at DATETIME NULL,
                PRIMARY KEY (id),
                INDEX idx_idea_id (idea_id),
                INDEX idx_decision (decision)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
```

- [ ] **Step 3: Add same table creation to upgrade() method**

Add the same SQL to the `upgrade()` method, wrapped in a `tablesExist` check (same pattern as `ib_categories`):

```php
        if (!$sm->tablesExist(['ib_moderation_reviews'])) {
            $db->executeStatement('
                CREATE TABLE IF NOT EXISTS ib_moderation_reviews (
                    id VARCHAR(64) NOT NULL,
                    idea_id VARCHAR(64) NOT NULL,
                    decision VARCHAR(20) NOT NULL DEFAULT \'pending\',
                    reviewer_id VARCHAR(64) NULL,
                    notes TEXT NULL,
                    title_override VARCHAR(255) NULL,
                    description_override TEXT NULL,
                    category_override VARCHAR(64) NULL,
                    created_at DATETIME NOT NULL,
                    reviewed_at DATETIME NULL,
                    PRIMARY KEY (id),
                    INDEX idx_idea_id (idea_id),
                    INDEX idx_decision (decision)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');
        }
```

- [ ] **Step 4: Run all tests**

Run: `./vendor/bin/pest`
Expected: All PASS

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_moderation/src/Adapter/Out/Persistence/ConcreteModerationReviewRepository.php \
        public/packages/inno_moderation/controller.php
git commit -m "feat(moderation): implement ConcreteModerationReviewRepository and create ib_moderation_reviews table"
```

---

## Task 6: Update ReviewProjectIdea Use Case — Pass Overrides Through

**Files:**
- Modify: `public/packages/inno_moderation/src/Application/UseCase/ReviewProjectIdea/ReviewProjectIdeaCommand.php`
- Modify: `public/packages/inno_moderation/src/Application/UseCase/ReviewProjectIdea/ReviewProjectIdeaHandler.php`
- Modify: `tests/unit/inno_moderation/Application/ReviewProjectIdeaHandlerTest.php`

- [ ] **Step 1: Write test for approval with overrides**

Append to `tests/unit/inno_moderation/Application/ReviewProjectIdeaHandlerTest.php`:

```php
test('handle() passes overrides through ModerationDecisionMade event on approval', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $repo = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('review_1')->andReturn($review);
    $repo->shouldReceive('save')->once();
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(
            Mockery::on(fn($e) => $e instanceof ModerationDecisionMade
                && $e->isApproved()
                && $e->getTitleOverride() === 'Better Title'
                && $e->getDescriptionOverride() === 'Better Desc'
                && $e->getCategoryOverride() === 'cat_2'),
            ModerationDecisionMade::NAME
        );
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new ReviewProjectIdeaHandler($repo, $dispatcher, $transaction, $logger);
    $handler->handle(new ReviewProjectIdeaCommand(
        reviewId: 'review_1',
        reviewerId: 'mod_1',
        approved: true,
        notes: 'Approved with changes',
        titleOverride: 'Better Title',
        descriptionOverride: 'Better Desc',
        categoryOverride: 'cat_2',
    ));
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/unit/inno_moderation/Application/ReviewProjectIdeaHandlerTest.php`
Expected: FAIL

- [ ] **Step 3: Extend ReviewProjectIdeaCommand**

Replace `public/packages/inno_moderation/src/Application/UseCase/ReviewProjectIdea/ReviewProjectIdeaCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea;

final class ReviewProjectIdeaCommand
{
    public function __construct(
        public readonly string $reviewId,
        public readonly string $reviewerId,
        public readonly bool $approved,
        public readonly string $notes,
        public readonly ?string $titleOverride = null,
        public readonly ?string $descriptionOverride = null,
        public readonly ?string $categoryOverride = null,
    ) {}
}
```

- [ ] **Step 4: Update ReviewProjectIdeaHandler to pass overrides**

Replace `public/packages/inno_moderation/src/Application/UseCase/ReviewProjectIdea/ReviewProjectIdeaHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea;

use Concrete\Package\InnoModeration\Domain\Exception\ModerationReviewNotFoundException;
use Concrete\Package\InnoModeration\Port\In\ReviewProjectIdeaPort;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ReviewProjectIdeaHandler implements ReviewProjectIdeaPort
{
    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ReviewProjectIdeaCommand $command): void
    {
        $this->transaction->transactional(function () use ($command) {
            $review = $this->repository->findById($command->reviewId);
            if ($review === null) {
                throw ModerationReviewNotFoundException::withId($command->reviewId);
            }

            if ($command->approved) {
                $review->approve(
                    $command->reviewerId,
                    $command->notes,
                    $command->titleOverride,
                    $command->descriptionOverride,
                    $command->categoryOverride,
                );
            } else {
                $review->reject($command->reviewerId, $command->notes);
            }

            $this->repository->save($review);

            $this->eventDispatcher->dispatch(
                new ModerationDecisionMade(
                    ideaId: $review->getIdeaId(),
                    reviewerId: $command->reviewerId,
                    approved: $command->approved,
                    reason: $command->notes,
                    titleOverride: $command->titleOverride,
                    descriptionOverride: $command->descriptionOverride,
                    categoryOverride: $command->categoryOverride,
                ),
                ModerationDecisionMade::NAME,
            );

            $this->logger->info('Moderation decision made', [
                'reviewId' => $command->reviewId,
                'approved' => $command->approved,
                'reviewerId' => $command->reviewerId,
            ]);
        });
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/unit/inno_moderation/Application/ReviewProjectIdeaHandlerTest.php`
Expected: 3 tests PASS

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_moderation/src/Application/UseCase/ReviewProjectIdea/ \
        tests/unit/inno_moderation/Application/ReviewProjectIdeaHandlerTest.php
git commit -m "feat(moderation): pass moderator overrides through ReviewProjectIdea use case"
```

---

## Task 7: Update OnModerationDecisionMade Event Handler — Apply Overrides + Store Rejection Reason

**Files:**
- Modify: `public/packages/inno_project_idea/src/Application/EventHandler/OnModerationDecisionMade.php`

- [ ] **Step 1: Update OnModerationDecisionMade**

Replace `public/packages/inno_project_idea/src/Application/EventHandler/OnModerationDecisionMade.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\EventHandler;

use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OnModerationDecisionMade
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly TransactionInterface $transaction,
    ) {}

    public function handle(ModerationDecisionMade $event): void
    {
        $idea = $this->repository->findById($event->getIdeaId());
        if ($idea === null) {
            $this->logger->warning('Idea not found for moderation decision', [
                'ideaId' => $event->getIdeaId(),
            ]);

            return;
        }

        $this->transaction->transactional(function () use ($event, $idea) {
            if ($event->isApproved()) {
                // Apply moderator overrides before approving
                if ($event->getTitleOverride() !== null || $event->getDescriptionOverride() !== null || $event->getCategoryOverride() !== null) {
                    // Force edit even though status may not be Submitted — use internal setter
                    $idea->applyOverrides(
                        $event->getTitleOverride(),
                        $event->getDescriptionOverride(),
                        $event->getCategoryOverride(),
                    );
                }
                $idea->approve();
                $this->repository->save($idea);
                $this->eventDispatcher->dispatch(
                    new ProjectIdeaApproved(
                        $idea->getId(),
                        $idea->getSubmitterId(),
                        $idea->getCategory(),
                        $idea->getTitle(),
                        $idea->getDescription(),
                    ),
                    ProjectIdeaApproved::NAME,
                );
                $this->logger->info('Project idea approved', ['ideaId' => $idea->getId()]);
            } else {
                $idea->reject($event->getReason());
                $this->repository->save($idea);
                $this->eventDispatcher->dispatch(
                    new ProjectIdeaRejected($idea->getId(), $idea->getSubmitterId(), $event->getReason()),
                    ProjectIdeaRejected::NAME,
                );
                $this->logger->info('Project idea rejected', ['ideaId' => $idea->getId()]);
            }
        });
    }
}
```

- [ ] **Step 2: Add applyOverrides() method to ProjectIdea**

Add this method to `public/packages/inno_project_idea/src/Domain/Model/ProjectIdea.php` (after the `edit()` method):

```php
    /**
     * Apply moderator overrides before approval (bypasses status guard).
     */
    public function applyOverrides(?string $title, ?string $description, ?string $category): void
    {
        if ($title !== null) {
            $this->title = $title;
        }
        if ($description !== null) {
            $this->description = $description;
        }
        if ($category !== null) {
            $this->category = $category;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }
```

- [ ] **Step 3: Run all tests**

Run: `./vendor/bin/pest`
Expected: All PASS

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_project_idea/src/Application/EventHandler/OnModerationDecisionMade.php \
        public/packages/inno_project_idea/src/Domain/Model/ProjectIdea.php
git commit -m "feat(project-idea): apply moderator overrides and store rejection reason in OnModerationDecisionMade"
```

---

## Task 8: Add OnProjectSpaceCreated Event Handler + Edit/Delete Use Cases

**Files:**
- Create: `public/packages/inno_project_idea/src/Application/EventHandler/OnProjectSpaceCreated.php`
- Create: `public/packages/inno_project_idea/src/Application/UseCase/EditProjectIdea/EditProjectIdeaCommand.php`
- Create: `public/packages/inno_project_idea/src/Application/UseCase/EditProjectIdea/EditProjectIdeaHandler.php`
- Create: `public/packages/inno_project_idea/src/Port/In/EditProjectIdeaPort.php`
- Create: `public/packages/inno_project_idea/src/Application/UseCase/DeleteProjectIdea/DeleteProjectIdeaCommand.php`
- Create: `public/packages/inno_project_idea/src/Application/UseCase/DeleteProjectIdea/DeleteProjectIdeaHandler.php`
- Create: `public/packages/inno_project_idea/src/Port/In/DeleteProjectIdeaPort.php`
- Create: `tests/unit/inno_project_idea/Application/EditProjectIdeaHandlerTest.php`
- Create: `tests/unit/inno_project_idea/Application/DeleteProjectIdeaHandlerTest.php`

- [ ] **Step 1: Write EditProjectIdeaHandlerTest**

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea\EditProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea\EditProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Domain\Exception\IdeaNotEditableException;
use Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

afterEach(fn() => Mockery::close());

test('handle() edits a submitted idea', function () {
    $idea = ProjectIdea::submit('idea_1', 'Old', 'Old desc', 'cat_1', 'user_1');
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('idea_1')->andReturn($idea);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->andReturn(false);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new EditProjectIdeaHandler($repo, $auth, $transaction, $logger);
    $handler->handle(new EditProjectIdeaCommand('idea_1', 'New Title', 'New desc', 'cat_2'));

    expect($idea->getTitle())->toBe('New Title');
});

test('handle() throws when idea not found', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('missing')->andReturn(null);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new EditProjectIdeaHandler($repo, $auth, $transaction, $logger);
    $handler->handle(new EditProjectIdeaCommand('missing', 'T', 'D', 'C'));
})->throws(ProjectIdeaNotFoundException::class);

test('handle() throws when user is not the owner', function () {
    $idea = ProjectIdea::submit('idea_1', 'T', 'D', 'cat_1', 'user_1');
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('idea_1')->andReturn($idea);
    $auth->shouldReceive('getUserId')->andReturn(999);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('idea.admin')->andReturn(false);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new EditProjectIdeaHandler($repo, $auth, $transaction, $logger);
    $handler->handle(new EditProjectIdeaCommand('idea_1', 'T', 'D', 'C'));
})->throws(AuthException::class);
```

- [ ] **Step 2: Write DeleteProjectIdeaHandlerTest**

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea\DeleteProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea\DeleteProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Domain\Exception\IdeaNotEditableException;
use Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

afterEach(fn() => Mockery::close());

test('handle() deletes a submitted idea', function () {
    $idea = ProjectIdea::submit('idea_1', 'T', 'D', 'cat_1', 'user_1');
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('idea_1')->andReturn($idea);
    $repo->shouldReceive('delete')->once()->with('idea_1');
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->andReturn(false);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new DeleteProjectIdeaHandler($repo, $auth, $transaction, $logger);
    $handler->handle(new DeleteProjectIdeaCommand('idea_1'));
});

test('handle() throws when idea is not submitted (approved)', function () {
    $idea = ProjectIdea::submit('idea_1', 'T', 'D', 'cat_1', 'user_1');
    $idea->approve();
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('idea_1')->andReturn($idea);
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->andReturn(false);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new DeleteProjectIdeaHandler($repo, $auth, $transaction, $logger);
    $handler->handle(new DeleteProjectIdeaCommand('idea_1'));
})->throws(IdeaNotEditableException::class);
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/unit/inno_project_idea/Application/EditProjectIdeaHandlerTest.php tests/unit/inno_project_idea/Application/DeleteProjectIdeaHandlerTest.php`
Expected: FAIL

- [ ] **Step 4: Create the port interfaces**

`public/packages/inno_project_idea/src/Port/In/EditProjectIdeaPort.php`:
```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Port\In;

use Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea\EditProjectIdeaCommand;

interface EditProjectIdeaPort
{
    public function handle(EditProjectIdeaCommand $command): void;
}
```

`public/packages/inno_project_idea/src/Port/In/DeleteProjectIdeaPort.php`:
```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Port\In;

use Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea\DeleteProjectIdeaCommand;

interface DeleteProjectIdeaPort
{
    public function handle(DeleteProjectIdeaCommand $command): void;
}
```

- [ ] **Step 5: Create commands**

`public/packages/inno_project_idea/src/Application/UseCase/EditProjectIdea/EditProjectIdeaCommand.php`:
```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea;

final class EditProjectIdeaCommand
{
    public function __construct(
        public readonly string $ideaId,
        public readonly string $title,
        public readonly string $description,
        public readonly string $category,
    ) {}
}
```

`public/packages/inno_project_idea/src/Application/UseCase/DeleteProjectIdea/DeleteProjectIdeaCommand.php`:
```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea;

final class DeleteProjectIdeaCommand
{
    public function __construct(
        public readonly string $ideaId,
    ) {}
}
```

- [ ] **Step 6: Create handlers**

`public/packages/inno_project_idea/src/Application/UseCase/EditProjectIdea/EditProjectIdeaHandler.php`:
```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea;

use Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Port\In\EditProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

class EditProjectIdeaHandler implements EditProjectIdeaPort
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(EditProjectIdeaCommand $command): void
    {
        $this->transaction->transactional(function () use ($command) {
            $idea = $this->repository->findById($command->ideaId);
            if ($idea === null) {
                throw ProjectIdeaNotFoundException::withId($command->ideaId);
            }

            if ($idea->getSubmitterId() !== (string) $this->auth->getUserId() && !$this->auth->hasPermission('idea.admin')) {
                throw new AuthException('Only the idea owner can edit', [], $this->auth->isAuthenticated());
            }

            $idea->edit($command->title, $command->description, $command->category);
            $this->repository->save($idea);

            $this->logger->info('Project idea edited', ['ideaId' => $command->ideaId]);
        });
    }
}
```

`public/packages/inno_project_idea/src/Application/UseCase/DeleteProjectIdea/DeleteProjectIdeaHandler.php`:
```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea;

use Concrete\Package\InnoProjectIdea\Domain\Exception\IdeaNotEditableException;
use Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Port\In\DeleteProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

class DeleteProjectIdeaHandler implements DeleteProjectIdeaPort
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(DeleteProjectIdeaCommand $command): void
    {
        $this->transaction->transactional(function () use ($command) {
            $idea = $this->repository->findById($command->ideaId);
            if ($idea === null) {
                throw ProjectIdeaNotFoundException::withId($command->ideaId);
            }

            if ($idea->getSubmitterId() !== (string) $this->auth->getUserId() && !$this->auth->hasPermission('idea.admin')) {
                throw new AuthException('Only the idea owner can delete', [], $this->auth->isAuthenticated());
            }

            if ($idea->getStatus() !== IdeaStatus::Submitted) {
                throw IdeaNotEditableException::withStatus($idea->getStatus()->value);
            }

            $this->repository->delete($command->ideaId);
            $this->logger->info('Project idea deleted', ['ideaId' => $command->ideaId]);
        });
    }
}
```

- [ ] **Step 7: Create OnProjectSpaceCreated event handler**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\EventHandler;

use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCreated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

class OnProjectSpaceCreated
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectSpaceCreated $event): void
    {
        $idea = $this->repository->findById($event->getProjectIdeaId());
        if ($idea === null) {
            return;
        }

        $idea->setProjectSpaceId($event->getSpaceId());
        $this->repository->save($idea);

        $this->logger->info('Linked project space to idea', [
            'ideaId' => $event->getProjectIdeaId(),
            'spaceId' => $event->getSpaceId(),
        ]);
    }
}
```

- [ ] **Step 8: Add `delete` method to repository interface**

Add to `public/packages/inno_project_idea/src/Port/Out/ProjectIdeaRepositoryInterface.php`:

```php
    /** @return ProjectIdea[] */
    public function findBySubmitterId(string $submitterId): array;
    public function delete(string $id): void;
```

- [ ] **Step 9: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/unit/inno_project_idea/`
Expected: All PASS

- [ ] **Step 10: Commit**

```bash
git add public/packages/inno_project_idea/src/Application/ \
        public/packages/inno_project_idea/src/Port/ \
        tests/unit/inno_project_idea/Application/
git commit -m "feat(project-idea): add Edit, Delete use cases and OnProjectSpaceCreated event handler"
```

---

## Task 9: Update ProjectIdea Repository + Controller + Package Controller

**Files:**
- Modify: `public/packages/inno_project_idea/src/Adapter/Out/Persistence/ConcreteProjectIdeaRepository.php`
- Modify: `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php`
- Modify: `public/packages/inno_project_idea/controller.php`

- [ ] **Step 1: Update ConcreteProjectIdeaRepository**

Add the new columns to save/hydrate, add `findBySubmitterId`, `delete`, and attachment loading. Replace the file completely:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaAttachment;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;

class ConcreteProjectIdeaRepository implements ProjectIdeaRepositoryInterface
{
    private const TABLE = 'ib_project_ideas';
    private const ATTACHMENTS_TABLE = 'ib_idea_attachments';

    public function __construct(private readonly Connection $db) {}

    public function findById(string $id): ?ProjectIdea
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE . ' WHERE id = ?', [$id]);

        return $row !== false ? $this->hydrate($row) : null;
    }

    public function findAll(): array
    {
        $rows = $this->db->fetchAllAssociative('SELECT * FROM ' . self::TABLE . ' ORDER BY created_at DESC');

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function findByStatus(IdeaStatus $status): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . self::TABLE . ' WHERE status = ? ORDER BY created_at DESC',
            [$status->value],
        );

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function findBySubmitterId(string $submitterId): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . self::TABLE . ' WHERE submitter_id = ? ORDER BY created_at DESC',
            [$submitterId],
        );

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function save(ProjectIdea $idea): void
    {
        $exists = $this->db->fetchOne('SELECT id FROM ' . self::TABLE . ' WHERE id = ?', [$idea->getId()]);

        if ($exists === false) {
            $this->db->executeStatement(
                'INSERT INTO ' . self::TABLE . '
                    (id, title, description, category_id, status, submitter_id, rejection_reason, project_space_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $idea->getId(),
                    $idea->getTitle(),
                    $idea->getDescription(),
                    $idea->getCategory(),
                    $idea->getStatus()->value,
                    $idea->getSubmitterId(),
                    $idea->getRejectionReason(),
                    $idea->getProjectSpaceId(),
                    $idea->getCreatedAt()->format('Y-m-d H:i:s'),
                    $idea->getUpdatedAt()->format('Y-m-d H:i:s'),
                ],
            );
        } else {
            $this->db->executeStatement(
                'UPDATE ' . self::TABLE . ' SET
                    title = ?, description = ?, category_id = ?, status = ?,
                    submitter_id = ?, rejection_reason = ?, project_space_id = ?, updated_at = ?
                 WHERE id = ?',
                [
                    $idea->getTitle(),
                    $idea->getDescription(),
                    $idea->getCategory(),
                    $idea->getStatus()->value,
                    $idea->getSubmitterId(),
                    $idea->getRejectionReason(),
                    $idea->getProjectSpaceId(),
                    $idea->getUpdatedAt()->format('Y-m-d H:i:s'),
                    $idea->getId(),
                ],
            );
        }

        // Sync attachments
        $this->syncAttachments($idea);
    }

    public function delete(string $id): void
    {
        $this->db->executeStatement('DELETE FROM ' . self::ATTACHMENTS_TABLE . ' WHERE idea_id = ?', [$id]);
        $this->db->executeStatement('DELETE FROM ' . self::TABLE . ' WHERE id = ?', [$id]);
    }

    private function syncAttachments(ProjectIdea $idea): void
    {
        $existing = $this->db->fetchAllAssociative(
            'SELECT id FROM ' . self::ATTACHMENTS_TABLE . ' WHERE idea_id = ?',
            [$idea->getId()],
        );
        $existingIds = array_column($existing, 'id');
        $currentIds = array_map(fn(IdeaAttachment $a) => $a->getId(), $idea->getAttachments());

        // Delete removed
        $toDelete = array_diff($existingIds, $currentIds);
        foreach ($toDelete as $attId) {
            $this->db->executeStatement('DELETE FROM ' . self::ATTACHMENTS_TABLE . ' WHERE id = ?', [$attId]);
        }

        // Insert new
        $toInsert = array_diff($currentIds, $existingIds);
        foreach ($idea->getAttachments() as $att) {
            if (in_array($att->getId(), $toInsert, true)) {
                $this->db->insert(self::ATTACHMENTS_TABLE, [
                    'id' => $att->getId(),
                    'idea_id' => $idea->getId(),
                    'file_manager_id' => $att->getFileManagerId(),
                    'filename' => $att->getFilename(),
                    'mime_type' => $att->getMimeType(),
                    'size' => $att->getSize(),
                    'added_at' => $att->getAddedAt()->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /** @return IdeaAttachment[] */
    private function loadAttachments(string $ideaId): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . self::ATTACHMENTS_TABLE . ' WHERE idea_id = ? ORDER BY added_at ASC',
            [$ideaId],
        );

        return array_map(fn(array $row) => IdeaAttachment::fromPersistence(
            id: $row['id'],
            filename: $row['filename'],
            fileManagerId: (int) $row['file_manager_id'],
            mimeType: $row['mime_type'],
            size: (int) $row['size'],
            addedAt: new \DateTimeImmutable($row['added_at']),
        ), $rows);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ProjectIdea
    {
        return ProjectIdea::reconstitute(
            id: $row['id'],
            title: $row['title'],
            description: $row['description'] ?? null,
            category: $row['category_id'],
            status: IdeaStatus::from($row['status']),
            submitterId: isset($row['submitter_id']) ? (int) $row['submitter_id'] : null,
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
            rejectionReason: $row['rejection_reason'] ?? null,
            projectSpaceId: $row['project_space_id'] ?? null,
            attachments: $this->loadAttachments($row['id']),
        );
    }
}
```

- [ ] **Step 2: Update the controller**

Update `public/packages/inno_project_idea/controller.php` to add:
- DI bindings for `EditProjectIdeaPort` and `DeleteProjectIdeaPort`
- New routes: `GET /api/project-idea/mine`, `DELETE /api/project-idea/{id}`
- Event listener for `ProjectSpaceCreated`
- DB migration: add `rejection_reason`, `project_space_id` columns + `ib_idea_attachments` table in `upgrade()`

The key additions to `on_start()`:
```php
        // New port bindings
        $this->app->bind(EditProjectIdeaPort::class, EditProjectIdeaHandler::class);
        $this->app->bind(DeleteProjectIdeaPort::class, DeleteProjectIdeaHandler::class);

        // New routes
        $router->register('/api/project-idea/mine', [ProjectIdeaApiController::class, 'mine'], 'api.project_idea.mine', ['GET']);
        $router->register('/api/project-idea/{id}', [ProjectIdeaApiController::class, 'destroy'], 'api.project_idea.destroy', ['DELETE']);

        // Listen for project space creation
        Events::addListener(
            'innovationsbox.project_space.created',
            [$this->app->make(OnProjectSpaceCreated::class), 'handle']
        );
```

And in `upgrade()`, add DB migration for new columns and new table:
```php
        $db = $this->app->make('database')->connection();
        $columns = array_map(fn($c) => $c->getName(), $db->getSchemaManager()->listTableColumns('ib_project_ideas'));
        if (!in_array('rejection_reason', $columns, true)) {
            $db->executeStatement('ALTER TABLE ib_project_ideas ADD COLUMN rejection_reason TEXT NULL');
        }
        if (!in_array('project_space_id', $columns, true)) {
            $db->executeStatement('ALTER TABLE ib_project_ideas ADD COLUMN project_space_id VARCHAR(64) NULL');
        }

        $sm = $db->getSchemaManager();
        if (!$sm->tablesExist(['ib_idea_attachments'])) {
            $db->executeStatement('
                CREATE TABLE IF NOT EXISTS ib_idea_attachments (
                    id VARCHAR(64) NOT NULL,
                    idea_id VARCHAR(64) NOT NULL,
                    file_manager_id INT NOT NULL,
                    filename VARCHAR(255) NOT NULL,
                    mime_type VARCHAR(100) NOT NULL,
                    size INT NOT NULL,
                    added_at DATETIME NOT NULL,
                    PRIMARY KEY (id),
                    INDEX idx_idea_id (idea_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');
        }
```

- [ ] **Step 3: Update ProjectIdeaApiController**

Add `mine`, `update` (real implementation), and `destroy` methods. Add exception handler injection (same pattern as `ProjectSpaceApiController`). Add file upload handling in `store` and `update`. This is a substantial rewrite — the full controller code should include:
- Constructor: add `EditProjectIdeaPort`, `DeleteProjectIdeaPort`, `ExceptionHandlerInterface`
- `mine()`: returns `$this->repository->findBySubmitterId(...)` with full data including attachments, rejection reason, project space ID
- `store()`: parse multipart/form-data, import files via CMS File Manager, create `IdeaAttachment` objects
- `update()`: similar to store but also handles `removeFiles[]`
- `destroy()`: calls delete port, also cleans up CMS files
- All methods wrapped in try/catch with exception handler

- [ ] **Step 4: Run all tests**

Run: `./vendor/bin/pest`
Expected: All PASS

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_project_idea/
git commit -m "feat(project-idea): update repository, controller, and package for full CRUD + file uploads"
```

---

## Task 10: Update ModerationApiController — Show Endpoint + Overrides + Exception Handler

**Files:**
- Modify: `public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php`
- Modify: `public/packages/inno_moderation/controller.php`

- [ ] **Step 1: Add show route to controller.php**

Add to `on_start()` in `public/packages/inno_moderation/controller.php`:
```php
        $router->register('/api/moderation/{id}', [ModerationApiController::class, 'show'], 'api.moderation.show', ['GET']);
```

- [ ] **Step 2: Update ModerationApiController**

Add to the controller:
- `ExceptionHandlerInterface` injection in constructor
- `show()` method: fetches review + related idea data (via idea repository) + attachments
- Update `approve()` to pass override fields from request body
- Wrap all methods in try/catch with exception handler
- Enrich `index()` response with idea title, submitter name, category, submission date (requires injecting `ProjectIdeaRepositoryInterface`)

The `approve()` method body update:
```php
        $this->reviewPort->handle(new ReviewProjectIdeaCommand(
            reviewId: $id,
            reviewerId: (string) $this->auth->getUserId(),
            approved: true,
            notes: $body['notes'] ?? '',
            titleOverride: isset($body['title']) ? (string) $body['title'] : null,
            descriptionOverride: isset($body['description']) ? (string) $body['description'] : null,
            categoryOverride: isset($body['category']) ? (string) $body['category'] : null,
        ));
```

- [ ] **Step 3: Run all tests**

Run: `./vendor/bin/pest`
Expected: All PASS

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php \
        public/packages/inno_moderation/controller.php
git commit -m "feat(moderation): add show endpoint, overrides in approve, exception handler"
```

---

## Task 11: Frontend — Submission Form Page (`/projektidee/einreichen`)

**Files:**
- Create: `public/packages/inno_frontend/single_pages/projektidee/einreichen.php`
- Create: `resources/js/single_pages/projektidee-einreichen.ts`
- Create: `resources/css/single_pages/projektidee-einreichen.scss`

- [ ] **Step 1: Create PHP template**

`public/packages/inno_frontend/single_pages/projektidee/einreichen.php`:
- Check auth, show login prompt if not authenticated
- Empty form container for TS to populate
- Form fields: title input, category select, CKEditor container, file upload zone
- Submit button

- [ ] **Step 2: Create TypeScript**

`resources/js/single_pages/projektidee-einreichen.ts`:
- Fetch categories from `GET /api/moderation/category`
- Populate category dropdown
- Init CKEditor on description field (same config as projektraum.ts)
- File upload zone: drag-and-drop + file input, preview thumbnails, limit enforcement (5 files, 10MB)
- Submit handler: build `FormData` with title, description (from CKEditor), category, files[]
- `POST /api/project-idea` with multipart/form-data
- Success: redirect to `/projektidee/meine`
- Error: inline alert (same pattern as projektraum.ts)

- [ ] **Step 3: Create SCSS**

`resources/css/single_pages/projektidee-einreichen.scss`:
- Form layout styles
- File upload zone styles (drag-and-drop area, file list, thumbnails)

- [ ] **Step 4: Build and verify**

Run: `npm run development`
Expected: Compiles without errors

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/single_pages/projektidee/ \
        resources/js/single_pages/projektidee-einreichen.ts \
        resources/css/single_pages/projektidee-einreichen.scss
git commit -m "feat(frontend): add idea submission form page"
```

---

## Task 12: Frontend — My Submissions Page (`/projektidee/meine`)

**Files:**
- Create: `public/packages/inno_frontend/single_pages/projektidee/meine.php`
- Create: `resources/js/single_pages/projektidee-meine.ts`
- Create: `resources/css/single_pages/projektidee-meine.scss`

- [ ] **Step 1: Create PHP template + TypeScript + SCSS**

Template: auth check, empty container, edit modal, withdraw confirmation modal.

TypeScript:
- Fetch from `GET /api/project-idea/mine`
- Render cards with status badges (Eingereicht=grey, In Prüfung=blue, Genehmigt=green, Abgelehnt=red)
- Show rejection reason for rejected ideas
- Show link to Projektraum for approved ideas
- Edit button (pencil) for submitted ideas — opens edit form (same as submission form, pre-filled)
- Edit submits via `PUT /api/project-idea/{id}` with multipart/form-data
- Withdraw button — confirmation modal, then `DELETE /api/project-idea/{id}`
- Empty state message with link to `/projektidee/einreichen`

- [ ] **Step 2: Build and verify**

Run: `npm run development`
Expected: Compiles without errors

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/single_pages/projektidee/ \
        resources/js/single_pages/projektidee-meine.ts \
        resources/css/single_pages/projektidee-meine.scss
git commit -m "feat(frontend): add 'My Submissions' page with status badges and edit/withdraw"
```

---

## Task 13: Frontend — Moderation Queue Page (`/moderation`)

**Files:**
- Create: `public/packages/inno_frontend/single_pages/moderation/view.php`
- Create: `resources/js/single_pages/moderation.ts`
- Create: `resources/css/single_pages/moderation.scss`

- [ ] **Step 1: Create PHP template + TypeScript + SCSS**

Template: permission check, empty container for list.

TypeScript:
- Fetch from `GET /api/moderation` (enriched response with idea details)
- Render table/list: idea title, submitter, category badge, submission date
- Click row → navigate to `/moderation/detail?id={reviewId}` (or `/moderation/{id}` depending on CMS routing)
- Empty state: "Keine offenen Reviews vorhanden."

- [ ] **Step 2: Build and verify**

Run: `npm run development`

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/single_pages/moderation/ \
        resources/js/single_pages/moderation.ts \
        resources/css/single_pages/moderation.scss
git commit -m "feat(frontend): add moderation queue page"
```

---

## Task 14: Frontend — Moderation Review Detail Page (`/moderation/{id}`)

**Files:**
- Create: `public/packages/inno_frontend/single_pages/moderation/detail.php`
- Create: `resources/js/single_pages/moderation-detail.ts`
- Create: `resources/css/single_pages/moderation-detail.scss`

- [ ] **Step 1: Create PHP template + TypeScript + SCSS**

Template: permission check, container, reject confirmation modal.

TypeScript:
- Fetch from `GET /api/moderation/{id}` (includes full idea data + attachments)
- Render full idea: title, submitter, category, date, description (HTML), attachments (image thumbnails, download links)
- Approve form: editable title input, CKEditor for description, category dropdown, notes textarea, green "Genehmigen" button
- Reject form: mandatory reason textarea, red "Ablehnen" button with confirmation modal
- Approve → `POST /api/moderation/{id}/approve` with overrides
- Reject → `POST /api/moderation/{id}/reject` with notes
- Success: redirect to `/moderation`

- [ ] **Step 2: Build and verify**

Run: `npm run development`

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/single_pages/moderation/ \
        resources/js/single_pages/moderation-detail.ts \
        resources/css/single_pages/moderation-detail.scss
git commit -m "feat(frontend): add moderation review detail page with approve/reject"
```

---

## Task 15: Register Single Pages in CMS + Build Production Assets

**Files:**
- Modify: `public/packages/inno_frontend/controller.php` (register single pages)
- Build: `npm run production`

- [ ] **Step 1: Register single pages in inno_frontend controller**

Add single page registration in `install()` and `upgrade()` of the frontend package controller. The pattern follows existing single page registration (check how `projektraum` is registered).

Pages to register:
- `/projektidee/einreichen` → `Projektidee einreichen`
- `/projektidee/meine` → `Meine Projektideen`
- `/moderation` → `Moderation`
- `/moderation/detail` → `Review Detail` (or use a view pattern with ID parameter)

- [ ] **Step 2: Build production assets**

Run: `npm run production`
Expected: Compiles without errors

- [ ] **Step 3: Run all tests**

Run: `./vendor/bin/pest && npm run typecheck && npm run lint:all`
Expected: All pass

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/controller.php \
        public/packages/inno_frontend/themes/innovationsbox/js/single_pages/ \
        public/packages/inno_frontend/themes/innovationsbox/css/single_pages/
git commit -m "feat(frontend): register single pages in CMS and build production assets"
```

---

## Task 16: Update Seed Script + Final Integration Verification

**Files:**
- Modify: `scripts/seed-testdata.sh`

- [ ] **Step 1: Add seed data for project ideas and moderation reviews**

Add to `scripts/seed-testdata.sh`:
```sql
-- Project Ideas
TRUNCATE TABLE ib_project_ideas;
INSERT INTO ib_project_ideas (id, title, description, category_id, status, submitter_id, rejection_reason, project_space_id, created_at, updated_at) VALUES
('test_idea_1', 'Solaranlage auf dem Schulhaus', '<p>Eine Solaranlage auf dem Schulhausdach könnte...</p>', 'cat_bauen', 'approved', 2, NULL, 'test_space_1', DATE_SUB(NOW(), INTERVAL 100 DAY), DATE_SUB(NOW(), INTERVAL 90 DAY)),
('test_idea_2', 'Veloverleih-System', '<p>Ein gemeinschaftlicher Veloverleih...</p>', 'cat_mobilitaet', 'submitted', 3, NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
('test_idea_3', 'Abgelehnte Idee', '<p>Diese Idee wurde abgelehnt.</p>', 'cat_tourismus', 'rejected', 3, 'Passt nicht zum Themenbereich Klima.', NULL, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY));

-- Moderation Reviews
TRUNCATE TABLE ib_moderation_reviews;
INSERT INTO ib_moderation_reviews (id, idea_id, decision, reviewer_id, notes, title_override, description_override, category_override, created_at, reviewed_at) VALUES
('test_review_1', 'test_idea_1', 'approved', '1', 'Sehr gute Idee!', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 100 DAY), DATE_SUB(NOW(), INTERVAL 90 DAY)),
('test_review_2', 'test_idea_2', 'pending', NULL, '', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY), NULL),
('test_review_3', 'test_idea_3', 'rejected', '1', 'Passt nicht zum Themenbereich Klima.', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY));
```

- [ ] **Step 2: Run full test suite**

Run: `composer test && npm run typecheck && npm run lint:all`
Expected: All pass

- [ ] **Step 3: Commit**

```bash
git add scripts/seed-testdata.sh
git commit -m "feat: add seed data for project ideas and moderation reviews"
```

- [ ] **Step 4: Manual smoke test**

Run `make seed` (or execute seed script manually), then:
1. Visit `/projektidee/einreichen` — verify form loads, categories populate
2. Submit an idea with attachments
3. Visit `/projektidee/meine` — verify idea appears with "Eingereicht" status
4. Visit `/moderation` as admin — verify pending review appears
5. Click through to detail page, approve with overrides
6. Verify Projektraum is created and linked on "Meine Ideen"
