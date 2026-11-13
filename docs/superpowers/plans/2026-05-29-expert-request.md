# Expert Request Feature — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enable project space initiators to request experts from the expert pool, and let experts accept or decline from a dedicated page.

**Architecture:** Extends `inno_project_space` with `ExpertRequest` value object following the existing `MembershipRequest` pattern. New use cases, API endpoints, and frontend additions. Events dispatched to `inno_shared` for future notification support.

**Tech Stack:** PHP 8.1+ (Concrete CMS 9.4), TypeScript, Pest (unit tests), Doctrine DBAL, Laravel Mix/webpack

---

## File Map

### Domain (`inno_project_space`)

| Action | File |
|--------|------|
| Create | `public/packages/inno_project_space/src/Domain/Model/ExpertRequest.php` |
| Create | `public/packages/inno_project_space/src/Domain/Model/ExpertRequestStatus.php` |
| Modify | `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php` — add expertRequests collection + methods |

### Application (`inno_project_space`)

| Action | File |
|--------|------|
| Create | `public/packages/inno_project_space/src/Application/UseCase/RequestExpert/RequestExpertCommand.php` |
| Create | `public/packages/inno_project_space/src/Application/UseCase/RequestExpert/RequestExpertHandler.php` |
| Create | `public/packages/inno_project_space/src/Application/UseCase/HandleExpertRequest/HandleExpertRequestCommand.php` |
| Create | `public/packages/inno_project_space/src/Application/UseCase/HandleExpertRequest/HandleExpertRequestHandler.php` |
| Create | `public/packages/inno_project_space/src/Port/In/RequestExpertPort.php` |
| Create | `public/packages/inno_project_space/src/Port/In/HandleExpertRequestPort.php` |

### Shared Events (`inno_shared`)

| Action | File |
|--------|------|
| Create | `public/packages/inno_shared/src/Domain/Event/ExpertRequested.php` |
| Create | `public/packages/inno_shared/src/Domain/Event/ExpertRequestAccepted.php` |
| Create | `public/packages/inno_shared/src/Domain/Event/ExpertRequestDeclined.php` |

### Adapter (`inno_project_space`)

| Action | File |
|--------|------|
| Modify | `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php` — new endpoints |
| Modify | `public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php` — sync + hydrate expert requests |
| Modify | `public/packages/inno_project_space/controller.php` — routes, DI, DB table |

### Frontend

| Action | File |
|--------|------|
| Modify | `resources/js/single_pages/projektraum.ts` — expert request section in sidebar |
| Create | `public/packages/inno_frontend/single_pages/expert-anfragen.php` |
| Create | `public/packages/inno_frontend/controllers/single_page/expert-anfragen.php` (controller for page) |
| Create | `resources/js/single_pages/expert-anfragen.ts` |
| Create | `resources/css/single_pages/expert-anfragen.scss` |
| Modify | `public/packages/inno_frontend/controller.php` — register page, assets, user menu |
| Modify | `public/packages/inno_frontend/components/molecules/user-menu/user-menu.php` — expert link |

### Tests

| Action | File |
|--------|------|
| Create | `tests/unit/inno_project_space/Domain/ExpertRequestTest.php` |
| Modify | `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php` — expert request methods |
| Create | `tests/unit/inno_project_space/Application/RequestExpertHandlerTest.php` |
| Create | `tests/unit/inno_project_space/Application/HandleExpertRequestHandlerTest.php` |

---

## Task 1: ExpertRequest Value Object + ExpertRequestStatus Enum

**Files:**
- Create: `public/packages/inno_project_space/src/Domain/Model/ExpertRequestStatus.php`
- Create: `public/packages/inno_project_space/src/Domain/Model/ExpertRequest.php`
- Create: `tests/unit/inno_project_space/Domain/ExpertRequestTest.php`

- [ ] **Step 1: Write ExpertRequest tests**

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\ExpertRequest;
use Concrete\Package\InnoProjectSpace\Domain\Model\ExpertRequestStatus;

test('create() builds a pending expert request', function () {
    $req = ExpertRequest::create('req_1', 'expert_5', 'initiator_1', 'Need solar expertise');
    expect($req->getId())->toBe('req_1')
        ->and($req->getExpertUserId())->toBe('expert_5')
        ->and($req->getRequestedByUserId())->toBe('initiator_1')
        ->and($req->getMessage())->toBe('Need solar expertise')
        ->and($req->getStatus())->toBe(ExpertRequestStatus::Pending)
        ->and($req->getRequestedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($req->getDecidedAt())->toBeNull();
});

test('create() with null message', function () {
    $req = ExpertRequest::create('req_2', 'expert_5', 'initiator_1', null);
    expect($req->getMessage())->toBeNull();
});

test('accept() changes status to Accepted and sets decidedAt', function () {
    $req = ExpertRequest::create('req_1', 'expert_5', 'initiator_1', null);
    $req->accept();
    expect($req->getStatus())->toBe(ExpertRequestStatus::Accepted)
        ->and($req->getDecidedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('decline() changes status to Declined and sets decidedAt', function () {
    $req = ExpertRequest::create('req_1', 'expert_5', 'initiator_1', null);
    $req->decline();
    expect($req->getStatus())->toBe(ExpertRequestStatus::Declined)
        ->and($req->getDecidedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('fromPersistence() reconstructs all fields', function () {
    $requested = new DateTimeImmutable('2026-01-01');
    $decided = new DateTimeImmutable('2026-01-02');
    $req = ExpertRequest::fromPersistence('req_1', 'expert_5', 'initiator_1', 'Help needed', ExpertRequestStatus::Accepted, $requested, $decided);
    expect($req->getId())->toBe('req_1')
        ->and($req->getStatus())->toBe(ExpertRequestStatus::Accepted)
        ->and($req->getRequestedAt())->toBe($requested)
        ->and($req->getDecidedAt())->toBe($decided);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/ExpertRequestTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create ExpertRequestStatus enum**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

enum ExpertRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
}
```

- [ ] **Step 4: Create ExpertRequest value object**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

class ExpertRequest
{
    private function __construct(
        private readonly string $id,
        private readonly string $expertUserId,
        private readonly string $requestedByUserId,
        private readonly ?string $message,
        private ExpertRequestStatus $status,
        private readonly \DateTimeImmutable $requestedAt,
        private ?\DateTimeImmutable $decidedAt,
    ) {}

    public static function create(string $id, string $expertUserId, string $requestedByUserId, ?string $message): self
    {
        return new self($id, $expertUserId, $requestedByUserId, $message, ExpertRequestStatus::Pending, new \DateTimeImmutable(), null);
    }

    public static function fromPersistence(
        string $id,
        string $expertUserId,
        string $requestedByUserId,
        ?string $message,
        ExpertRequestStatus $status,
        \DateTimeImmutable $requestedAt,
        ?\DateTimeImmutable $decidedAt,
    ): self {
        return new self($id, $expertUserId, $requestedByUserId, $message, $status, $requestedAt, $decidedAt);
    }

    public function accept(): void
    {
        $this->status = ExpertRequestStatus::Accepted;
        $this->decidedAt = new \DateTimeImmutable();
    }

    public function decline(): void
    {
        $this->status = ExpertRequestStatus::Declined;
        $this->decidedAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getExpertUserId(): string { return $this->expertUserId; }
    public function getRequestedByUserId(): string { return $this->requestedByUserId; }
    public function getMessage(): ?string { return $this->message; }
    public function getStatus(): ExpertRequestStatus { return $this->status; }
    public function getRequestedAt(): \DateTimeImmutable { return $this->requestedAt; }
    public function getDecidedAt(): ?\DateTimeImmutable { return $this->decidedAt; }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/ExpertRequestTest.php`
Expected: 5 tests PASS

- [ ] **Step 6: Run full test suite**

Run: `./vendor/bin/pest`
Expected: All PASS

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_project_space/src/Domain/Model/ExpertRequest.php \
        public/packages/inno_project_space/src/Domain/Model/ExpertRequestStatus.php \
        tests/unit/inno_project_space/Domain/ExpertRequestTest.php
git commit -m "feat(project-space): add ExpertRequest value object and ExpertRequestStatus enum"
```

---

## Task 2: Extend ProjectSpace with Expert Request Methods

**Files:**
- Modify: `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php`
- Modify: `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php`

- [ ] **Step 1: Write new tests**

APPEND to `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php`:

```php
use Concrete\Package\InnoProjectSpace\Domain\Model\ExpertRequestStatus;

test('requestExpert() creates a pending expert request', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', 'user_1', 'cat_1');
    $req = $space->requestExpert('req_1', 'expert_5', 'user_1', 'Need help');
    expect($req->getExpertUserId())->toBe('expert_5')
        ->and($req->getStatus())->toBe(ExpertRequestStatus::Pending)
        ->and($space->getExpertRequests())->toHaveCount(1);
});

test('requestExpert() throws if expert is already a member', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', 'user_1', 'cat_1');
    $space->addMember('expert_5');
    $space->requestExpert('req_1', 'expert_5', 'user_1', null);
})->throws(\DomainException::class);

test('requestExpert() throws if expert already has a pending request', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', 'user_1', 'cat_1');
    $space->requestExpert('req_1', 'expert_5', 'user_1', null);
    $space->requestExpert('req_2', 'expert_5', 'user_1', null);
})->throws(\DomainException::class);

test('acceptExpertRequest() adds expert to members', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', 'user_1', 'cat_1');
    $space->requestExpert('req_1', 'expert_5', 'user_1', null);
    $req = $space->acceptExpertRequest('req_1');
    expect($req->getStatus())->toBe(ExpertRequestStatus::Accepted)
        ->and($space->getMemberIds())->toContain('expert_5');
});

test('declineExpertRequest() does not add expert to members', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', 'user_1', 'cat_1');
    $space->requestExpert('req_1', 'expert_5', 'user_1', null);
    $req = $space->declineExpertRequest('req_1');
    expect($req->getStatus())->toBe(ExpertRequestStatus::Declined)
        ->and($space->getMemberIds())->not->toContain('expert_5');
});
```

- [ ] **Step 2: Run tests to verify they fail**

- [ ] **Step 3: Extend ProjectSpace model**

Add to `ProjectSpace.php`:
- New private property: `private array $expertRequests = [];` (initialized in constructor, populated in `fromPersistence`)
- Add `expertRequests` parameter to `fromPersistence()` (with default `[]`)
- New methods:

```php
public function requestExpert(string $id, string $expertUserId, string $requestedByUserId, ?string $message): ExpertRequest
{
    if (in_array($expertUserId, $this->memberIds, true)) {
        throw new \DomainException("User '{$expertUserId}' is already a member of this space.");
    }
    foreach ($this->expertRequests as $req) {
        if ($req->getExpertUserId() === $expertUserId && $req->getStatus() === ExpertRequestStatus::Pending) {
            throw new \DomainException("Expert '{$expertUserId}' already has a pending request.");
        }
    }
    $request = ExpertRequest::create($id, $expertUserId, $requestedByUserId, $message);
    $this->expertRequests[] = $request;
    $this->updatedAt = new \DateTimeImmutable();
    return $request;
}

public function acceptExpertRequest(string $requestId): ExpertRequest
{
    $request = $this->findExpertRequest($requestId);
    $request->accept();
    $this->addMember($request->getExpertUserId());
    return $request;
}

public function declineExpertRequest(string $requestId): ExpertRequest
{
    $request = $this->findExpertRequest($requestId);
    $request->decline();
    return $request;
}

/** @return ExpertRequest[] */
public function getExpertRequests(): array
{
    return $this->expertRequests;
}

private function findExpertRequest(string $requestId): ExpertRequest
{
    foreach ($this->expertRequests as $req) {
        if ($req->getId() === $requestId) {
            return $req;
        }
    }
    throw new \DomainException("Expert request '{$requestId}' not found.");
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/`
Expected: All PASS

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php \
        tests/unit/inno_project_space/Domain/ProjectSpaceTest.php
git commit -m "feat(project-space): add expert request methods to ProjectSpace aggregate"
```

---

## Task 3: Shared Events

**Files:**
- Create: `public/packages/inno_shared/src/Domain/Event/ExpertRequested.php`
- Create: `public/packages/inno_shared/src/Domain/Event/ExpertRequestAccepted.php`
- Create: `public/packages/inno_shared/src/Domain/Event/ExpertRequestDeclined.php`

- [ ] **Step 1: Create ExpertRequested event**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ExpertRequested extends Event
{
    public const NAME = 'innovationsbox.expert.requested';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $expertUserId,
        private readonly string $requestedByUserId,
        private readonly ?string $message,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getExpertUserId(): string { return $this->expertUserId; }
    public function getRequestedByUserId(): string { return $this->requestedByUserId; }
    public function getMessage(): ?string { return $this->message; }
}
```

- [ ] **Step 2: Create ExpertRequestAccepted event**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ExpertRequestAccepted extends Event
{
    public const NAME = 'innovationsbox.expert.request_accepted';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $expertUserId,
        private readonly string $requestId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getExpertUserId(): string { return $this->expertUserId; }
    public function getRequestId(): string { return $this->requestId; }
}
```

- [ ] **Step 3: Create ExpertRequestDeclined event**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ExpertRequestDeclined extends Event
{
    public const NAME = 'innovationsbox.expert.request_declined';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $expertUserId,
        private readonly string $requestId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getExpertUserId(): string { return $this->expertUserId; }
    public function getRequestId(): string { return $this->requestId; }
}
```

- [ ] **Step 4: Run all tests**

Run: `./vendor/bin/pest`
Expected: All PASS

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_shared/src/Domain/Event/ExpertRequested.php \
        public/packages/inno_shared/src/Domain/Event/ExpertRequestAccepted.php \
        public/packages/inno_shared/src/Domain/Event/ExpertRequestDeclined.php
git commit -m "feat(shared): add ExpertRequested, ExpertRequestAccepted, ExpertRequestDeclined events"
```

---

## Task 4: Use Cases — RequestExpert + HandleExpertRequest

**Files:**
- Create: `public/packages/inno_project_space/src/Application/UseCase/RequestExpert/RequestExpertCommand.php`
- Create: `public/packages/inno_project_space/src/Application/UseCase/RequestExpert/RequestExpertHandler.php`
- Create: `public/packages/inno_project_space/src/Port/In/RequestExpertPort.php`
- Create: `public/packages/inno_project_space/src/Application/UseCase/HandleExpertRequest/HandleExpertRequestCommand.php`
- Create: `public/packages/inno_project_space/src/Application/UseCase/HandleExpertRequest/HandleExpertRequestHandler.php`
- Create: `public/packages/inno_project_space/src/Port/In/HandleExpertRequestPort.php`
- Create: `tests/unit/inno_project_space/Application/RequestExpertHandlerTest.php`
- Create: `tests/unit/inno_project_space/Application/HandleExpertRequestHandlerTest.php`

- [ ] **Step 1: Write RequestExpertHandlerTest**

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert\RequestExpertCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert\RequestExpertHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() creates expert request and dispatches event', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', '1', 'cat_1');
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('s_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('getUserId')->andReturn(1);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $auth->shouldReceive('hasPermission')->with('space.admin')->andReturn(false);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(ExpertRequested::class), ExpertRequested::NAME);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new RequestExpertHandler($repo, $auth, $dispatcher, $transaction, $logger);
    $handler->handle(new RequestExpertCommand('s_1', 'expert_5', 'Need solar help'));

    expect($space->getExpertRequests())->toHaveCount(1);
});
```

- [ ] **Step 2: Write HandleExpertRequestHandlerTest**

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest\HandleExpertRequestCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest\HandleExpertRequestHandler;
use Concrete\Package\InnoProjectSpace\Domain\Model\ExpertRequestStatus;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequestAccepted;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequestDeclined;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn() => Mockery::close());

test('handle() accept adds expert to members', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', '1', 'cat_1');
    $space->requestExpert('req_1', '5', '1', null);

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('s_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('getUserId')->andReturn(5);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(ExpertRequestAccepted::class), ExpertRequestAccepted::NAME);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new HandleExpertRequestHandler($repo, $auth, $dispatcher, $transaction, $logger);
    $handler->handle(new HandleExpertRequestCommand('s_1', 'req_1', true));

    expect($space->getMemberIds())->toContain('5');
});

test('handle() decline does not add expert to members', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', '1', 'cat_1');
    $space->requestExpert('req_1', '5', '1', null);

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('s_1')->andReturn($space);
    $repo->shouldReceive('save')->once();
    $auth->shouldReceive('getUserId')->andReturn(5);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $dispatcher->shouldReceive('dispatch')->once()->with(Mockery::type(ExpertRequestDeclined::class), ExpertRequestDeclined::NAME);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());
    $logger->shouldReceive('info')->once();

    $handler = new HandleExpertRequestHandler($repo, $auth, $dispatcher, $transaction, $logger);
    $handler->handle(new HandleExpertRequestCommand('s_1', 'req_1', false));

    expect($space->getMemberIds())->not->toContain('5');
});

test('handle() throws if user is not the requested expert', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', '1', 'cat_1');
    $space->requestExpert('req_1', '5', '1', null);

    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $auth = Mockery::mock(AuthInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $transaction = Mockery::mock(TransactionInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $repo->shouldReceive('findById')->with('s_1')->andReturn($space);
    $auth->shouldReceive('getUserId')->andReturn(999);
    $auth->shouldReceive('isAuthenticated')->andReturn(true);
    $transaction->shouldReceive('transactional')->andReturnUsing(fn(callable $fn) => $fn());

    $handler = new HandleExpertRequestHandler($repo, $auth, $dispatcher, $transaction, $logger);
    $handler->handle(new HandleExpertRequestCommand('s_1', 'req_1', true));
})->throws(\Concrete\Package\InnoShared\Infrastructure\Exception\AuthException::class);
```

- [ ] **Step 3: Run tests to verify they fail**

- [ ] **Step 4: Create ports**

`RequestExpertPort.php`:
```php
<?php
declare(strict_types=1);
namespace Concrete\Package\InnoProjectSpace\Port\In;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert\RequestExpertCommand;
interface RequestExpertPort
{
    public function handle(RequestExpertCommand $command): void;
}
```

`HandleExpertRequestPort.php`:
```php
<?php
declare(strict_types=1);
namespace Concrete\Package\InnoProjectSpace\Port\In;
use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest\HandleExpertRequestCommand;
interface HandleExpertRequestPort
{
    public function handle(HandleExpertRequestCommand $command): void;
}
```

- [ ] **Step 5: Create commands**

`RequestExpertCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert;
final class RequestExpertCommand
{
    public function __construct(
        public readonly string $spaceId,
        public readonly string $expertUserId,
        public readonly ?string $message = null,
    ) {}
}
```

`HandleExpertRequestCommand.php`:
```php
<?php
declare(strict_types=1);
namespace Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest;
final class HandleExpertRequestCommand
{
    public function __construct(
        public readonly string $spaceId,
        public readonly string $requestId,
        public readonly bool $accept,
    ) {}
}
```

- [ ] **Step 6: Create handlers**

`RequestExpertHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\In\RequestExpertPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RequestExpertHandler implements RequestExpertPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(RequestExpertCommand $command): void
    {
        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            $userId = (string) $this->auth->getUserId();
            if ($space->getInitiatorId() !== $userId && !$this->auth->hasPermission('space.admin')) {
                throw new AuthException('Only the initiator or admin can request experts', [], $this->auth->isAuthenticated());
            }

            $space->requestExpert(
                id: uniqid('ereq_', true),
                expertUserId: $command->expertUserId,
                requestedByUserId: $userId,
                message: $command->message,
            );
            $this->repository->save($space);

            $this->eventDispatcher->dispatch(
                new ExpertRequested($space->getId(), $command->expertUserId, $userId, $command->message),
                ExpertRequested::NAME,
            );
            $this->logger->info('Expert requested', ['spaceId' => $space->getId(), 'expertUserId' => $command->expertUserId]);
        });
    }
}
```

`HandleExpertRequestHandler.php`:
```php
<?php
declare(strict_types=1);
namespace Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest;

use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\In\HandleExpertRequestPort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequestAccepted;
use Concrete\Package\InnoShared\Domain\Event\ExpertRequestDeclined;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class HandleExpertRequestHandler implements HandleExpertRequestPort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionInterface $transaction,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(HandleExpertRequestCommand $command): void
    {
        $this->transaction->transactional(function () use ($command) {
            $space = $this->repository->findById($command->spaceId);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($command->spaceId);
            }

            // Find the request to verify the current user is the expert
            $request = null;
            foreach ($space->getExpertRequests() as $req) {
                if ($req->getId() === $command->requestId) {
                    $request = $req;
                    break;
                }
            }
            if ($request === null) {
                throw new \DomainException("Expert request '{$command->requestId}' not found.");
            }

            $userId = (string) $this->auth->getUserId();
            if ($request->getExpertUserId() !== $userId) {
                throw new AuthException('Only the requested expert can respond', [], $this->auth->isAuthenticated());
            }

            if ($command->accept) {
                $space->acceptExpertRequest($command->requestId);
                $this->eventDispatcher->dispatch(
                    new ExpertRequestAccepted($space->getId(), $userId, $command->requestId),
                    ExpertRequestAccepted::NAME,
                );
            } else {
                $space->declineExpertRequest($command->requestId);
                $this->eventDispatcher->dispatch(
                    new ExpertRequestDeclined($space->getId(), $userId, $command->requestId),
                    ExpertRequestDeclined::NAME,
                );
            }

            $this->repository->save($space);
            $this->logger->info('Expert request handled', ['requestId' => $command->requestId, 'accepted' => $command->accept]);
        });
    }
}
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `./vendor/bin/pest`
Expected: All PASS

- [ ] **Step 8: Commit**

```bash
git add public/packages/inno_project_space/src/Application/UseCase/RequestExpert/ \
        public/packages/inno_project_space/src/Application/UseCase/HandleExpertRequest/ \
        public/packages/inno_project_space/src/Port/In/RequestExpertPort.php \
        public/packages/inno_project_space/src/Port/In/HandleExpertRequestPort.php \
        tests/unit/inno_project_space/Application/
git commit -m "feat(project-space): add RequestExpert and HandleExpertRequest use cases"
```

---

## Task 5: Repository — Persist + Hydrate Expert Requests

**Files:**
- Modify: `public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php`
- Modify: `public/packages/inno_project_space/controller.php` — DB table + routes + DI

- [ ] **Step 1: Add syncExpertRequests to repository**

Add to `ConcreteProjectSpaceRepository.php` a `syncExpertRequests(ProjectSpace $space)` method following the `syncMembershipRequests` pattern. It upserts rows in `ib_expert_requests`.

Also add `loadExpertRequests(string $spaceId)` to hydrate, and pass results to `ProjectSpace::fromPersistence()`.

- [ ] **Step 2: Add DB table creation + routes + DI to controller.php**

In `install()` and `upgrade()`: create `ib_expert_requests` table.

In `on_start()`: add DI bindings for `RequestExpertPort` and `HandleExpertRequestPort`. Add routes:
```php
$router->register('/api/project-space/{id}/experts', "{$ctrl}::listExperts", 'api.project_space.list_experts', [], [], '', [], ['GET']);
$router->register('/api/project-space/{id}/expert-request', "{$ctrl}::requestExpert", 'api.project_space.request_expert', [], [], '', [], ['POST']);
$router->register('/api/project-space/{id}/expert-request/{requestId}', "{$ctrl}::handleExpertRequest", 'api.project_space.handle_expert_request', [], [], '', [], ['PUT']);
$router->register('/api/expert-request/mine', "{$ctrl}::myExpertRequests", 'api.expert_request.mine', [], [], '', [], ['GET']);
```

IMPORTANT: `/api/expert-request/mine` must be registered BEFORE `/api/project-space/{id}` routes to avoid wildcard conflicts. Also `/api/project-space/{id}/experts` and `/api/project-space/{id}/expert-request` must be BEFORE `/api/project-space/{id}/expert-request/{requestId}`.

- [ ] **Step 3: Run tests and commit**

```bash
git add public/packages/inno_project_space/
git commit -m "feat(project-space): persist expert requests and register routes"
```

---

## Task 6: API Controller — Expert Endpoints

**Files:**
- Modify: `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`

- [ ] **Step 1: Add constructor dependencies**

Add `RequestExpertPort` and `HandleExpertRequestPort` to the constructor.

- [ ] **Step 2: Add `listExperts()` endpoint**

Query CMS users in the "Experte" group. Filter out users already members of the space and users with pending expert requests. Return `{data: [{userId, name, expertise}]}`.

- [ ] **Step 3: Add `requestExpert()` endpoint**

Read `{expertUserId, message?}` from request body. Call `$this->requestExpertPort->handle()`. Wrap in try/catch.

- [ ] **Step 4: Add `handleExpertRequest()` endpoint**

Read `{accept: boolean}` from request body. Call `$this->handleExpertRequestPort->handle()`. Wrap in try/catch.

- [ ] **Step 5: Add `myExpertRequests()` endpoint**

Query `ib_expert_requests` where `expert_user_id = current user` and status in ('pending', 'accepted', 'declined'). Join with space data to get titles. Return enriched data.

- [ ] **Step 6: Enrich `show()` response**

Add `expertRequests` array to the response for initiator/admin, showing pending requests with expert names and expertise.

- [ ] **Step 7: Run all tests and commit**

```bash
git add public/packages/inno_project_space/
git commit -m "feat(project-space): add expert request API endpoints"
```

---

## Task 7: Frontend — Expert Request Section in Projektraum Sidebar

**Files:**
- Modify: `resources/js/single_pages/projektraum.ts`

- [ ] **Step 1: Add interfaces**

Add `ExpertRequestData` and `ExpertOption` interfaces to the TypeScript.

- [ ] **Step 2: Add `renderExpertRequests()` function**

In the sidebar, after the members section (visible to initiator/admin only):
- Fetch available experts from `GET /api/project-space/{id}/experts`
- Render a select dropdown with expert name + expertise
- Optional message textarea
- "Anfragen" button
- List of pending requests with "Ausstehend" badge

- [ ] **Step 3: Build and commit**

```bash
npm run development
git add resources/js/single_pages/projektraum.ts \
        public/packages/inno_frontend/themes/innovationsbox/js/single_pages/projektraum.js
git commit -m "feat(frontend): add expert request section in Projektraum sidebar"
```

---

## Task 8: Frontend — "Meine Anfragen" Page

**Files:**
- Create: `public/packages/inno_frontend/single_pages/expert-anfragen.php`
- Create: `public/packages/inno_frontend/controllers/single_page/expert-anfragen.php`
- Create: `resources/js/single_pages/expert-anfragen.ts`
- Create: `resources/css/single_pages/expert-anfragen.scss`

- [ ] **Step 1: Create PHP template**

Auth check (must be logged in), empty container for TS to populate.

- [ ] **Step 2: Create page controller**

Load `inno_frontend/expert-anfragen` asset group.

- [ ] **Step 3: Create TypeScript**

Fetch from `GET /api/expert-request/mine`. Render cards with space title, initiator, message, date, accept/decline buttons. Handle accept/decline via `PUT /api/project-space/{spaceId}/expert-request/{requestId}`.

- [ ] **Step 4: Create SCSS**

Card styles following the existing pattern (similar to idea cards on "Meine Ideen").

- [ ] **Step 5: Build and commit**

```bash
npm run typecheck && npm run development
git add public/packages/inno_frontend/single_pages/expert-anfragen.php \
        public/packages/inno_frontend/controllers/single_page/expert-anfragen.php \
        resources/js/single_pages/expert-anfragen.ts \
        resources/css/single_pages/expert-anfragen.scss \
        public/packages/inno_frontend/themes/innovationsbox/
git commit -m "feat(frontend): add Meine Anfragen page for experts"
```

---

## Task 9: Register Page + User Menu + Assets

**Files:**
- Modify: `public/packages/inno_frontend/controller.php` — register page, asset group
- Modify: `public/packages/inno_frontend/components/molecules/user-menu/user-menu.php` — add expert link

- [ ] **Step 1: Register single page and assets**

In `installSinglePages()`: add `/expert-anfragen` with `excludeNav => true`.
In `on_start()`: register CSS/JS assets and asset group for `inno_frontend/expert-anfragen`.

- [ ] **Step 2: Add user menu link for experts**

Add "Meine Anfragen" link visible only to users in the "Experte" group (same pattern as "Moderation" for moderators).

- [ ] **Step 3: Update package, build, commit**

```bash
npm run production
./vendor/bin/concrete c5:package:update inno_frontend --force
./vendor/bin/concrete c5:package:update inno_project_space --force
./vendor/bin/concrete c5:clear-cache
git add public/packages/inno_frontend/ resources/
git commit -m "feat(frontend): register expert-anfragen page, assets, and user menu link"
```

---

## Task 10: Seed Data + Final Verification

**Files:**
- Modify: `scripts/seed-testdata.sh`

- [ ] **Step 1: Add seed data for expert requests**

```sql
TRUNCATE TABLE ib_expert_requests;
INSERT INTO ib_expert_requests (id, space_id, expert_user_id, requested_by_user_id, message, status, requested_at, decided_at) VALUES
('test_ereq_1', 'test_space_1', '4', '2', 'Wir brauchen deine Expertise im Bereich Solarenergie.', 'pending', DATE_SUB(NOW(), INTERVAL 5 DAY), NULL),
('test_ereq_2', 'test_space_2', '4', '3', NULL, 'accepted', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY));
```

- [ ] **Step 2: Run full test suite**

```bash
./vendor/bin/pest && npm run typecheck && npm run lint:all
```

- [ ] **Step 3: Commit**

```bash
git add scripts/seed-testdata.sh
git commit -m "feat: add seed data for expert requests"
```

- [ ] **Step 4: Manual smoke test**

1. Log in as initiator, open a Projektraum → sidebar should show "Experte anfragen" section
2. Select an expert, optionally add message, click "Anfragen"
3. Log in as expert → user menu shows "Meine Anfragen"
4. Open "Meine Anfragen" → pending request visible with accept/decline
5. Accept → refresh Projektraum → expert appears in member list
