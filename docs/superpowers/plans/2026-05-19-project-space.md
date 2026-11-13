# Project Space Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the full project space feature — domain model, use cases, REST API, persistence, shared events, and frontend (public overview + detail page with collaboard iframe, status updates, and membership management).

**Architecture:** Single aggregate root (`ProjectSpace`) in `inno_project_space` package following hexagonal architecture (Ports & Adapters). New shared events in `inno_shared`. Frontend as PHP component partials + TypeScript single pages in `inno_frontend`. All cross-package communication via events only.

**Tech Stack:** PHP 8.2+, Pest (testing), Mockery (mocks), Concrete CMS 9.4+ (DI, routing, auth), Symfony EventDispatcher, TypeScript, SCSS with design tokens, Laravel Mix (webpack)

**Spec:** `docs/superpowers/specs/2026-05-18-project-space-design.md`

---

## File Structure Overview

### New files in `public/packages/inno_project_space/src/`

```
Domain/Model/
  SpaceStatus.php               ← enum
  RequestStatus.php             ← enum
  CollaboardLink.php            ← value object
  StatusUpdate.php              ← value object
  MembershipRequest.php         ← value object
  ProjectSpace.php              ← MODIFY: extend aggregate root
Domain/Exception/
  InvalidCollaboardUrlException.php
  InvalidStatusTransitionException.php
Application/UseCase/
  AddCollaboardLink/            ← Command + Handler (7 use cases total)
  RemoveCollaboardLink/
  WriteStatusUpdate/
  RequestToJoin/
  HandleJoinRequest/
  ChangeSpaceStatus/
  InviteMember/
Application/EventHandler/
  OnProjectIdeaApproved.php     ← MODIFY: pass description, set status Draft
Port/In/
  AddCollaboardLinkPort.php
  RemoveCollaboardLinkPort.php
  WriteStatusUpdatePort.php
  RequestToJoinPort.php
  HandleJoinRequestPort.php
  ChangeSpaceStatusPort.php
  InviteMemberPort.php
Port/Out/
  ProjectSpaceRepositoryInterface.php  ← MODIFY: add findPublic()
Adapter/Out/Persistence/
  ConcreteProjectSpaceRepository.php   ← MODIFY: implement all methods
Adapter/In/Http/
  ProjectSpaceApiController.php        ← MODIFY: implement all endpoints
```

### New files in `public/packages/inno_shared/src/Domain/Event/`

```
StatusUpdatePublished.php
JoinRequestReceived.php
JoinRequestAccepted.php
JoinRequestRejected.php
ProjectSpaceActivated.php
ProjectSpaceCompleted.php
MemberInvited.php
```

### New files in `public/packages/inno_frontend/`

```
components/molecules/project-card/
components/molecules/collaboard-item/
components/molecules/status-update-entry/
components/molecules/join-request-card/
components/organisms/collaboard-panel/
components/organisms/status-update-timeline/
components/organisms/project-space-sidebar/
single_pages/projektraeume.php
single_pages/projektraum/view.php
```

### New files in `resources/`

```
js/single_pages/projektraeume.ts
js/single_pages/projektraum.ts
css/single_pages/projektraeume.scss
css/single_pages/projektraum.scss
```

### Test files

```
tests/unit/inno_project_space/Domain/SpaceStatusTest.php
tests/unit/inno_project_space/Domain/CollaboardLinkTest.php
tests/unit/inno_project_space/Domain/StatusUpdateTest.php
tests/unit/inno_project_space/Domain/MembershipRequestTest.php
tests/unit/inno_project_space/Domain/ProjectSpaceTest.php          ← MODIFY
tests/unit/inno_project_space/Application/AddCollaboardLinkHandlerTest.php
tests/unit/inno_project_space/Application/RemoveCollaboardLinkHandlerTest.php
tests/unit/inno_project_space/Application/WriteStatusUpdateHandlerTest.php
tests/unit/inno_project_space/Application/RequestToJoinHandlerTest.php
tests/unit/inno_project_space/Application/HandleJoinRequestHandlerTest.php
tests/unit/inno_project_space/Application/ChangeSpaceStatusHandlerTest.php
tests/unit/inno_project_space/Application/InviteMemberHandlerTest.php
tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php  ← MODIFY
```

---

### Task 1: Domain Enums — SpaceStatus and RequestStatus

**Files:**

- Create: `public/packages/inno_project_space/src/Domain/Model/SpaceStatus.php`
- Create: `public/packages/inno_project_space/src/Domain/Model/RequestStatus.php`
- Create: `tests/unit/inno_project_space/Domain/SpaceStatusTest.php`

- [ ] **Step 1: Write tests for enums**

Create `tests/unit/inno_project_space/Domain/SpaceStatusTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;

test('SpaceStatus has four cases with correct string values', function () {
    expect(SpaceStatus::Draft->value)->toBe('draft')
        ->and(SpaceStatus::Active->value)->toBe('active')
        ->and(SpaceStatus::Completed->value)->toBe('completed')
        ->and(SpaceStatus::Archived->value)->toBe('archived');
});

test('SpaceStatus can be created from string value', function () {
    expect(SpaceStatus::from('active'))->toBe(SpaceStatus::Active);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/SpaceStatusTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Implement SpaceStatus enum**

Create `public/packages/inno_project_space/src/Domain/Model/SpaceStatus.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

enum SpaceStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';
}
```

- [ ] **Step 4: Implement RequestStatus enum**

Create `public/packages/inno_project_space/src/Domain/Model/RequestStatus.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

enum RequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/SpaceStatusTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_project_space/src/Domain/Model/SpaceStatus.php \
        public/packages/inno_project_space/src/Domain/Model/RequestStatus.php \
        tests/unit/inno_project_space/Domain/SpaceStatusTest.php
git commit -m "feat(project-space): add SpaceStatus and RequestStatus enums"
```

---

### Task 2: Value Objects — CollaboardLink, StatusUpdate, MembershipRequest

**Files:**

- Create: `public/packages/inno_project_space/src/Domain/Model/CollaboardLink.php`
- Create: `public/packages/inno_project_space/src/Domain/Model/StatusUpdate.php`
- Create: `public/packages/inno_project_space/src/Domain/Model/MembershipRequest.php`
- Create: `public/packages/inno_project_space/src/Domain/Exception/InvalidCollaboardUrlException.php`
- Create: `tests/unit/inno_project_space/Domain/CollaboardLinkTest.php`
- Create: `tests/unit/inno_project_space/Domain/StatusUpdateTest.php`
- Create: `tests/unit/inno_project_space/Domain/MembershipRequestTest.php`

- [ ] **Step 1: Write CollaboardLink test**

Create `tests/unit/inno_project_space/Domain/CollaboardLinkTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidCollaboardUrlException;
use Concrete\Package\InnoProjectSpace\Domain\Model\CollaboardLink;

test('create() builds a CollaboardLink with correct properties', function () {
    $link = CollaboardLink::create('link_1', 'Brainstorming', 'https://web.collaboard.app/share/abc123');
    expect($link->getId())->toBe('link_1')
        ->and($link->getTitle())->toBe('Brainstorming')
        ->and($link->getUrl())->toBe('https://web.collaboard.app/share/abc123')
        ->and($link->getAddedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('create() rejects non-collaboard URLs', function () {
    CollaboardLink::create('link_1', 'Bad', 'https://miro.com/board/abc');
})->throws(InvalidCollaboardUrlException::class);

test('create() accepts web.collaboard.app URLs', function () {
    $link = CollaboardLink::create('link_1', 'Board', 'https://web.collaboard.app/share/xyz');
    expect($link->getUrl())->toBe('https://web.collaboard.app/board/xyz');
});

test('create() rejects URLs with collaboard.app as substring', function () {
    CollaboardLink::create('link_1', 'Fake', 'https://notcollaboard.app/share/abc');
})->throws(InvalidCollaboardUrlException::class);
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/CollaboardLinkTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Implement InvalidCollaboardUrlException**

Create `public/packages/inno_project_space/src/Domain/Exception/InvalidCollaboardUrlException.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Exception;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InvalidCollaboardUrlException extends ValidationException
{
    public static function withUrl(string $url): self
    {
        return new self(
            "URL must be a web.collaboard.app link, got: '{$url}'",
            ['url' => $url],
        );
    }
}
```

- [ ] **Step 4: Implement CollaboardLink**

Create `public/packages/inno_project_space/src/Domain/Model/CollaboardLink.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

use Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidCollaboardUrlException;

class CollaboardLink
{
    private function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $url,
        private readonly \DateTimeImmutable $addedAt,
    ) {}

    public static function create(string $id, string $title, string $url): self
    {
        if (!self::isValidCollaboardUrl($url)) {
            throw InvalidCollaboardUrlException::withUrl($url);
        }

        return new self($id, $title, $url, new \DateTimeImmutable());
    }

    /**
     * Reconstruct from persistence (no validation — data is already valid).
     */
    public static function fromPersistence(
        string $id,
        string $title,
        string $url,
        \DateTimeImmutable $addedAt,
    ): self {
        return new self($id, $title, $url, $addedAt);
    }

    private static function isValidCollaboardUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null || $host === false) {
            return false;
        }

        return $host === 'web.collaboard.app';
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
```

- [ ] **Step 5: Run CollaboardLink test**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/CollaboardLinkTest.php`
Expected: PASS

- [ ] **Step 6: Write StatusUpdate test**

Create `tests/unit/inno_project_space/Domain/StatusUpdateTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdate;

test('create() builds a StatusUpdate with correct properties', function () {
    $update = StatusUpdate::create('upd_1', 'Kickoff done', '<p>We started!</p>', 'user_1');
    expect($update->getId())->toBe('upd_1')
        ->and($update->getTitle())->toBe('Kickoff done')
        ->and($update->getBody())->toBe('<p>We started!</p>')
        ->and($update->getAuthorId())->toBe('user_1')
        ->and($update->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class);
});
```

- [ ] **Step 7: Implement StatusUpdate**

Create `public/packages/inno_project_space/src/Domain/Model/StatusUpdate.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

class StatusUpdate
{
    private function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly string $body,
        private readonly string $authorId,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(string $id, string $title, string $body, string $authorId): self
    {
        return new self($id, $title, $body, $authorId, new \DateTimeImmutable());
    }

    public static function fromPersistence(
        string $id,
        string $title,
        string $body,
        string $authorId,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $title, $body, $authorId, $createdAt);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getAuthorId(): string
    {
        return $this->authorId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

- [ ] **Step 8: Run StatusUpdate test**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/StatusUpdateTest.php`
Expected: PASS

- [ ] **Step 9: Write MembershipRequest test**

Create `tests/unit/inno_project_space/Domain/MembershipRequestTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\MembershipRequest;
use Concrete\Package\InnoProjectSpace\Domain\Model\RequestStatus;

test('create() builds a pending MembershipRequest', function () {
    $req = MembershipRequest::create('req_1', 'user_42');
    expect($req->getId())->toBe('req_1')
        ->and($req->getUserId())->toBe('user_42')
        ->and($req->getStatus())->toBe(RequestStatus::Pending)
        ->and($req->getRequestedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($req->getDecidedAt())->toBeNull();
});

test('accept() changes status to Accepted and sets decidedAt', function () {
    $req = MembershipRequest::create('req_1', 'user_42');
    $req->accept();
    expect($req->getStatus())->toBe(RequestStatus::Accepted)
        ->and($req->getDecidedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('reject() changes status to Rejected and sets decidedAt', function () {
    $req = MembershipRequest::create('req_1', 'user_42');
    $req->reject();
    expect($req->getStatus())->toBe(RequestStatus::Rejected)
        ->and($req->getDecidedAt())->toBeInstanceOf(DateTimeImmutable::class);
});
```

- [ ] **Step 10: Implement MembershipRequest**

Create `public/packages/inno_project_space/src/Domain/Model/MembershipRequest.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

class MembershipRequest
{
    private function __construct(
        private readonly string $id,
        private readonly string $userId,
        private RequestStatus $status,
        private readonly \DateTimeImmutable $requestedAt,
        private ?\DateTimeImmutable $decidedAt,
    ) {}

    public static function create(string $id, string $userId): self
    {
        return new self($id, $userId, RequestStatus::Pending, new \DateTimeImmutable(), null);
    }

    public static function fromPersistence(
        string $id,
        string $userId,
        RequestStatus $status,
        \DateTimeImmutable $requestedAt,
        ?\DateTimeImmutable $decidedAt,
    ): self {
        return new self($id, $userId, $status, $requestedAt, $decidedAt);
    }

    public function accept(): void
    {
        $this->status = RequestStatus::Accepted;
        $this->decidedAt = new \DateTimeImmutable();
    }

    public function reject(): void
    {
        $this->status = RequestStatus::Rejected;
        $this->decidedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getStatus(): RequestStatus
    {
        return $this->status;
    }

    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function getDecidedAt(): ?\DateTimeImmutable
    {
        return $this->decidedAt;
    }
}
```

- [ ] **Step 11: Run all value object tests**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/`
Expected: ALL PASS

- [ ] **Step 12: Commit**

```bash
git add public/packages/inno_project_space/src/Domain/Model/CollaboardLink.php \
        public/packages/inno_project_space/src/Domain/Model/StatusUpdate.php \
        public/packages/inno_project_space/src/Domain/Model/MembershipRequest.php \
        public/packages/inno_project_space/src/Domain/Exception/InvalidCollaboardUrlException.php \
        tests/unit/inno_project_space/Domain/CollaboardLinkTest.php \
        tests/unit/inno_project_space/Domain/StatusUpdateTest.php \
        tests/unit/inno_project_space/Domain/MembershipRequestTest.php
git commit -m "feat(project-space): add CollaboardLink, StatusUpdate, MembershipRequest value objects"
```

---

### Task 3: Extend ProjectSpace Aggregate Root

**Files:**

- Modify: `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php`
- Create: `public/packages/inno_project_space/src/Domain/Exception/InvalidStatusTransitionException.php`
- Modify: `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php`

- [ ] **Step 1: Write extended ProjectSpace tests**

Replace `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php` with:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidCollaboardUrlException;
use Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidStatusTransitionException;
use Concrete\Package\InnoProjectSpace\Domain\Model\CollaboardLink;
use Concrete\Package\InnoProjectSpace\Domain\Model\MembershipRequest;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\RequestStatus;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;

// --- Creation ---

test('createFromApprovedIdea() creates space in Draft status with initiator as member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Solar Project', 'A great idea', 'user_1');
    expect($space->getId())->toBe('space_1')
        ->and($space->getProjectIdeaId())->toBe('idea_1')
        ->and($space->getTitle())->toBe('Solar Project')
        ->and($space->getDescription())->toBe('A great idea')
        ->and($space->getStatus())->toBe(SpaceStatus::Draft)
        ->and($space->getInitiatorId())->toBe('user_1')
        ->and($space->getMemberIds())->toBe(['user_1']);
});

// --- Collaboard links ---

test('addCollaboardLink() adds a link and returns it', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $link = $space->addCollaboardLink('link_1', 'Board', 'https://web.collaboard.app/share/abc');
    expect($link->getId())->toBe('link_1')
        ->and($space->getCollaboardLinks())->toHaveCount(1);
});

test('addCollaboardLink() rejects invalid URL', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->addCollaboardLink('link_1', 'Bad', 'https://miro.com/board/abc');
})->throws(InvalidCollaboardUrlException::class);

test('removeCollaboardLink() removes an existing link', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->addCollaboardLink('link_1', 'Board', 'https://web.collaboard.app/share/abc');
    $space->removeCollaboardLink('link_1');
    expect($space->getCollaboardLinks())->toHaveCount(0);
});

// --- Status updates ---

test('addStatusUpdate() adds an update and returns it', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $update = $space->addStatusUpdate('upd_1', 'Kickoff', '<p>Done!</p>', 'user_1');
    expect($update->getTitle())->toBe('Kickoff')
        ->and($space->getStatusUpdates())->toHaveCount(1);
});

// --- Membership requests ---

test('requestToJoin() creates a pending request', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->activate();
    $req = $space->requestToJoin('req_1', 'user_2');
    expect($req->getStatus())->toBe(RequestStatus::Pending)
        ->and($space->getMembershipRequests())->toHaveCount(1);
});

test('requestToJoin() throws if user is already a member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->activate();
    $space->requestToJoin('req_1', 'user_1');
})->throws(\DomainException::class, 'already a member');

test('requestToJoin() throws if space is in Draft status', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->requestToJoin('req_1', 'user_2');
})->throws(\DomainException::class, 'not accepting');

test('acceptJoinRequest() moves request to Accepted and adds member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->activate();
    $space->requestToJoin('req_1', 'user_2');
    $space->acceptJoinRequest('req_1');
    expect($space->getMemberIds())->toContain('user_2')
        ->and($space->getMembershipRequests()[0]->getStatus())->toBe(RequestStatus::Accepted);
});

test('rejectJoinRequest() moves request to Rejected', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->activate();
    $space->requestToJoin('req_1', 'user_2');
    $space->rejectJoinRequest('req_1');
    expect($space->getMembershipRequests()[0]->getStatus())->toBe(RequestStatus::Rejected)
        ->and($space->getMemberIds())->not->toContain('user_2');
});

// --- Status transitions ---

test('activate() transitions from Draft to Active', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->activate();
    expect($space->getStatus())->toBe(SpaceStatus::Active);
});

test('complete() transitions from Active to Completed', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->activate();
    $space->complete();
    expect($space->getStatus())->toBe(SpaceStatus::Completed);
});

test('archive() transitions from Completed to Archived', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->activate();
    $space->complete();
    $space->archive();
    expect($space->getStatus())->toBe(SpaceStatus::Archived);
});

test('activate() throws if not in Draft status', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->activate();
    $space->activate();
})->throws(InvalidStatusTransitionException::class);

test('complete() throws if not in Active status', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->complete();
})->throws(InvalidStatusTransitionException::class);

// --- Direct member add (for invites) ---

test('addMember() adds a new member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->addMember('user_2');
    expect($space->getMemberIds())->toContain('user_2');
});

test('addMember() is idempotent', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->addMember('user_1');
    expect($space->getMemberIds())->toHaveCount(1);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/ProjectSpaceTest.php`
Expected: FAIL — new methods don't exist yet

- [ ] **Step 3: Create InvalidStatusTransitionException**

Create `public/packages/inno_project_space/src/Domain/Exception/InvalidStatusTransitionException.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Exception;

use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InvalidStatusTransitionException extends ValidationException
{
    public static function fromTo(SpaceStatus $from, SpaceStatus $to): self
    {
        return new self(
            "Cannot transition from '{$from->value}' to '{$to->value}'.",
            ['from' => $from->value, 'to' => $to->value],
        );
    }
}
```

- [ ] **Step 4: Rewrite ProjectSpace aggregate root**

Replace `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php` with:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

use Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidStatusTransitionException;

class ProjectSpace
{
    /** @var string[] */
    private array $memberIds;
    /** @var CollaboardLink[] */
    private array $collaboardLinks;
    /** @var StatusUpdate[] */
    private array $statusUpdates;
    /** @var MembershipRequest[] */
    private array $membershipRequests;

    private function __construct(
        private readonly string $id,
        private readonly string $projectIdeaId,
        private string $title,
        private string $description,
        private SpaceStatus $status,
        private readonly string $initiatorId,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->memberIds = [];
        $this->collaboardLinks = [];
        $this->statusUpdates = [];
        $this->membershipRequests = [];
    }

    public static function createFromApprovedIdea(
        string $id,
        string $projectIdeaId,
        string $title,
        string $description,
        string $initiatorId,
    ): self {
        $now = new \DateTimeImmutable();
        $space = new self($id, $projectIdeaId, $title, $description, SpaceStatus::Draft, $initiatorId, $now, $now);
        $space->memberIds[] = $initiatorId;

        return $space;
    }

    /**
     * Reconstruct from persistence — no validation, no side effects.
     *
     * @param string[] $memberIds
     * @param CollaboardLink[] $collaboardLinks
     * @param StatusUpdate[] $statusUpdates
     * @param MembershipRequest[] $membershipRequests
     */
    public static function fromPersistence(
        string $id,
        string $projectIdeaId,
        string $title,
        string $description,
        SpaceStatus $status,
        string $initiatorId,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        array $memberIds,
        array $collaboardLinks,
        array $statusUpdates,
        array $membershipRequests,
    ): self {
        $space = new self($id, $projectIdeaId, $title, $description, $status, $initiatorId, $createdAt, $updatedAt);
        $space->memberIds = $memberIds;
        $space->collaboardLinks = $collaboardLinks;
        $space->statusUpdates = $statusUpdates;
        $space->membershipRequests = $membershipRequests;

        return $space;
    }

    // --- Collaboard links ---

    public function addCollaboardLink(string $id, string $title, string $url): CollaboardLink
    {
        $link = CollaboardLink::create($id, $title, $url);
        $this->collaboardLinks[] = $link;
        $this->updatedAt = new \DateTimeImmutable();

        return $link;
    }

    public function removeCollaboardLink(string $linkId): void
    {
        $this->collaboardLinks = array_values(array_filter(
            $this->collaboardLinks,
            fn(CollaboardLink $l) => $l->getId() !== $linkId,
        ));
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Status updates ---

    public function addStatusUpdate(string $id, string $title, string $body, string $authorId): StatusUpdate
    {
        $update = StatusUpdate::create($id, $title, $body, $authorId);
        $this->statusUpdates[] = $update;
        $this->updatedAt = new \DateTimeImmutable();

        return $update;
    }

    // --- Membership ---

    public function addMember(string $userId): void
    {
        if (!in_array($userId, $this->memberIds, true)) {
            $this->memberIds[] = $userId;
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function requestToJoin(string $requestId, string $userId): MembershipRequest
    {
        if ($this->status !== SpaceStatus::Active && $this->status !== SpaceStatus::Completed) {
            throw new \DomainException('Space is not accepting join requests.');
        }
        if (in_array($userId, $this->memberIds, true)) {
            throw new \DomainException('User is already a member.');
        }

        $request = MembershipRequest::create($requestId, $userId);
        $this->membershipRequests[] = $request;

        return $request;
    }

    public function acceptJoinRequest(string $requestId): MembershipRequest
    {
        $request = $this->findRequest($requestId);
        $request->accept();
        $this->addMember($request->getUserId());

        return $request;
    }

    public function rejectJoinRequest(string $requestId): MembershipRequest
    {
        $request = $this->findRequest($requestId);
        $request->reject();

        return $request;
    }

    private function findRequest(string $requestId): MembershipRequest
    {
        foreach ($this->membershipRequests as $request) {
            if ($request->getId() === $requestId) {
                return $request;
            }
        }
        throw new \DomainException("MembershipRequest '{$requestId}' not found.");
    }

    // --- Status transitions ---

    public function activate(): void
    {
        if ($this->status !== SpaceStatus::Draft) {
            throw InvalidStatusTransitionException::fromTo($this->status, SpaceStatus::Active);
        }
        $this->status = SpaceStatus::Active;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function complete(): void
    {
        if ($this->status !== SpaceStatus::Active) {
            throw InvalidStatusTransitionException::fromTo($this->status, SpaceStatus::Completed);
        }
        $this->status = SpaceStatus::Completed;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function archive(): void
    {
        if ($this->status !== SpaceStatus::Completed) {
            throw InvalidStatusTransitionException::fromTo($this->status, SpaceStatus::Archived);
        }
        $this->status = SpaceStatus::Archived;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Getters ---

    public function getId(): string { return $this->id; }
    public function getProjectIdeaId(): string { return $this->projectIdeaId; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function getStatus(): SpaceStatus { return $this->status; }
    public function getInitiatorId(): string { return $this->initiatorId; }
    /** @return list<string> */
    public function getMemberIds(): array { return $this->memberIds; }
    /** @return CollaboardLink[] */
    public function getCollaboardLinks(): array { return $this->collaboardLinks; }
    /** @return StatusUpdate[] */
    public function getStatusUpdates(): array { return $this->statusUpdates; }
    /** @return MembershipRequest[] */
    public function getMembershipRequests(): array { return $this->membershipRequests; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
```

- [ ] **Step 5: Run all domain tests**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/`
Expected: ALL PASS

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_project_space/src/Domain/ \
        tests/unit/inno_project_space/Domain/
git commit -m "feat(project-space): extend ProjectSpace aggregate with collaboards, updates, requests"
```

---

### Task 4: Shared Events

**Files:**

- Create: `public/packages/inno_shared/src/Domain/Event/StatusUpdatePublished.php`
- Create: `public/packages/inno_shared/src/Domain/Event/JoinRequestReceived.php`
- Create: `public/packages/inno_shared/src/Domain/Event/JoinRequestAccepted.php`
- Create: `public/packages/inno_shared/src/Domain/Event/JoinRequestRejected.php`
- Create: `public/packages/inno_shared/src/Domain/Event/ProjectSpaceActivated.php`
- Create: `public/packages/inno_shared/src/Domain/Event/ProjectSpaceCompleted.php`
- Create: `public/packages/inno_shared/src/Domain/Event/MemberInvited.php`

- [ ] **Step 1: Create all 7 event classes**

All events follow the same pattern as `ProjectSpaceCreated`. Create each file:

`public/packages/inno_shared/src/Domain/Event/StatusUpdatePublished.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class StatusUpdatePublished extends Event
{
    public const NAME = 'innovationsbox.project_space.status_update_published';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $updateId,
        private readonly string $authorId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getUpdateId(): string { return $this->updateId; }
    public function getAuthorId(): string { return $this->authorId; }
}
```

`public/packages/inno_shared/src/Domain/Event/JoinRequestReceived.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class JoinRequestReceived extends Event
{
    public const NAME = 'innovationsbox.project_space.join_request_received';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $requestId,
        private readonly string $userId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getRequestId(): string { return $this->requestId; }
    public function getUserId(): string { return $this->userId; }
}
```

`public/packages/inno_shared/src/Domain/Event/JoinRequestAccepted.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class JoinRequestAccepted extends Event
{
    public const NAME = 'innovationsbox.project_space.join_request_accepted';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $requestId,
        private readonly string $userId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getRequestId(): string { return $this->requestId; }
    public function getUserId(): string { return $this->userId; }
}
```

`public/packages/inno_shared/src/Domain/Event/JoinRequestRejected.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class JoinRequestRejected extends Event
{
    public const NAME = 'innovationsbox.project_space.join_request_rejected';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $requestId,
        private readonly string $userId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getRequestId(): string { return $this->requestId; }
    public function getUserId(): string { return $this->userId; }
}
```

`public/packages/inno_shared/src/Domain/Event/ProjectSpaceActivated.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ProjectSpaceActivated extends Event
{
    public const NAME = 'innovationsbox.project_space.activated';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $initiatorId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getInitiatorId(): string { return $this->initiatorId; }
}
```

`public/packages/inno_shared/src/Domain/Event/ProjectSpaceCompleted.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ProjectSpaceCompleted extends Event
{
    public const NAME = 'innovationsbox.project_space.completed';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $initiatorId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getInitiatorId(): string { return $this->initiatorId; }
}
```

`public/packages/inno_shared/src/Domain/Event/MemberInvited.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MemberInvited extends Event
{
    public const NAME = 'innovationsbox.project_space.member_invited';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $userId,
        private readonly string $initiatorId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getUserId(): string { return $this->userId; }
    public function getInitiatorId(): string { return $this->initiatorId; }
}
```

- [ ] **Step 2: Commit**

```bash
git add public/packages/inno_shared/src/Domain/Event/StatusUpdatePublished.php \
        public/packages/inno_shared/src/Domain/Event/JoinRequestReceived.php \
        public/packages/inno_shared/src/Domain/Event/JoinRequestAccepted.php \
        public/packages/inno_shared/src/Domain/Event/JoinRequestRejected.php \
        public/packages/inno_shared/src/Domain/Event/ProjectSpaceActivated.php \
        public/packages/inno_shared/src/Domain/Event/ProjectSpaceCompleted.php \
        public/packages/inno_shared/src/Domain/Event/MemberInvited.php
git commit -m "feat(shared): add project space domain events"
```

---

### Task 5: Use Case — AddCollaboardLink (Command + Port + Handler)

**Files:**

- Create: `public/packages/inno_project_space/src/Application/UseCase/AddCollaboardLink/AddCollaboardLinkCommand.php`
- Create: `public/packages/inno_project_space/src/Application/UseCase/AddCollaboardLink/AddCollaboardLinkHandler.php`
- Create: `public/packages/inno_project_space/src/Port/In/AddCollaboardLinkPort.php`
- Create: `tests/unit/inno_project_space/Application/AddCollaboardLinkHandlerTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/unit/inno_project_space/Application/AddCollaboardLinkHandlerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\AddCollaboardLink\AddCollaboardLinkCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\AddCollaboardLink\AddCollaboardLinkHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

afterEach(fn() => Mockery::close());

function makeAddCollaboardMocks(string $initiatorId = 'user_1'): array
{
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', $initiatorId);
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    return [$space, $repo, $auth, $tx, $logger];
}

test('handle() adds a collaboard link and saves', function () {
    [$space, $repo, $auth, $tx, $logger] = makeAddCollaboardMocks();

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once()->with($space);
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('hasPermission')->with('space.manage')->andReturn(true);

    $handler = new AddCollaboardLinkHandler($repo, $auth, $tx, $logger);
    $handler->handle(new AddCollaboardLinkCommand(
        spaceId: 'space_1',
        title: 'Brainstorming Board',
        url: 'https://web.collaboard.app/share/abc123',
    ));

    expect($space->getCollaboardLinks())->toHaveCount(1);
});

test('handle() throws AuthException if user lacks space.manage permission', function () {
    [$space, $repo, $auth, $tx, $logger] = makeAddCollaboardMocks();

    $auth->shouldReceive('hasPermission')->with('space.manage')->andReturn(false);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);

    $handler = new AddCollaboardLinkHandler($repo, $auth, $tx, $logger);
    $handler->handle(new AddCollaboardLinkCommand('space_1', 'Board', 'https://web.collaboard.app/share/abc'));
})->throws(AuthException::class);
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Application/AddCollaboardLinkHandlerTest.php`
Expected: FAIL — classes not found

- [ ] **Step 3: Create Command**

Create `public/packages/inno_project_space/src/Application/UseCase/AddCollaboardLink/AddCollaboardLinkCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\AddCollaboardLink;

final readonly class AddCollaboardLinkCommand
{
    public function __construct(
        public string $spaceId,
        public string $title,
        public string $url,
    ) {}
}
```

- [ ] **Step 4: Create Port/In interface**

Create `public/packages/inno_project_space/src/Port/In/AddCollaboardLinkPort.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\AddCollaboardLink\AddCollaboardLinkCommand;
use Concrete\Package\InnoProjectSpace\Domain\Model\CollaboardLink;

interface AddCollaboardLinkPort
{
    public function handle(AddCollaboardLinkCommand $command): CollaboardLink;
}
```

- [ ] **Step 5: Create Handler**

Create `public/packages/inno_project_space/src/Application/UseCase/AddCollaboardLink/AddCollaboardLinkHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\AddCollaboardLink;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Domain\Model\CollaboardLink;
use Concrete\Package\InnoProjectSpace\Port\In\AddCollaboardLinkPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

class AddCollaboardLinkHandler implements AddCollaboardLinkPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(AddCollaboardLinkCommand $command): CollaboardLink
    {
        if (!$this->auth->hasPermission('space.manage')) {
            throw new AuthException(
                'Permission denied for space.manage',
                ['permission' => 'space.manage'],
                $this->auth->isAuthenticated(),
            );
        }

        return $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $link = $space->addCollaboardLink(
                id: uniqid('link_', true),
                title: $command->title,
                url: $command->url,
            );

            $this->repository->save($space);

            $this->logger->info('Collaboard link added', [
                'spaceId' => $command->spaceId,
                'linkId' => $link->getId(),
            ]);

            return $link;
        });
    }
}
```

- [ ] **Step 6: Run test**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Application/AddCollaboardLinkHandlerTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_project_space/src/Application/UseCase/AddCollaboardLink/ \
        public/packages/inno_project_space/src/Port/In/AddCollaboardLinkPort.php \
        tests/unit/inno_project_space/Application/AddCollaboardLinkHandlerTest.php
git commit -m "feat(project-space): add AddCollaboardLink use case"
```

---

### Task 6: Use Cases — RemoveCollaboardLink, WriteStatusUpdate, RequestToJoin, HandleJoinRequest, ChangeSpaceStatus, InviteMember

This task creates the remaining 6 use cases. Each follows the identical pattern established in Task 5: Command (readonly DTO) → Port/In (interface) → Handler (implements port, injects repo + auth + transaction + logger/dispatcher).

**Files:** 6 Command files, 6 Handler files, 6 Port/In files, 6 test files.

- [ ] **Step 1: Create RemoveCollaboardLink use case**

Create `public/packages/inno_project_space/src/Application/UseCase/RemoveCollaboardLink/RemoveCollaboardLinkCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveCollaboardLink;

final readonly class RemoveCollaboardLinkCommand
{
    public function __construct(
        public string $spaceId,
        public string $linkId,
    ) {}
}
```

Create `public/packages/inno_project_space/src/Port/In/RemoveCollaboardLinkPort.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveCollaboardLink\RemoveCollaboardLinkCommand;

interface RemoveCollaboardLinkPort
{
    public function handle(RemoveCollaboardLinkCommand $command): void;
}
```

Create `public/packages/inno_project_space/src/Application/UseCase/RemoveCollaboardLink/RemoveCollaboardLinkHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveCollaboardLink;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\In\RemoveCollaboardLinkPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;

class RemoveCollaboardLinkHandler implements RemoveCollaboardLinkPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(RemoveCollaboardLinkCommand $command): void
    {
        if (!$this->auth->hasPermission('space.manage')) {
            throw new AuthException('Permission denied for space.manage', ['permission' => 'space.manage'], $this->auth->isAuthenticated());
        }

        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $space->removeCollaboardLink($command->linkId);
            $this->repository->save($space);

            $this->logger->info('Collaboard link removed', ['spaceId' => $command->spaceId, 'linkId' => $command->linkId]);
        });
    }
}
```

Create `tests/unit/inno_project_space/Application/RemoveCollaboardLinkHandlerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveCollaboardLink\RemoveCollaboardLinkCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveCollaboardLink\RemoveCollaboardLinkHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;

afterEach(fn() => Mockery::close());

test('handle() removes a collaboard link and saves', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->addCollaboardLink('link_1', 'Board', 'https://web.collaboard.app/share/abc');

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once()->with($space);
    $auth->shouldReceive('hasPermission')->with('space.manage')->andReturn(true);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new RemoveCollaboardLinkHandler($repo, $auth, $tx, $logger);
    $handler->handle(new RemoveCollaboardLinkCommand('space_1', 'link_1'));

    expect($space->getCollaboardLinks())->toHaveCount(0);
});
```

- [ ] **Step 2: Create WriteStatusUpdate use case**

Create `public/packages/inno_project_space/src/Application/UseCase/WriteStatusUpdate/WriteStatusUpdateCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate;

final readonly class WriteStatusUpdateCommand
{
    public function __construct(
        public string $spaceId,
        public string $title,
        public string $body,
    ) {}
}
```

Create `public/packages/inno_project_space/src/Port/In/WriteStatusUpdatePort.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate\WriteStatusUpdateCommand;
use Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdate;

interface WriteStatusUpdatePort
{
    public function handle(WriteStatusUpdateCommand $command): StatusUpdate;
}
```

Create `public/packages/inno_project_space/src/Application/UseCase/WriteStatusUpdate/WriteStatusUpdateHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdate;
use Concrete\Package\InnoProjectSpace\Port\In\WriteStatusUpdatePort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\StatusUpdatePublished;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class WriteStatusUpdateHandler implements WriteStatusUpdatePort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(WriteStatusUpdateCommand $command): StatusUpdate
    {
        if (!$this->auth->hasPermission('space.manage')) {
            throw new AuthException('Permission denied for space.manage', ['permission' => 'space.manage'], $this->auth->isAuthenticated());
        }

        return $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $sanitizedBody = strip_tags($command->body, '<p><br><strong><em><ul><ol><li><a><h2><h3><h4>');
            $update = $space->addStatusUpdate(
                id: uniqid('upd_', true),
                title: $command->title,
                body: $sanitizedBody,
                authorId: (string) $this->auth->getUserId(),
            );

            $this->repository->save($space);

            $this->eventDispatcher->dispatch(
                new StatusUpdatePublished($space->getId(), $update->getId(), $update->getAuthorId()),
                StatusUpdatePublished::NAME,
            );

            $this->logger->info('Status update published', ['spaceId' => $command->spaceId, 'updateId' => $update->getId()]);

            return $update;
        });
    }
}
```

Create `tests/unit/inno_project_space/Application/WriteStatusUpdateHandlerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate\WriteStatusUpdateCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate\WriteStatusUpdateHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\StatusUpdatePublished;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() adds status update, saves, and dispatches event', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('hasPermission')->with('space.manage')->andReturn(true);
    $auth->shouldReceive('getUserId')->andReturn(1);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(StatusUpdatePublished::class), StatusUpdatePublished::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new WriteStatusUpdateHandler($repo, $auth, $dispatcher, $tx, $logger);
    $update = $handler->handle(new WriteStatusUpdateCommand('space_1', 'Kickoff', '<p>Done!</p>'));

    expect($update->getTitle())->toBe('Kickoff')
        ->and($space->getStatusUpdates())->toHaveCount(1);
});

test('handle() strips unsafe HTML from body', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->andReturn($space);
    $repo->shouldReceive('save');
    $auth->shouldReceive('hasPermission')->andReturn(true);
    $auth->shouldReceive('getUserId')->andReturn(1);
    $dispatcher->shouldReceive('dispatch');
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new WriteStatusUpdateHandler($repo, $auth, $dispatcher, $tx, $logger);
    $update = $handler->handle(new WriteStatusUpdateCommand('space_1', 'Test', '<p>Safe</p><script>alert("xss")</script>'));

    expect($update->getBody())->toBe('<p>Safe</p>alert("xss")');
});
```

- [ ] **Step 3: Create RequestToJoin use case**

Create `public/packages/inno_project_space/src/Application/UseCase/RequestToJoin/RequestToJoinCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin;

final readonly class RequestToJoinCommand
{
    public function __construct(
        public string $spaceId,
    ) {}
}
```

Create `public/packages/inno_project_space/src/Port/In/RequestToJoinPort.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin\RequestToJoinCommand;
use Concrete\Package\InnoProjectSpace\Domain\Model\MembershipRequest;

interface RequestToJoinPort
{
    public function handle(RequestToJoinCommand $command): MembershipRequest;
}
```

Create `public/packages/inno_project_space/src/Application/UseCase/RequestToJoin/RequestToJoinHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Domain\Model\MembershipRequest;
use Concrete\Package\InnoProjectSpace\Port\In\RequestToJoinPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestReceived;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RequestToJoinHandler implements RequestToJoinPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(RequestToJoinCommand $command): MembershipRequest
    {
        if (!$this->auth->hasPermission('space.join')) {
            throw new AuthException('Permission denied for space.join', ['permission' => 'space.join'], $this->auth->isAuthenticated());
        }

        return $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $request = $space->requestToJoin(
                requestId: uniqid('req_', true),
                userId: (string) $this->auth->getUserId(),
            );

            $this->repository->save($space);

            $this->eventDispatcher->dispatch(
                new JoinRequestReceived($space->getId(), $request->getId(), $request->getUserId()),
                JoinRequestReceived::NAME,
            );

            $this->logger->info('Join request received', ['spaceId' => $command->spaceId, 'requestId' => $request->getId()]);

            return $request;
        });
    }
}
```

Create `tests/unit/inno_project_space/Application/RequestToJoinHandlerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin\RequestToJoinCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin\RequestToJoinHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\RequestStatus;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestReceived;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() creates a pending join request and dispatches event', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->activate();

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('hasPermission')->with('space.join')->andReturn(true);
    $auth->shouldReceive('getUserId')->andReturn(42);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(JoinRequestReceived::class), JoinRequestReceived::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new RequestToJoinHandler($repo, $auth, $dispatcher, $tx, $logger);
    $request = $handler->handle(new RequestToJoinCommand('space_1'));

    expect($request->getStatus())->toBe(RequestStatus::Pending)
        ->and($request->getUserId())->toBe('42');
});
```

- [ ] **Step 4: Create HandleJoinRequest use case**

Create `public/packages/inno_project_space/src/Application/UseCase/HandleJoinRequest/HandleJoinRequestCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest;

final readonly class HandleJoinRequestCommand
{
    public function __construct(
        public string $spaceId,
        public string $requestId,
        public bool $accept,
    ) {}
}
```

Create `public/packages/inno_project_space/src/Port/In/HandleJoinRequestPort.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest\HandleJoinRequestCommand;

interface HandleJoinRequestPort
{
    public function handle(HandleJoinRequestCommand $command): void;
}
```

Create `public/packages/inno_project_space/src/Application/UseCase/HandleJoinRequest/HandleJoinRequestHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\In\HandleJoinRequestPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestAccepted;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class HandleJoinRequestHandler implements HandleJoinRequestPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(HandleJoinRequestCommand $command): void
    {
        if (!$this->auth->hasPermission('space.manage')) {
            throw new AuthException('Permission denied for space.manage', ['permission' => 'space.manage'], $this->auth->isAuthenticated());
        }

        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            if ($command->accept) {
                $request = $space->acceptJoinRequest($command->requestId);
                $this->eventDispatcher->dispatch(
                    new JoinRequestAccepted($space->getId(), $request->getId(), $request->getUserId()),
                    JoinRequestAccepted::NAME,
                );
            } else {
                $request = $space->rejectJoinRequest($command->requestId);
                $this->eventDispatcher->dispatch(
                    new JoinRequestRejected($space->getId(), $request->getId(), $request->getUserId()),
                    JoinRequestRejected::NAME,
                );
            }

            $this->repository->save($space);
            $this->logger->info('Join request handled', ['spaceId' => $command->spaceId, 'requestId' => $command->requestId, 'accepted' => $command->accept]);
        });
    }
}
```

Create `tests/unit/inno_project_space/Application/HandleJoinRequestHandlerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest\HandleJoinRequestCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest\HandleJoinRequestHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\RequestStatus;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestAccepted;
use Concrete\Package\InnoShared\Domain\Event\JoinRequestRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() accepts a join request and dispatches JoinRequestAccepted', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->activate();
    $space->requestToJoin('req_1', 'user_2');

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('hasPermission')->with('space.manage')->andReturn(true);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(JoinRequestAccepted::class), JoinRequestAccepted::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new HandleJoinRequestHandler($repo, $auth, $dispatcher, $tx, $logger);
    $handler->handle(new HandleJoinRequestCommand('space_1', 'req_1', true));

    expect($space->getMemberIds())->toContain('user_2');
});

test('handle() rejects a join request and dispatches JoinRequestRejected', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');
    $space->activate();
    $space->requestToJoin('req_1', 'user_2');

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('hasPermission')->with('space.manage')->andReturn(true);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(JoinRequestRejected::class), JoinRequestRejected::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new HandleJoinRequestHandler($repo, $auth, $dispatcher, $tx, $logger);
    $handler->handle(new HandleJoinRequestCommand('space_1', 'req_1', false));

    expect($space->getMembershipRequests()[0]->getStatus())->toBe(RequestStatus::Rejected);
});
```

- [ ] **Step 5: Create ChangeSpaceStatus use case**

Create `public/packages/inno_project_space/src/Application/UseCase/ChangeSpaceStatus/ChangeSpaceStatusCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\ChangeSpaceStatus;

final readonly class ChangeSpaceStatusCommand
{
    public function __construct(
        public string $spaceId,
        public string $newStatus,
    ) {}
}
```

Create `public/packages/inno_project_space/src/Port/In/ChangeSpaceStatusPort.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\ChangeSpaceStatus\ChangeSpaceStatusCommand;

interface ChangeSpaceStatusPort
{
    public function handle(ChangeSpaceStatusCommand $command): void;
}
```

Create `public/packages/inno_project_space/src/Application/UseCase/ChangeSpaceStatus/ChangeSpaceStatusHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\ChangeSpaceStatus;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
use Concrete\Package\InnoProjectSpace\Port\In\ChangeSpaceStatusPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceActivated;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCompleted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ChangeSpaceStatusHandler implements ChangeSpaceStatusPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ChangeSpaceStatusCommand $command): void
    {
        $newStatus = SpaceStatus::from($command->newStatus);
        $requiredPermission = $newStatus === SpaceStatus::Archived ? 'space.admin' : 'space.manage';

        if (!$this->auth->hasPermission($requiredPermission)) {
            throw new AuthException("Permission denied for {$requiredPermission}", ['permission' => $requiredPermission], $this->auth->isAuthenticated());
        }

        $this->transaction->transactional(function () use ($command, $newStatus) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            match ($newStatus) {
                SpaceStatus::Active => $space->activate(),
                SpaceStatus::Completed => $space->complete(),
                SpaceStatus::Archived => $space->archive(),
                default => throw new \DomainException("Cannot transition to '{$newStatus->value}'."),
            };

            $this->repository->save($space);

            if ($newStatus === SpaceStatus::Active) {
                $this->eventDispatcher->dispatch(
                    new ProjectSpaceActivated($space->getId(), $space->getInitiatorId()),
                    ProjectSpaceActivated::NAME,
                );
            } elseif ($newStatus === SpaceStatus::Completed) {
                $this->eventDispatcher->dispatch(
                    new ProjectSpaceCompleted($space->getId(), $space->getInitiatorId()),
                    ProjectSpaceCompleted::NAME,
                );
            }

            $this->logger->info('Space status changed', ['spaceId' => $command->spaceId, 'newStatus' => $newStatus->value]);
        });
    }
}
```

Create `tests/unit/inno_project_space/Application/ChangeSpaceStatusHandlerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\ChangeSpaceStatus\ChangeSpaceStatusCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\ChangeSpaceStatus\ChangeSpaceStatusHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceActivated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() activates a draft space and dispatches ProjectSpaceActivated', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('hasPermission')->with('space.manage')->andReturn(true);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(ProjectSpaceActivated::class), ProjectSpaceActivated::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new ChangeSpaceStatusHandler($repo, $auth, $dispatcher, $tx, $logger);
    $handler->handle(new ChangeSpaceStatusCommand('space_1', 'active'));

    expect($space->getStatus())->toBe(SpaceStatus::Active);
});
```

- [ ] **Step 6: Create InviteMember use case**

Create `public/packages/inno_project_space/src/Application/UseCase/InviteMember/InviteMemberCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\InviteMember;

final readonly class InviteMemberCommand
{
    public function __construct(
        public string $spaceId,
        public string $userId,
    ) {}
}
```

Create `public/packages/inno_project_space/src/Port/In/InviteMemberPort.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

use Concrete\Package\InnoProjectSpace\Application\UseCase\InviteMember\InviteMemberCommand;

interface InviteMemberPort
{
    public function handle(InviteMemberCommand $command): void;
}
```

Create `public/packages/inno_project_space/src/Application/UseCase/InviteMember/InviteMemberHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\InviteMember;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\In\InviteMemberPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberInvited;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class InviteMemberHandler implements InviteMemberPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(InviteMemberCommand $command): void
    {
        if (!$this->auth->hasPermission('space.manage')) {
            throw new AuthException('Permission denied for space.manage', ['permission' => 'space.manage'], $this->auth->isAuthenticated());
        }

        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $space->addMember($command->userId);
            $this->repository->save($space);

            $this->eventDispatcher->dispatch(
                new MemberInvited($space->getId(), $command->userId, $space->getInitiatorId()),
                MemberInvited::NAME,
            );

            $this->logger->info('Member invited', ['spaceId' => $command->spaceId, 'userId' => $command->userId]);
        });
    }
}
```

Create `tests/unit/inno_project_space/Application/InviteMemberHandlerTest.php`:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\InviteMember\InviteMemberCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\InviteMember\InviteMemberHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\MemberInvited;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() adds member and dispatches MemberInvited event', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1');

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $tx = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('space_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('hasPermission')->with('space.manage')->andReturn(true);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(MemberInvited::class), MemberInvited::NAME);
    $tx->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info');

    $handler = new InviteMemberHandler($repo, $auth, $dispatcher, $tx, $logger);
    $handler->handle(new InviteMemberCommand('space_1', 'user_2'));

    expect($space->getMemberIds())->toContain('user_2');
});
```

- [ ] **Step 7: Run all application tests**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Application/`
Expected: ALL PASS

- [ ] **Step 8: Commit**

```bash
git add public/packages/inno_project_space/src/Application/UseCase/ \
        public/packages/inno_project_space/src/Port/In/ \
        tests/unit/inno_project_space/Application/
git commit -m "feat(project-space): add all use cases (collaboard, status updates, membership, status transitions)"
```

---

### Task 7: Update OnProjectIdeaApproved Event Handler

**Files:**

- Modify: `public/packages/inno_project_space/src/Application/EventHandler/OnProjectIdeaApproved.php`
- Modify: `tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php`

The existing handler needs to pass `description` and the new factory signature.

- [ ] **Step 1: Update test**

Replace `tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php` with:

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\EventHandler\OnProjectIdeaApproved;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCreated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() creates a project space in Draft status and dispatches ProjectSpaceCreated', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);

    $repo->shouldReceive('save')->once()->with(Mockery::on(function (ProjectSpace $space) {
        return $space->getStatus() === SpaceStatus::Draft
            && $space->getInitiatorId() === 'user_1'
            && $space->getMemberIds() === ['user_1'];
    }));
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(ProjectSpaceCreated::class), ProjectSpaceCreated::NAME);
    $logger->shouldReceive('info')->once();
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new OnProjectIdeaApproved($repo, $dispatcher, $logger, $transaction);
    $handler->handle(new ProjectIdeaApproved('idea_1', 'user_1'));
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php`
Expected: FAIL — factory signature mismatch (missing `description` parameter)

- [ ] **Step 3: Update the event handler**

Replace `public/packages/inno_project_space/src/Application/EventHandler/OnProjectIdeaApproved.php` with:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\EventHandler;

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Domain\Event\ProjectSpaceCreated;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OnProjectIdeaApproved
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly TransactionInterface $transaction,
    ) {}

    public function handle(ProjectIdeaApproved $event): void
    {
        $this->transaction->transactional(function () use ($event) {
            $space = ProjectSpace::createFromApprovedIdea(
                id: uniqid('space_', true),
                projectIdeaId: $event->getIdeaId(),
                title: "Project Space – {$event->getIdeaId()}",
                description: '',
                initiatorId: $event->getSubmitterId(),
            );

            $this->repository->save($space);

            $this->eventDispatcher->dispatch(
                new ProjectSpaceCreated($space->getId(), $event->getIdeaId(), $event->getSubmitterId()),
                ProjectSpaceCreated::NAME,
            );

            $this->logger->info('Project space created', [
                'spaceId' => $space->getId(),
                'ideaId' => $event->getIdeaId(),
            ]);
        });
    }
}
```

- [ ] **Step 4: Run test**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php`
Expected: PASS

- [ ] **Step 5: Run full test suite**

Run: `./vendor/bin/pest tests/unit/inno_project_space/`
Expected: ALL PASS

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_project_space/src/Application/EventHandler/OnProjectIdeaApproved.php \
        tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php
git commit -m "feat(project-space): update OnProjectIdeaApproved for new aggregate signature"
```

---

### Task 8: Repository Interface Extension and Persistence Implementation

**Files:**

- Modify: `public/packages/inno_project_space/src/Port/Out/ProjectSpaceRepositoryInterface.php`
- Modify: `public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php`

- [ ] **Step 1: Update repository interface**

Replace `public/packages/inno_project_space/src/Port/Out/ProjectSpaceRepositoryInterface.php` with:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\Out;

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;

interface ProjectSpaceRepositoryInterface
{
    public function findById(string $id): ?ProjectSpace;
    public function findByProjectIdeaId(string $ideaId): ?ProjectSpace;
    /** @return ProjectSpace[] */
    public function findByMemberId(string $userId): array;
    /**
     * @return array{data: ProjectSpace[], total: int}
     */
    public function findPublic(?SpaceStatus $status, int $page, int $limit): array;
    public function save(ProjectSpace $space): void;
}
```

- [ ] **Step 2: Implement ConcreteProjectSpaceRepository**

Replace `public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php` with:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectSpace\Domain\Model\CollaboardLink;
use Concrete\Package\InnoProjectSpace\Domain\Model\MembershipRequest;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\RequestStatus;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
use Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdate;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;

class ConcreteProjectSpaceRepository implements ProjectSpaceRepositoryInterface
{
    public function __construct(private readonly Connection $db) {}

    public function findById(string $id): ?ProjectSpace
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ib_project_spaces WHERE id = ?', [$id]);
        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByProjectIdeaId(string $ideaId): ?ProjectSpace
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ib_project_spaces WHERE project_idea_id = ?', [$ideaId]);
        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByMemberId(string $userId): array
    {
        $spaceIds = $this->db->fetchFirstColumn(
            'SELECT space_id FROM ib_project_space_members WHERE user_id = ? AND request_status = ?',
            [(int) $userId, RequestStatus::Accepted->value],
        );

        return array_map(fn(string $id) => $this->findById($id), $spaceIds);
    }

    public function findPublic(?SpaceStatus $status, int $page, int $limit): array
    {
        $where = 'status IN (?, ?)';
        $params = [SpaceStatus::Active->value, SpaceStatus::Completed->value];

        if ($status !== null) {
            $where = 'status = ?';
            $params = [$status->value];
        }

        $total = (int) $this->db->fetchOne("SELECT COUNT(*) FROM ib_project_spaces WHERE {$where}", $params);
        $offset = ($page - 1) * $limit;

        $rows = $this->db->fetchAllAssociative(
            "SELECT * FROM ib_project_spaces WHERE {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [...$params, $limit, $offset],
        );

        return [
            'data' => array_map(fn(array $row) => $this->hydrate($row), $rows),
            'total' => $total,
        ];
    }

    public function save(ProjectSpace $space): void
    {
        $exists = $this->db->fetchOne('SELECT 1 FROM ib_project_spaces WHERE id = ?', [$space->getId()]);

        if ($exists) {
            $this->db->executeStatement(
                'UPDATE ib_project_spaces SET title = ?, description = ?, status = ?, updated_at = ? WHERE id = ?',
                [$space->getTitle(), $space->getDescription(), $space->getStatus()->value, $space->getUpdatedAt()->format('Y-m-d H:i:s'), $space->getId()],
            );
        } else {
            $this->db->executeStatement(
                'INSERT INTO ib_project_spaces (id, project_idea_id, title, description, status, initiator_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$space->getId(), $space->getProjectIdeaId(), $space->getTitle(), $space->getDescription(), $space->getStatus()->value, (int) $space->getInitiatorId(), $space->getCreatedAt()->format('Y-m-d H:i:s'), $space->getUpdatedAt()->format('Y-m-d H:i:s')],
            );
        }

        $this->syncMembers($space);
        $this->syncCollaboardLinks($space);
        $this->syncStatusUpdates($space);
        $this->syncMembershipRequests($space);
    }

    private function syncMembers(ProjectSpace $space): void
    {
        // Upsert initiator
        $this->db->executeStatement(
            'INSERT IGNORE INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at) VALUES (?, ?, ?, ?, ?, ?)',
            [uniqid('mem_', true), $space->getId(), (int) $space->getInitiatorId(), 'initiator', RequestStatus::Accepted->value, $space->getCreatedAt()->format('Y-m-d H:i:s')],
        );

        // Upsert members
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

    private function syncCollaboardLinks(ProjectSpace $space): void
    {
        $existingIds = $this->db->fetchFirstColumn(
            'SELECT id FROM ib_project_space_collaboards WHERE space_id = ?',
            [$space->getId()],
        );
        $currentIds = array_map(fn(CollaboardLink $l) => $l->getId(), $space->getCollaboardLinks());

        // Delete removed links
        $toDelete = array_diff($existingIds, $currentIds);
        foreach ($toDelete as $id) {
            $this->db->executeStatement('DELETE FROM ib_project_space_collaboards WHERE id = ?', [$id]);
        }

        // Insert new links
        $toInsert = array_diff($currentIds, $existingIds);
        foreach ($space->getCollaboardLinks() as $link) {
            if (in_array($link->getId(), $toInsert, true)) {
                $this->db->executeStatement(
                    'INSERT INTO ib_project_space_collaboards (id, space_id, title, url, added_at) VALUES (?, ?, ?, ?, ?)',
                    [$link->getId(), $space->getId(), $link->getTitle(), $link->getUrl(), $link->getAddedAt()->format('Y-m-d H:i:s')],
                );
            }
        }
    }

    private function syncStatusUpdates(ProjectSpace $space): void
    {
        $existingIds = $this->db->fetchFirstColumn(
            'SELECT id FROM ib_project_space_updates WHERE space_id = ?',
            [$space->getId()],
        );

        foreach ($space->getStatusUpdates() as $update) {
            if (!in_array($update->getId(), $existingIds, true)) {
                $this->db->executeStatement(
                    'INSERT INTO ib_project_space_updates (id, space_id, title, body, author_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                    [$update->getId(), $space->getId(), $update->getTitle(), $update->getBody(), (int) $update->getAuthorId(), $update->getCreatedAt()->format('Y-m-d H:i:s')],
                );
            }
        }
    }

    private function syncMembershipRequests(ProjectSpace $space): void
    {
        foreach ($space->getMembershipRequests() as $request) {
            $exists = $this->db->fetchOne('SELECT 1 FROM ib_project_space_members WHERE id = ?', [$request->getId()]);
            if ($exists) {
                $this->db->executeStatement(
                    'UPDATE ib_project_space_members SET request_status = ?, decided_at = ? WHERE id = ?',
                    [$request->getStatus()->value, $request->getDecidedAt()?->format('Y-m-d H:i:s'), $request->getId()],
                );
            } else {
                $this->db->executeStatement(
                    'INSERT INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at, decided_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$request->getId(), $space->getId(), (int) $request->getUserId(), 'developer', $request->getStatus()->value, $request->getRequestedAt()->format('Y-m-d H:i:s'), $request->getDecidedAt()?->format('Y-m-d H:i:s')],
                );
            }
        }
    }

    private function hydrate(array $row): ProjectSpace
    {
        $spaceId = $row['id'];

        $collaboards = array_map(
            fn(array $r) => CollaboardLink::fromPersistence($r['id'], $r['title'], $r['url'], new \DateTimeImmutable($r['added_at'])),
            $this->db->fetchAllAssociative('SELECT * FROM ib_project_space_collaboards WHERE space_id = ?', [$spaceId]),
        );

        $updates = array_map(
            fn(array $r) => StatusUpdate::fromPersistence($r['id'], $r['title'], $r['body'], (string) $r['author_id'], new \DateTimeImmutable($r['created_at'])),
            $this->db->fetchAllAssociative('SELECT * FROM ib_project_space_updates WHERE space_id = ? ORDER BY created_at DESC', [$spaceId]),
        );

        $memberRows = $this->db->fetchAllAssociative('SELECT * FROM ib_project_space_members WHERE space_id = ?', [$spaceId]);
        $memberIds = [];
        $requests = [];
        foreach ($memberRows as $mr) {
            $status = RequestStatus::from($mr['request_status']);
            if ($status === RequestStatus::Accepted) {
                $memberIds[] = (string) $mr['user_id'];
            }
            if ($mr['role'] === 'developer' && $status !== RequestStatus::Accepted) {
                $requests[] = MembershipRequest::fromPersistence(
                    $mr['id'],
                    (string) $mr['user_id'],
                    $status,
                    new \DateTimeImmutable($mr['requested_at']),
                    $mr['decided_at'] ? new \DateTimeImmutable($mr['decided_at']) : null,
                );
            }
        }

        return ProjectSpace::fromPersistence(
            id: $row['id'],
            projectIdeaId: $row['project_idea_id'],
            title: $row['title'],
            description: $row['description'],
            status: SpaceStatus::from($row['status']),
            initiatorId: (string) $row['initiator_id'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
            memberIds: $memberIds,
            collaboardLinks: $collaboards,
            statusUpdates: $updates,
            membershipRequests: $requests,
        );
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_project_space/src/Port/Out/ProjectSpaceRepositoryInterface.php \
        public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php
git commit -m "feat(project-space): implement repository interface and persistence adapter"
```

---

### Task 9: REST API Controller and Package Registration

**Files:**

- Modify: `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`
- Modify: `public/packages/inno_project_space/controller.php`

- [ ] **Step 1: Rewrite the API controller**

Replace `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php` with the full implementation. The controller injects all 7 Port/In interfaces + repository + auth, delegates to use case handlers, and shapes responses by role. See spec section 6 for all endpoints.

The controller should follow the exact pattern of `ProjectIdeaApiController`: extend `AbstractController`, inject ports via constructor, use `#[OA\...]` attributes for OpenAPI documentation, parse JSON body via `json_decode($request->getContent(), true)`, and return `JsonResponse`.

Key methods:

- `index(Request)` — calls `findPublic()` on repository, shapes public card data
- `show(Request, string $id)` — calls `findById()`, shapes response based on whether user is member/initiator/admin
- `addCollaboard(Request, string $id)` — delegates to `AddCollaboardLinkPort`
- `removeCollaboard(Request, string $id, string $linkId)` — delegates to `RemoveCollaboardLinkPort`
- `addStatusUpdate(Request, string $id)` — delegates to `WriteStatusUpdatePort`
- `joinRequest(Request, string $id)` — delegates to `RequestToJoinPort`
- `handleJoinRequest(Request, string $id, string $requestId)` — delegates to `HandleJoinRequestPort`
- `changeStatus(Request, string $id)` — delegates to `ChangeSpaceStatusPort`
- `invite(Request, string $id)` — delegates to `InviteMemberPort`

- [ ] **Step 2: Update controller.php to bind all ports and register all routes**

Replace `public/packages/inno_project_space/controller.php` with the full version. Key additions:

- Bind all 7 `Port/In` interfaces to their `Handler` implementations via `$this->app->bind()`
- Register all new routes matching spec section 6.2
- Add database table creation SQL in `install()`
- Add table migration in `upgrade()`

The `install()` method should execute the 4 CREATE TABLE statements from spec section 7.

- [ ] **Step 3: Run full test suite to verify nothing is broken**

Run: `./vendor/bin/pest tests/unit/inno_project_space/`
Expected: ALL PASS

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php \
        public/packages/inno_project_space/controller.php
git commit -m "feat(project-space): implement REST API controller and package registration with DB tables"
```

---

### Task 10: Frontend Molecule Components

**Files:**

- Create: `public/packages/inno_frontend/components/molecules/project-card/` (4 files)
- Create: `public/packages/inno_frontend/components/molecules/collaboard-item/` (4 files)
- Create: `public/packages/inno_frontend/components/molecules/status-update-entry/` (4 files)
- Create: `public/packages/inno_frontend/components/molecules/join-request-card/` (4 files)

Each component gets: `<name>.php`, `<name>.variants.php`, `<name>.stories.json`, `<name>.scss`.

Follow the exact pattern of the existing `card` and `member-card` molecules:

- PHP partial receives `$props` array, destructures with defaults, outputs semantic HTML with BEM CSS classes
- Variants file returns `['defaults' => [...]]` array
- Stories JSON file defines Storybook stories with argTypes and server ID
- SCSS uses design tokens (`var(--color-*)`, `var(--space-*)`, etc.) and `~@helpers` imports

- [ ] **Step 1: Create project-card molecule**

`project-card.php` renders a card with: status badge, age, title, description snippet, latest update, stats (Entwickler count, supporter count), Initiator avatar + name, "Mitmachen" button (only for active projects). Uses existing `badge`, `avatar`, and `button` atoms via `include DIR_COMPONENTS . '/atoms/...'`.

- [ ] **Step 2: Create collaboard-item molecule**

`collaboard-item.php` renders a clickable row with: Collaboard icon, title, URL preview, "Offnen" action. Includes a `data-url` attribute for the TypeScript iframe handler.

- [ ] **Step 3: Create status-update-entry molecule**

`status-update-entry.php` renders a timeline entry with: date, author name, title, body (HTML output via `$body` — already sanitized server-side).

- [ ] **Step 4: Create join-request-card molecule**

`join-request-card.php` renders a request row with: avatar, name, accept/reject buttons. Uses `avatar` atom and `button` atom.

- [ ] **Step 5: Register new component SCSS in Storybook bundle**

Add `@use` lines to `public/packages/inno_frontend/components/_storybook/components.scss`:

```scss
// After existing molecules
@use '../molecules/project-card/project-card';
@use '../molecules/collaboard-item/collaboard-item';
@use '../molecules/status-update-entry/status-update-entry';
@use '../molecules/join-request-card/join-request-card';
```

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/project-card/ \
        public/packages/inno_frontend/components/molecules/collaboard-item/ \
        public/packages/inno_frontend/components/molecules/status-update-entry/ \
        public/packages/inno_frontend/components/molecules/join-request-card/ \
        public/packages/inno_frontend/components/_storybook/components.scss
git commit -m "feat(frontend): add project-card, collaboard-item, status-update-entry, join-request-card molecules"
```

---

### Task 11: Frontend Organism Components

**Files:**

- Create: `public/packages/inno_frontend/components/organisms/collaboard-panel/` (4 files)
- Create: `public/packages/inno_frontend/components/organisms/status-update-timeline/` (4 files)
- Create: `public/packages/inno_frontend/components/organisms/project-space-sidebar/` (4 files)

Organisms compose molecules. Follow the pattern of existing organisms (header, footer).

- [ ] **Step 1: Create collaboard-panel organism**

Composes `collaboard-item` molecules into a list with a header ("Collaboards" + "Board hinzufugen" button for initiator). Below the list, an iframe container with toolbar (board title, "In neuem Tab offnen", "Schliessen") — hidden by default, shown via TypeScript when a board is clicked.

- [ ] **Step 2: Create status-update-timeline organism**

Composes `status-update-entry` molecules into a vertical timeline with left border. Header shows "Projekt-Updates" + "+ Update schreiben" button for initiator.

- [ ] **Step 3: Create project-space-sidebar organism**

Shows: team section (Initiator highlighted with `member-card`, Entwickler list), pending requests section (for initiator — `join-request-card` list), and stats section (created date, age, update count, collaboard count).

- [ ] **Step 4: Register organism SCSS in Storybook bundle**

Add to `public/packages/inno_frontend/components/_storybook/components.scss`:

```scss
// After existing organisms
@use '../organisms/collaboard-panel/collaboard-panel';
@use '../organisms/status-update-timeline/status-update-timeline';
@use '../organisms/project-space-sidebar/project-space-sidebar';
```

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/components/organisms/collaboard-panel/ \
        public/packages/inno_frontend/components/organisms/status-update-timeline/ \
        public/packages/inno_frontend/components/organisms/project-space-sidebar/ \
        public/packages/inno_frontend/components/_storybook/components.scss
git commit -m "feat(frontend): add collaboard-panel, status-update-timeline, project-space-sidebar organisms"
```

---

### Task 12: Frontend Single Pages and TypeScript

**Files:**

- Create: `public/packages/inno_frontend/single_pages/projektraeume.php`
- Create: `public/packages/inno_frontend/single_pages/projektraum/view.php`
- Create: `resources/js/single_pages/projektraeume.ts`
- Create: `resources/js/single_pages/projektraum.ts`
- Create: `resources/css/single_pages/projektraeume.scss`
- Create: `resources/css/single_pages/projektraum.scss`

- [ ] **Step 1: Create overview single page**

`projektraeume.php` — follows the `login.php` pattern: PHP template using component includes. Layout: page header, filter tabs (using `tabs` molecule), a container `div` with `id="project-spaces-grid"` that TypeScript populates, and pagination.

- [ ] **Step 2: Create detail single page**

`projektraum/view.php` — PHP template with two-column layout. Left column includes `collaboard-panel` organism and `status-update-timeline` organism. Right column includes `project-space-sidebar` organism. All data loaded dynamically via TypeScript fetch from `/api/project-space/{id}`.

- [ ] **Step 3: Create TypeScript for overview page**

`resources/js/single_pages/projektraeume.ts` — fetches `GET /api/project-space?status=...&page=...&limit=12`, renders `project-card` items into the grid container, handles filter tab clicks and pagination.

- [ ] **Step 4: Create TypeScript for detail page**

`resources/js/single_pages/projektraum.ts` — fetches `GET /api/project-space/{id}`, populates all sections. Handles:

- Collaboard click → show iframe, set `src` attribute
- "Board hinzufugen" → modal with form → `POST /api/project-space/{id}/collaboard`
- "Update schreiben" → modal with WYSIWYG → `POST /api/project-space/{id}/status-update`
- "Mitmachen" → `POST /api/project-space/{id}/join-request`
- Accept/reject → `PUT /api/project-space/{id}/join-request/{requestId}`
- Status change → `PUT /api/project-space/{id}/status`

- [ ] **Step 5: Create SCSS files**

`resources/css/single_pages/projektraeume.scss` — grid layout for cards, filter tabs styling, responsive breakpoints.
`resources/css/single_pages/projektraum.scss` — two-column layout, iframe container styling, timeline styling.

- [ ] **Step 6: Build assets to verify**

Run: `npm run development`
Expected: Build succeeds, files appear in `public/packages/inno_frontend/themes/innovationsbox/js/single_pages/` and `css/single_pages/`

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_frontend/single_pages/projektraeume.php \
        public/packages/inno_frontend/single_pages/projektraum/ \
        resources/js/single_pages/projektraeume.ts \
        resources/js/single_pages/projektraum.ts \
        resources/css/single_pages/projektraeume.scss \
        resources/css/single_pages/projektraum.scss
git commit -m "feat(frontend): add projektraeume overview and projektraum detail single pages"
```

---

### Task 13: OpenAPI Schema Update

**Files:**

- Modify: `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ProjectSpaceDetail.php`

- [ ] **Step 1: Update the schema to match new response shape**

Replace `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ProjectSpaceDetail.php` to include all fields from spec section 6.3 (title, description, status, initiatorId, memberCount, collaboardLinks, statusUpdates, membershipRequests, latestUpdate).

- [ ] **Step 2: Regenerate OpenAPI spec**

Run: `composer openapi:generate`
Expected: Spec regenerated without errors

- [ ] **Step 3: Lint the spec**

Run: `npm run lint:api`
Expected: No errors

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ProjectSpaceDetail.php \
        docs/api/
git commit -m "feat(shared): update ProjectSpaceDetail OpenAPI schema for project space feature"
```

---

### Task 14: Final Verification

- [ ] **Step 1: Run full unit test suite**

Run: `./vendor/bin/pest tests/unit/`
Expected: ALL PASS

- [ ] **Step 2: Run linting**

Run: `composer lint:php`
Expected: No errors

- [ ] **Step 3: Run TypeScript type check**

Run: `npm run typecheck`
Expected: No errors

- [ ] **Step 4: Run production build**

Run: `npm run production`
Expected: Build succeeds

- [ ] **Step 5: Run all lint checks**

Run: `npm run lint:all`
Expected: No errors

- [ ] **Step 6: Commit any remaining fixes**

```bash
git add -A
git commit -m "chore(project-space): final lint and build fixes"
```
