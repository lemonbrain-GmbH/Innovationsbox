# Hexagonal Architecture Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor all five domain packages from Clean Architecture layers to genuine Hexagonal Architecture (Ports & Adapters), extract cross-package event contracts into a new `inno_shared` package, and centralise all tests at the repo root.

**Architecture:** Each domain package gets explicit `Port/In/` (primary ports = use-case interfaces), `Port/Out/` (secondary ports = repository/service interfaces), `Adapter/In/` (HTTP controllers), and `Adapter/Out/` (DB/email adapters). `Application/` is the hexagon centre. No domain package imports from another domain package; all shared event contracts live in `inno_shared`.

**Tech Stack:** PHP 8.1+, Concrete CMS 9.4, Symfony EventDispatcher, Mockery, Pest v1, Playwright (TypeScript)

**Spec:** `docs/superpowers/specs/2026-04-13-hexagonal-architecture-refactor-design.md`

---

## Task 1: Create `inno_shared` package

**Files:**
- Create: `public/packages/inno_shared/controller.php`
- Create: `public/packages/inno_shared/composer.json`
- Create: `public/packages/inno_shared/src/Event/ProjectIdeaSubmitted.php`
- Create: `public/packages/inno_shared/src/Event/ProjectIdeaApproved.php`
- Create: `public/packages/inno_shared/src/Event/ProjectIdeaRejected.php`
- Create: `public/packages/inno_shared/src/Event/ModerationDecisionMade.php`
- Create: `public/packages/inno_shared/src/Event/ProjectSpaceCreated.php`

- [ ] **Step 1: Create the package directory structure**

```bash
mkdir -p public/packages/inno_shared/src/Event
```

- [ ] **Step 2: Create `public/packages/inno_shared/controller.php`**

```php
<?php
namespace Concrete\Package\InnoShared;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Package\Package;

class Controller extends Package
{
    protected $pkgHandle = 'inno_shared';
    protected $appVersionRequired = '9.4.0';
    protected $pkgVersion = '0.1.0';

    public function getPackageDescription(): string
    {
        return t('Shared kernel: cross-package event contracts for the Innovationsbox platform.');
    }

    public function getPackageName(): string
    {
        return t('Innovationsbox – Shared Kernel');
    }

    public function on_start(): void
    {
        // No bindings, no routes. This package only provides event contract classes.
    }
}
```

- [ ] **Step 3: Create `public/packages/inno_shared/composer.json`**

```json
{
  "name": "innovationsbox/inno-shared",
  "description": "Shared kernel: cross-package event contracts for Innovationsbox",
  "type": "concrete5-package",
  "license": "MIT",
  "minimum-stability": "stable",
  "repositories": [
    {
      "type": "composer",
      "url": "https://packagist.lemonbrain.ch"
    }
  ],
  "require": {
    "concrete5/core": "^9.4"
  }
}
```

- [ ] **Step 4: Create `public/packages/inno_shared/src/Event/ProjectIdeaSubmitted.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoShared\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a new project idea is submitted.
 * Consumers: moderation (creates review), notification (notifies admins).
 */
class ProjectIdeaSubmitted extends Event
{
    public const NAME = 'innovationsbox.project_idea.submitted';

    public function __construct(
        private readonly string $ideaId,
        private readonly string $submitterId,
        private readonly string $title,
    ) {}

    public function getIdeaId(): string { return $this->ideaId; }
    public function getSubmitterId(): string { return $this->submitterId; }
    public function getTitle(): string { return $this->title; }
}
```

- [ ] **Step 5: Create `public/packages/inno_shared/src/Event/ProjectIdeaApproved.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoShared\Event;

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
    ) {}

    public function getIdeaId(): string { return $this->ideaId; }
    public function getSubmitterId(): string { return $this->submitterId; }
}
```

- [ ] **Step 6: Create `public/packages/inno_shared/src/Event/ProjectIdeaRejected.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoShared\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a moderation decision rejects a project idea.
 * Consumers: notification (notifies submitter with reason).
 */
class ProjectIdeaRejected extends Event
{
    public const NAME = 'innovationsbox.project_idea.rejected';

    public function __construct(
        private readonly string $ideaId,
        private readonly string $submitterId,
        private readonly string $reason,
    ) {}

    public function getIdeaId(): string { return $this->ideaId; }
    public function getSubmitterId(): string { return $this->submitterId; }
    public function getReason(): string { return $this->reason; }
}
```

- [ ] **Step 7: Create `public/packages/inno_shared/src/Event/ModerationDecisionMade.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoShared\Event;

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
    ) {}

    public function getIdeaId(): string { return $this->ideaId; }
    public function getReviewerId(): string { return $this->reviewerId; }
    public function isApproved(): bool { return $this->approved; }
    public function getReason(): string { return $this->reason; }
}
```

- [ ] **Step 8: Create `public/packages/inno_shared/src/Event/ProjectSpaceCreated.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoShared\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a project space is created for an approved idea.
 * Consumers: notification (notifies the submitter).
 */
class ProjectSpaceCreated extends Event
{
    public const NAME = 'innovationsbox.project_space.created';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $projectIdeaId,
        private readonly string $initiatorId,
    ) {}

    public function getSpaceId(): string { return $this->spaceId; }
    public function getProjectIdeaId(): string { return $this->projectIdeaId; }
    public function getInitiatorId(): string { return $this->initiatorId; }
}
```

- [ ] **Step 9: Commit**

```bash
git add public/packages/inno_shared/
git commit -m "feat: add inno_shared package with cross-package event contracts"
```

---

## Task 2: Set up root test infrastructure

**Files:**
- Modify: `composer.json`
- Create: `phpunit.xml`

- [ ] **Step 1: Add `autoload-dev` to `composer.json`**

Open `composer.json` and add an `autoload-dev` section. The full updated relevant portion:

```json
{
  "require-dev": {
    "mockery/mockery": "^1",
    "pestphp/pest": "^1.23"
  },
  "autoload-dev": {
    "psr-4": {
      "Concrete\\Package\\InnoShared\\": "./public/packages/inno_shared/src",
      "Concrete\\Package\\InnoProjectIdea\\": "./public/packages/inno_project_idea/src",
      "Concrete\\Package\\InnoModeration\\": "./public/packages/inno_moderation/src",
      "Concrete\\Package\\InnoNotification\\": "./public/packages/inno_notification/src",
      "Concrete\\Package\\InnoProjectSpace\\": "./public/packages/inno_project_space/src",
      "Tests\\": "./tests"
    }
  },
  "scripts": {
    "test:unit": "pest --testsuite=Unit",
    "test:integration": "pest --testsuite=Integration",
    "test": ["@test:unit"]
  }
}
```

- [ ] **Step 2: Create `phpunit.xml` at project root**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 3: Create the root tests directory structure**

```bash
mkdir -p tests/unit/inno_project_idea/Domain
mkdir -p tests/unit/inno_project_idea/Application
mkdir -p tests/unit/inno_moderation/Domain
mkdir -p tests/unit/inno_moderation/Application
mkdir -p tests/unit/inno_notification/Application
mkdir -p tests/unit/inno_project_space/Domain
mkdir -p tests/unit/inno_project_space/Application
mkdir -p tests/integration/inno_project_idea/Adapter/Out/Persistence
mkdir -p tests/integration/inno_moderation/Adapter/Out/Persistence
mkdir -p tests/integration/inno_notification/Adapter/Out/Email
mkdir -p tests/e2e/fixtures
mkdir -p tests/e2e/specs
```

- [ ] **Step 4: Dump autoload**

```bash
composer dump-autoload
```

Expected: `Generated autoload files containing X classes`

- [ ] **Step 5: Commit**

```bash
git add composer.json phpunit.xml tests/
git commit -m "test: set up root tests/ structure with Pest and autoload-dev"
```

---

## Task 3: Refactor `inno_project_idea`

**Files:**
- Create: `public/packages/inno_project_idea/src/Port/In/SubmitProjectIdeaPort.php`
- Create: `public/packages/inno_project_idea/src/Port/Out/ProjectIdeaRepositoryInterface.php`
- Create: `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php`
- Create: `public/packages/inno_project_idea/src/Adapter/Out/Persistence/ConcreteProjectIdeaRepository.php`
- Modify: `public/packages/inno_project_idea/src/Application/UseCase/SubmitProjectIdea/SubmitProjectIdeaHandler.php`
- Modify: `public/packages/inno_project_idea/src/Application/EventHandler/OnModerationDecisionMade.php`
- Modify: `public/packages/inno_project_idea/controller.php`
- Delete: `public/packages/inno_project_idea/src/Domain/Port/` (whole directory)
- Delete: `public/packages/inno_project_idea/src/Domain/Event/` (whole directory)
- Delete: `public/packages/inno_project_idea/src/Infrastructure/` (whole directory)
- Test: `tests/unit/inno_project_idea/Domain/ProjectIdeaTest.php`
- Test: `tests/unit/inno_project_idea/Application/SubmitProjectIdeaHandlerTest.php`

- [ ] **Step 1: Write `tests/unit/inno_project_idea/Domain/ProjectIdeaTest.php`**

```php
<?php
declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;

test('submit() creates idea with Submitted status and correct fields', function () {
    $idea = ProjectIdea::submit('id_1', 'My Idea', 'Great description', 'tech', 'user_1');

    expect($idea->getId())->toBe('id_1')
        ->and($idea->getTitle())->toBe('My Idea')
        ->and($idea->getDescription())->toBe('Great description')
        ->and($idea->getCategory())->toBe('tech')
        ->and($idea->getSubmitterId())->toBe('user_1')
        ->and($idea->getStatus())->toBe(IdeaStatus::Submitted);
});

test('approve() changes status to Approved and updates updatedAt', function () {
    $idea = ProjectIdea::submit('id_1', 'My Idea', 'Desc', 'tech', 'user_1');
    $beforeUpdate = $idea->getUpdatedAt();

    $idea->approve();

    expect($idea->getStatus())->toBe(IdeaStatus::Approved)
        ->and($idea->getUpdatedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('reject() changes status to Rejected', function () {
    $idea = ProjectIdea::submit('id_1', 'My Idea', 'Desc', 'tech', 'user_1');

    $idea->reject();

    expect($idea->getStatus())->toBe(IdeaStatus::Rejected);
});
```

- [ ] **Step 2: Write `tests/unit/inno_project_idea/Application/SubmitProjectIdeaHandlerTest.php`**

```php
<?php
declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Event\ProjectIdeaSubmitted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn () => Mockery::close());

test('handle() saves the idea and dispatches ProjectIdeaSubmitted', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $repo->shouldReceive('save')->once()->with(Mockery::type(ProjectIdea::class));
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(ProjectIdeaSubmitted::class), ProjectIdeaSubmitted::NAME);

    $handler = new SubmitProjectIdeaHandler($repo, $dispatcher);
    $idea = $handler->handle(new SubmitProjectIdeaCommand(
        title: 'My Idea',
        description: 'Great description',
        category: 'tech',
        submitterId: 'user_1',
    ));

    expect($idea->getTitle())->toBe('My Idea')
        ->and($idea->getSubmitterId())->toBe('user_1');
});

test('handle() returns ProjectIdea with a non-empty id', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $repo->shouldReceive('save')->once();
    $dispatcher->shouldReceive('dispatch')->once();

    $handler = new SubmitProjectIdeaHandler($repo, $dispatcher);
    $idea = $handler->handle(new SubmitProjectIdeaCommand('Title', 'Desc', 'cat', 'user_1'));

    expect($idea->getId())->not->toBeEmpty();
});
```

- [ ] **Step 3: Run tests — expect FAIL**

```bash
composer dump-autoload && ./vendor/bin/pest tests/unit/inno_project_idea/ --no-coverage
```

Expected output: FAIL — `Class "Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface" not found`

- [ ] **Step 4: Create new directory structure**

```bash
mkdir -p public/packages/inno_project_idea/src/Port/In
mkdir -p public/packages/inno_project_idea/src/Port/Out
mkdir -p public/packages/inno_project_idea/src/Adapter/In/Http
mkdir -p public/packages/inno_project_idea/src/Adapter/Out/Persistence
```

- [ ] **Step 5: Create `src/Port/In/SubmitProjectIdeaPort.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Port\In;

use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;

interface SubmitProjectIdeaPort
{
    public function handle(SubmitProjectIdeaCommand $command): ProjectIdea;
}
```

- [ ] **Step 6: Create `src/Port/Out/ProjectIdeaRepositoryInterface.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Port\Out;

use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;

interface ProjectIdeaRepositoryInterface
{
    public function findById(string $id): ?ProjectIdea;

    /** @return ProjectIdea[] */
    public function findAll(): array;

    /** @return ProjectIdea[] */
    public function findByStatus(IdeaStatus $status): array;

    public function save(ProjectIdea $idea): void;
}
```

- [ ] **Step 7: Update `src/Application/UseCase/SubmitProjectIdea/SubmitProjectIdeaHandler.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea;

use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\In\SubmitProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Event\ProjectIdeaSubmitted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SubmitProjectIdeaHandler implements SubmitProjectIdeaPort
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handle(SubmitProjectIdeaCommand $command): ProjectIdea
    {
        $idea = ProjectIdea::submit(
            id: uniqid('idea_', true),
            title: $command->title,
            description: $command->description,
            category: $command->category,
            submitterId: $command->submitterId,
        );

        $this->repository->save($idea);

        $this->eventDispatcher->dispatch(
            new ProjectIdeaSubmitted($idea->getId(), $idea->getSubmitterId(), $idea->getTitle()),
            ProjectIdeaSubmitted::NAME,
        );

        return $idea;
    }
}
```

- [ ] **Step 8: Update `src/Application/EventHandler/OnModerationDecisionMade.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Application\EventHandler;

use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Event\ProjectIdeaRejected;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Reacts to a moderation decision: updates the idea's status and re-publishes
 * a domain event so other contexts (project_space, notification) can respond.
 */
class OnModerationDecisionMade
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handle(ModerationDecisionMade $event): void
    {
        $idea = $this->repository->findById($event->getIdeaId());
        if ($idea === null) {
            return;
        }

        if ($event->isApproved()) {
            $idea->approve();
            $this->repository->save($idea);
            $this->eventDispatcher->dispatch(
                new ProjectIdeaApproved($idea->getId(), $idea->getSubmitterId()),
                ProjectIdeaApproved::NAME,
            );
        } else {
            $idea->reject();
            $this->repository->save($idea);
            $this->eventDispatcher->dispatch(
                new ProjectIdeaRejected($idea->getId(), $idea->getSubmitterId(), $event->getReason()),
                ProjectIdeaRejected::NAME,
            );
        }
    }
}
```

- [ ] **Step 9: Create `src/Adapter/In/Http/ProjectIdeaApiController.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Port\In\SubmitProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProjectIdeaApiController extends AbstractController
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly SubmitProjectIdeaPort $submitPort,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ideas = $this->repository->findAll();
        return new JsonResponse(['data' => array_map(
            fn($idea) => [
                'id' => $idea->getId(),
                'title' => $idea->getTitle(),
                'status' => $idea->getStatus()->value,
            ],
            $ideas
        )]);
    }

    public function store(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $idea = $this->submitPort->handle(new SubmitProjectIdeaCommand(
            title: $body['title'] ?? '',
            description: $body['description'] ?? '',
            category: $body['category'] ?? '',
            submitterId: (string) $request->getUser()?->getUserID(),
        ));

        return new JsonResponse(['data' => ['id' => $idea->getId()]], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $idea = $this->repository->findById($id);
        if ($idea === null) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }
        return new JsonResponse(['data' => [
            'id' => $idea->getId(),
            'title' => $idea->getTitle(),
            'description' => $idea->getDescription(),
            'category' => $idea->getCategory(),
            'status' => $idea->getStatus()->value,
            'submitterId' => $idea->getSubmitterId(),
        ]]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return new JsonResponse(['error' => 'Not implemented'], 501);
    }
}
```

- [ ] **Step 10: Create `src/Adapter/Out/Persistence/ConcreteProjectIdeaRepository.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;

class ConcreteProjectIdeaRepository implements ProjectIdeaRepositoryInterface
{
    private const TABLE = 'ib_project_ideas';

    public function __construct(private readonly Connection $db) {}

    public function findById(string $id): ?ProjectIdea
    {
        // TODO: implement via $this->db->fetchAssociative(...)
        return null;
    }

    public function findAll(): array
    {
        // TODO: implement
        return [];
    }

    public function findByStatus(IdeaStatus $status): array
    {
        // TODO: implement
        return [];
    }

    public function save(ProjectIdea $idea): void
    {
        // TODO: implement upsert via $this->db->executeStatement(...)
    }
}
```

- [ ] **Step 11: Update `public/packages/inno_project_idea/controller.php`**

```php
<?php
namespace Concrete\Package\InnoProjectIdea;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Core\Support\Facade\Events;
use Concrete\Package\InnoProjectIdea\Adapter\In\Http\ProjectIdeaApiController;
use Concrete\Package\InnoProjectIdea\Adapter\Out\Persistence\ConcreteProjectIdeaRepository;
use Concrete\Package\InnoProjectIdea\Application\EventHandler\OnModerationDecisionMade;
use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Port\In\SubmitProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;

class Controller extends Package
{
    protected $pkgHandle = 'inno_project_idea';
    protected $appVersionRequired = '9.4.0';
    protected $pkgVersion = '0.1.0';

    public function getPackageDescription(): string
    {
        return t('Manages the submission, storage and lifecycle of project ideas.');
    }

    public function getPackageName(): string
    {
        return t('Innovationsbox – Project Idea');
    }

    public function on_start(): void
    {
        $this->app->bind(ProjectIdeaRepositoryInterface::class, ConcreteProjectIdeaRepository::class);
        $this->app->bind(SubmitProjectIdeaPort::class, SubmitProjectIdeaHandler::class);

        $router = $this->app->make(Router::class);
        $router->register('/api/project-idea', [ProjectIdeaApiController::class, 'index'], 'api.project_idea.index', ['GET']);
        $router->register('/api/project-idea', [ProjectIdeaApiController::class, 'store'], 'api.project_idea.store', ['POST']);
        $router->register('/api/project-idea/{id}', [ProjectIdeaApiController::class, 'show'], 'api.project_idea.show', ['GET']);
        $router->register('/api/project-idea/{id}', [ProjectIdeaApiController::class, 'update'], 'api.project_idea.update', ['PUT']);

        Events::addListener(
            'innovationsbox.moderation.decision_made',
            [$this->app->make(OnModerationDecisionMade::class), 'handle']
        );
    }

    public function install(): void
    {
        parent::install();
    }

    public function upgrade(): void
    {
        parent::upgrade();
    }
}
```

- [ ] **Step 12: Delete old directories**

```bash
rm -rf public/packages/inno_project_idea/src/Domain/Port
rm -rf public/packages/inno_project_idea/src/Domain/Event
rm -rf public/packages/inno_project_idea/src/Infrastructure
```

- [ ] **Step 13: Run tests — expect PASS**

```bash
composer dump-autoload && ./vendor/bin/pest tests/unit/inno_project_idea/ --no-coverage
```

Expected: 5 tests passed (green)

- [ ] **Step 14: Commit**

```bash
git add public/packages/inno_project_idea/ tests/unit/inno_project_idea/
git commit -m "refactor(inno_project_idea): restructure to hexagonal Ports & Adapters"
```

---

## Task 4: Refactor `inno_moderation`

**Files:**
- Create: `public/packages/inno_moderation/src/Port/In/ReviewProjectIdeaPort.php`
- Create: `public/packages/inno_moderation/src/Port/Out/ModerationReviewRepositoryInterface.php`
- Create: `public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php`
- Create: `public/packages/inno_moderation/src/Adapter/Out/Persistence/ConcreteModerationReviewRepository.php`
- Modify: `public/packages/inno_moderation/src/Application/UseCase/ReviewProjectIdea/ReviewProjectIdeaHandler.php`
- Modify: `public/packages/inno_moderation/src/Application/EventHandler/OnProjectIdeaSubmitted.php`
- Modify: `public/packages/inno_moderation/controller.php`
- Delete: `public/packages/inno_moderation/src/Domain/Port/` (whole directory)
- Delete: `public/packages/inno_moderation/src/Domain/Event/` (whole directory)
- Delete: `public/packages/inno_moderation/src/Infrastructure/` (whole directory)
- Test: `tests/unit/inno_moderation/Domain/ModerationReviewTest.php`
- Test: `tests/unit/inno_moderation/Application/ReviewProjectIdeaHandlerTest.php`

- [ ] **Step 1: Write `tests/unit/inno_moderation/Domain/ModerationReviewTest.php`**

```php
<?php
declare(strict_types=1);

use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;

test('createPending() creates a review with Pending decision and no reviewer', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');

    expect($review->getId())->toBe('review_1')
        ->and($review->getIdeaId())->toBe('idea_1')
        ->and($review->getDecision())->toBe(ModerationDecision::Pending)
        ->and($review->getReviewerId())->toBeNull()
        ->and($review->getNotes())->toBe('');
});

test('approve() sets decision to Approved, records reviewerId and notes', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $review->approve('reviewer_1', 'Looks great');

    expect($review->getDecision())->toBe(ModerationDecision::Approved)
        ->and($review->getReviewerId())->toBe('reviewer_1')
        ->and($review->getNotes())->toBe('Looks great')
        ->and($review->getReviewedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('reject() sets decision to Rejected with a reason', function () {
    $review = ModerationReview::createPending('review_1', 'idea_1');
    $review->reject('reviewer_1', 'Not relevant');

    expect($review->getDecision())->toBe(ModerationDecision::Rejected)
        ->and($review->getNotes())->toBe('Not relevant');
});
```

- [ ] **Step 2: Write `tests/unit/inno_moderation/Application/ReviewProjectIdeaHandlerTest.php`**

```php
<?php
declare(strict_types=1);

use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaHandler;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Event\ModerationDecisionMade;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn () => Mockery::close());

test('handle() dispatches ModerationDecisionMade with approved=true when approved', function () {
    $repo = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $review = ModerationReview::createPending('review_1', 'idea_1');

    $repo->shouldReceive('findById')->once()->with('review_1')->andReturn($review);
    $repo->shouldReceive('save')->once()->with($review);
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(
            Mockery::on(fn ($e) => $e instanceof ModerationDecisionMade && $e->isApproved() === true),
            ModerationDecisionMade::NAME
        );

    $handler = new ReviewProjectIdeaHandler($repo, $dispatcher);
    $handler->handle(new ReviewProjectIdeaCommand(
        reviewId: 'review_1',
        reviewerId: 'mod_1',
        approved: true,
        notes: 'Approved',
    ));
});

test('handle() throws RuntimeException when review not found', function () {
    $repo = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $repo->shouldReceive('findById')->once()->with('missing')->andReturn(null);

    $handler = new ReviewProjectIdeaHandler($repo, $dispatcher);

    expect(fn () => $handler->handle(new ReviewProjectIdeaCommand('missing', 'mod_1', true, '')))
        ->toThrow(RuntimeException::class);
});
```

- [ ] **Step 3: Run tests — expect FAIL**

```bash
./vendor/bin/pest tests/unit/inno_moderation/ --no-coverage
```

Expected: FAIL — `Class "Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface" not found`

- [ ] **Step 4: Create new directory structure**

```bash
mkdir -p public/packages/inno_moderation/src/Port/In
mkdir -p public/packages/inno_moderation/src/Port/Out
mkdir -p public/packages/inno_moderation/src/Adapter/In/Http
mkdir -p public/packages/inno_moderation/src/Adapter/Out/Persistence
```

- [ ] **Step 5: Create `src/Port/In/ReviewProjectIdeaPort.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Port\In;

use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaCommand;

interface ReviewProjectIdeaPort
{
    public function handle(ReviewProjectIdeaCommand $command): void;
}
```

- [ ] **Step 6: Create `src/Port/Out/ModerationReviewRepositoryInterface.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Port\Out;

use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;

interface ModerationReviewRepositoryInterface
{
    public function findById(string $id): ?ModerationReview;
    public function findByIdeaId(string $ideaId): ?ModerationReview;

    /** @return ModerationReview[] */
    public function findByDecision(ModerationDecision $decision): array;

    public function save(ModerationReview $review): void;
}
```

- [ ] **Step 7: Update `src/Application/UseCase/ReviewProjectIdea/ReviewProjectIdeaHandler.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea;

use Concrete\Package\InnoModeration\Port\In\ReviewProjectIdeaPort;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Event\ModerationDecisionMade;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ReviewProjectIdeaHandler implements ReviewProjectIdeaPort
{
    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handle(ReviewProjectIdeaCommand $command): void
    {
        $review = $this->repository->findById($command->reviewId);
        if ($review === null) {
            throw new \RuntimeException("ModerationReview {$command->reviewId} not found.");
        }

        if ($command->approved) {
            $review->approve($command->reviewerId, $command->notes);
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
            ),
            ModerationDecisionMade::NAME,
        );
    }
}
```

- [ ] **Step 8: Update `src/Application/EventHandler/OnProjectIdeaSubmitted.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\EventHandler;

use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Event\ProjectIdeaSubmitted;

/**
 * Creates a pending ModerationReview when a new project idea is submitted.
 */
class OnProjectIdeaSubmitted
{
    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
    ) {}

    public function handle(ProjectIdeaSubmitted $event): void
    {
        $review = ModerationReview::createPending(
            id: uniqid('review_', true),
            ideaId: $event->getIdeaId(),
        );
        $this->repository->save($review);
    }
}
```

- [ ] **Step 9: Create `src/Adapter/In/Http/ModerationApiController.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaCommand;
use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Port\In\ReviewProjectIdeaPort;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ModerationApiController extends AbstractController
{
    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
        private readonly ReviewProjectIdeaPort $reviewPort,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $reviews = $this->repository->findByDecision(ModerationDecision::Pending);
        return new JsonResponse(['data' => array_map(
            fn($r) => ['id' => $r->getId(), 'ideaId' => $r->getIdeaId()],
            $reviews,
        )]);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $this->reviewPort->handle(new ReviewProjectIdeaCommand(
            reviewId: $id,
            reviewerId: (string) $request->getUser()?->getUserID(),
            approved: true,
            notes: $body['notes'] ?? '',
        ));
        return new JsonResponse(['status' => 'approved']);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $this->reviewPort->handle(new ReviewProjectIdeaCommand(
            reviewId: $id,
            reviewerId: (string) $request->getUser()?->getUserID(),
            approved: false,
            notes: $body['notes'] ?? '',
        ));
        return new JsonResponse(['status' => 'rejected']);
    }
}
```

- [ ] **Step 10: Create `src/Adapter/Out/Persistence/ConcreteModerationReviewRepository.php`**

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
        // TODO: implement via $this->db->fetchAssociative(...)
        return null;
    }

    public function findByIdeaId(string $ideaId): ?ModerationReview
    {
        // TODO: implement
        return null;
    }

    public function findByDecision(ModerationDecision $decision): array
    {
        // TODO: implement
        return [];
    }

    public function save(ModerationReview $review): void
    {
        // TODO: implement upsert
    }
}
```

- [ ] **Step 11: Update `public/packages/inno_moderation/controller.php`**

```php
<?php
namespace Concrete\Package\InnoModeration;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Core\Support\Facade\Events;
use Concrete\Package\InnoModeration\Adapter\In\Http\ModerationApiController;
use Concrete\Package\InnoModeration\Adapter\Out\Persistence\ConcreteModerationReviewRepository;
use Concrete\Package\InnoModeration\Application\EventHandler\OnProjectIdeaSubmitted;
use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaHandler;
use Concrete\Package\InnoModeration\Port\In\ReviewProjectIdeaPort;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;

class Controller extends Package
{
    protected $pkgHandle = 'inno_moderation';
    protected $appVersionRequired = '9.4.0';
    protected $pkgVersion = '0.1.0';

    public function getPackageDescription(): string
    {
        return t('Review queue for submitted project ideas. Approve or reject before publishing.');
    }

    public function getPackageName(): string
    {
        return t('Innovationsbox – Moderation');
    }

    public function on_start(): void
    {
        $this->app->bind(ModerationReviewRepositoryInterface::class, ConcreteModerationReviewRepository::class);
        $this->app->bind(ReviewProjectIdeaPort::class, ReviewProjectIdeaHandler::class);

        $router = $this->app->make(Router::class);
        $router->register('/api/moderation', [ModerationApiController::class, 'index'], 'api.moderation.index', ['GET']);
        $router->register('/api/moderation/{id}/approve', [ModerationApiController::class, 'approve'], 'api.moderation.approve', ['POST']);
        $router->register('/api/moderation/{id}/reject', [ModerationApiController::class, 'reject'], 'api.moderation.reject', ['POST']);

        Events::addListener(
            'innovationsbox.project_idea.submitted',
            [$this->app->make(OnProjectIdeaSubmitted::class), 'handle']
        );
    }

    public function install(): void
    {
        parent::install();
    }

    public function upgrade(): void
    {
        parent::upgrade();
    }
}
```

- [ ] **Step 12: Delete old directories**

```bash
rm -rf public/packages/inno_moderation/src/Domain/Port
rm -rf public/packages/inno_moderation/src/Domain/Event
rm -rf public/packages/inno_moderation/src/Infrastructure
```

- [ ] **Step 13: Run tests — expect PASS**

```bash
composer dump-autoload && ./vendor/bin/pest tests/unit/inno_moderation/ --no-coverage
```

Expected: 5 tests passed (green)

- [ ] **Step 14: Commit**

```bash
git add public/packages/inno_moderation/ tests/unit/inno_moderation/
git commit -m "refactor(inno_moderation): restructure to hexagonal Ports & Adapters"
```

---

## Task 5: Refactor `inno_notification`

**Files:**
- Create: `public/packages/inno_notification/src/Port/Out/EmailServiceInterface.php`
- Create: `public/packages/inno_notification/src/Adapter/In/Http/NotificationApiController.php`
- Create: `public/packages/inno_notification/src/Adapter/Out/Email/ConcreteEmailService.php`
- Modify: `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaSubmitted.php`
- Modify: `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaApproved.php`
- Modify: `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaRejected.php`
- Modify: `public/packages/inno_notification/controller.php`
- Delete: `public/packages/inno_notification/src/Domain/` (whole directory)
- Delete: `public/packages/inno_notification/src/Infrastructure/` (whole directory)
- Test: `tests/unit/inno_notification/Application/OnProjectIdeaSubmittedTest.php`

- [ ] **Step 1: Write `tests/unit/inno_notification/Application/OnProjectIdeaSubmittedTest.php`**

```php
<?php
declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaSubmitted;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Event\ProjectIdeaSubmitted;

afterEach(fn () => Mockery::close());

test('handle() sends an email with the idea title in the subject', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);

    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::type('string'),
            Mockery::on(fn ($subject) => str_contains($subject, 'My Idea')),
            Mockery::type('string'),
        );

    $handler = new OnProjectIdeaSubmitted($emailService);
    $handler->handle(new ProjectIdeaSubmitted('idea_1', 'user_1', 'My Idea'));
});
```

- [ ] **Step 2: Run tests — expect FAIL**

```bash
./vendor/bin/pest tests/unit/inno_notification/ --no-coverage
```

Expected: FAIL — `Class "Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface" not found`

- [ ] **Step 3: Create new directory structure**

```bash
mkdir -p public/packages/inno_notification/src/Port/Out
mkdir -p public/packages/inno_notification/src/Adapter/In/Http
mkdir -p public/packages/inno_notification/src/Adapter/Out/Email
```

- [ ] **Step 4: Create `src/Port/Out/EmailServiceInterface.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Port\Out;

interface EmailServiceInterface
{
    public function send(string $to, string $subject, string $body): void;
}
```

- [ ] **Step 5: Update `src/Application/EventHandler/OnProjectIdeaSubmitted.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Event\ProjectIdeaSubmitted;

/**
 * Notifies platform admins when a new project idea is submitted and awaits review.
 */
class OnProjectIdeaSubmitted
{
    public function __construct(private readonly EmailServiceInterface $emailService) {}

    public function handle(ProjectIdeaSubmitted $event): void
    {
        // TODO: load admin emails from config or user group
        $adminEmail = 'admin@innovationsbox.ch';

        $this->emailService->send(
            to: $adminEmail,
            subject: 'Neue Projektidee eingereicht: ' . $event->getTitle(),
            body: "Eine neue Projektidee wurde eingereicht und wartet auf Moderation.\n\nID: {$event->getIdeaId()}",
        );
    }
}
```

- [ ] **Step 6: Update `src/Application/EventHandler/OnProjectIdeaApproved.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Event\ProjectIdeaApproved;

/**
 * Notifies the submitter that their idea has been approved and a project space is ready.
 */
class OnProjectIdeaApproved
{
    public function __construct(private readonly EmailServiceInterface $emailService) {}

    public function handle(ProjectIdeaApproved $event): void
    {
        // TODO: resolve submitter email via Concrete CMS UserInfo
        $this->emailService->send(
            to: "user-{$event->getSubmitterId()}@example.com",
            subject: 'Deine Projektidee wurde freigegeben!',
            body: "Gratulation! Deine Projektidee wurde freigegeben. Ein Projektraum wurde für dich erstellt.\n\nIdeenID: {$event->getIdeaId()}",
        );
    }
}
```

- [ ] **Step 7: Update `src/Application/EventHandler/OnProjectIdeaRejected.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Event\ProjectIdeaRejected;

/**
 * Notifies the submitter that their idea has been rejected with a reason.
 */
class OnProjectIdeaRejected
{
    public function __construct(private readonly EmailServiceInterface $emailService) {}

    public function handle(ProjectIdeaRejected $event): void
    {
        // TODO: resolve submitter email via Concrete CMS UserInfo
        $this->emailService->send(
            to: "user-{$event->getSubmitterId()}@example.com",
            subject: 'Projektidee konnte nicht freigegeben werden',
            body: "Leider wurde deine Projektidee nicht freigegeben.\n\nGrund: {$event->getReason()}\n\nIdeenID: {$event->getIdeaId()}",
        );
    }
}
```

- [ ] **Step 8: Create `src/Adapter/In/Http/NotificationApiController.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class NotificationApiController extends AbstractController
{
    public function index(Request $request): JsonResponse
    {
        // TODO: fetch notifications for the current user
        return new JsonResponse(['data' => []]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        // TODO: mark notification as read
        return new JsonResponse(['status' => 'read']);
    }
}
```

- [ ] **Step 9: Create `src/Adapter/Out/Email/ConcreteEmailService.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Adapter\Out\Email;

use Concrete\Core\Mail\Service as MailService;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;

class ConcreteEmailService implements EmailServiceInterface
{
    public function __construct(private readonly MailService $mailer) {}

    public function send(string $to, string $subject, string $body): void
    {
        $this->mailer->reset();
        $this->mailer->to($to);
        $this->mailer->setSubject($subject);
        $this->mailer->setBody($body);
        $this->mailer->sendMail();
    }
}
```

- [ ] **Step 10: Update `public/packages/inno_notification/controller.php`**

```php
<?php
namespace Concrete\Package\InnoNotification;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Core\Support\Facade\Events;
use Concrete\Package\InnoNotification\Adapter\In\Http\NotificationApiController;
use Concrete\Package\InnoNotification\Adapter\Out\Email\ConcreteEmailService;
use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaApproved;
use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaRejected;
use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaSubmitted;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;

class Controller extends Package
{
    protected $pkgHandle = 'inno_notification';
    protected $appVersionRequired = '9.4.0';
    protected $pkgVersion = '0.1.0';

    public function getPackageDescription(): string
    {
        return t('Sends email and system notifications in response to domain events.');
    }

    public function getPackageName(): string
    {
        return t('Innovationsbox – Notification');
    }

    public function on_start(): void
    {
        $this->app->bind(EmailServiceInterface::class, ConcreteEmailService::class);

        $router = $this->app->make(Router::class);
        $router->register('/api/notification', [NotificationApiController::class, 'index'], 'api.notification.index', ['GET']);
        $router->register('/api/notification/{id}/read', [NotificationApiController::class, 'markRead'], 'api.notification.mark_read', ['POST']);

        Events::addListener(
            'innovationsbox.project_idea.submitted',
            [$this->app->make(OnProjectIdeaSubmitted::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.project_idea.approved',
            [$this->app->make(OnProjectIdeaApproved::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.project_idea.rejected',
            [$this->app->make(OnProjectIdeaRejected::class), 'handle']
        );
    }

    public function install(): void
    {
        parent::install();
    }

    public function upgrade(): void
    {
        parent::upgrade();
    }
}
```

- [ ] **Step 11: Delete old directories**

```bash
rm -rf public/packages/inno_notification/src/Domain
rm -rf public/packages/inno_notification/src/Infrastructure
```

- [ ] **Step 12: Run tests — expect PASS**

```bash
composer dump-autoload && ./vendor/bin/pest tests/unit/inno_notification/ --no-coverage
```

Expected: 1 test passed (green)

- [ ] **Step 13: Commit**

```bash
git add public/packages/inno_notification/ tests/unit/inno_notification/
git commit -m "refactor(inno_notification): restructure to hexagonal Ports & Adapters"
```

---

## Task 6: Refactor `inno_project_space`

**Files:**
- Create: `public/packages/inno_project_space/src/Port/Out/ProjectSpaceRepositoryInterface.php`
- Create: `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`
- Create: `public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php`
- Modify: `public/packages/inno_project_space/src/Application/EventHandler/OnProjectIdeaApproved.php`
- Modify: `public/packages/inno_project_space/controller.php`
- Delete: `public/packages/inno_project_space/src/Domain/Port/` (whole directory)
- Delete: `public/packages/inno_project_space/src/Domain/Event/` (whole directory)
- Delete: `public/packages/inno_project_space/src/Infrastructure/` (whole directory)
- Test: `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php`
- Test: `tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php`

- [ ] **Step 1: Write `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php`**

```php
<?php
declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;

test('createFromApprovedIdea() creates space with initiator as first member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Project Alpha', 'user_1');

    expect($space->getId())->toBe('space_1')
        ->and($space->getProjectIdeaId())->toBe('idea_1')
        ->and($space->getTitle())->toBe('Project Alpha')
        ->and($space->getMemberIds())->toContain('user_1');
});

test('addMember() adds a new member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Project Alpha', 'user_1');
    $space->addMember('user_2');

    expect($space->getMemberIds())->toContain('user_2');
});

test('addMember() does not add duplicate members', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Project Alpha', 'user_1');
    $space->addMember('user_1');

    expect(count($space->getMemberIds()))->toBe(1);
});
```

- [ ] **Step 2: Write `tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php`**

```php
<?php
declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Application\EventHandler\OnProjectIdeaApproved;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Event\ProjectSpaceCreated;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn () => Mockery::close());

test('handle() creates a project space and dispatches ProjectSpaceCreated', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $repo->shouldReceive('save')->once()->with(Mockery::type(ProjectSpace::class));
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->with(Mockery::type(ProjectSpaceCreated::class), ProjectSpaceCreated::NAME);

    $handler = new OnProjectIdeaApproved($repo, $dispatcher);
    $handler->handle(new ProjectIdeaApproved('idea_1', 'user_1'));
});
```

- [ ] **Step 3: Run tests — expect FAIL**

```bash
./vendor/bin/pest tests/unit/inno_project_space/ --no-coverage
```

Expected: FAIL — `Class "Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface" not found`

- [ ] **Step 4: Create new directory structure**

```bash
mkdir -p public/packages/inno_project_space/src/Port/Out
mkdir -p public/packages/inno_project_space/src/Adapter/In/Http
mkdir -p public/packages/inno_project_space/src/Adapter/Out/Persistence
```

- [ ] **Step 5: Create `src/Port/Out/ProjectSpaceRepositoryInterface.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\Out;

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;

interface ProjectSpaceRepositoryInterface
{
    public function findById(string $id): ?ProjectSpace;
    public function findByProjectIdeaId(string $ideaId): ?ProjectSpace;

    /** @return ProjectSpace[] */
    public function findByMemberId(string $userId): array;

    public function save(ProjectSpace $space): void;
}
```

- [ ] **Step 6: Update `src/Application/EventHandler/OnProjectIdeaApproved.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\EventHandler;

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Event\ProjectSpaceCreated;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Creates a project space automatically when an idea is approved.
 */
class OnProjectIdeaApproved
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handle(ProjectIdeaApproved $event): void
    {
        $space = ProjectSpace::createFromApprovedIdea(
            id: uniqid('space_', true),
            projectIdeaId: $event->getIdeaId(),
            title: "Project Space – {$event->getIdeaId()}",
            initiatorId: $event->getSubmitterId(),
        );

        $this->repository->save($space);

        $this->eventDispatcher->dispatch(
            new ProjectSpaceCreated($space->getId(), $event->getIdeaId(), $event->getSubmitterId()),
            ProjectSpaceCreated::NAME,
        );
    }
}
```

- [ ] **Step 7: Create `src/Adapter/In/Http/ProjectSpaceApiController.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProjectSpaceApiController extends AbstractController
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        // TODO: restrict to spaces the current user is a member of
        return new JsonResponse(['data' => []]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $space = $this->repository->findById($id);
        if ($space === null) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }
        return new JsonResponse(['data' => [
            'id' => $space->getId(),
            'projectIdeaId' => $space->getProjectIdeaId(),
            'members' => $space->getMemberIds(),
        ]]);
    }

    public function join(Request $request, string $id): JsonResponse
    {
        $space = $this->repository->findById($id);
        if ($space === null) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }
        $space->addMember((string) $request->getUser()?->getUserID());
        $this->repository->save($space);
        return new JsonResponse(['status' => 'joined']);
    }

    public function invite(Request $request, string $id): JsonResponse
    {
        return new JsonResponse(['error' => 'Not implemented'], 501);
    }
}
```

- [ ] **Step 8: Create `src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php`**

```php
<?php
declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;

class ConcreteProjectSpaceRepository implements ProjectSpaceRepositoryInterface
{
    private const TABLE = 'ib_project_spaces';

    public function __construct(private readonly Connection $db) {}

    public function findById(string $id): ?ProjectSpace
    {
        // TODO: implement
        return null;
    }

    public function findByProjectIdeaId(string $ideaId): ?ProjectSpace
    {
        // TODO: implement
        return null;
    }

    public function findByMemberId(string $userId): array
    {
        // TODO: implement
        return [];
    }

    public function save(ProjectSpace $space): void
    {
        // TODO: implement upsert
    }
}
```

- [ ] **Step 9: Update `public/packages/inno_project_space/controller.php`**

```php
<?php
namespace Concrete\Package\InnoProjectSpace;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Core\Support\Facade\Events;
use Concrete\Package\InnoProjectSpace\Adapter\In\Http\ProjectSpaceApiController;
use Concrete\Package\InnoProjectSpace\Adapter\Out\Persistence\ConcreteProjectSpaceRepository;
use Concrete\Package\InnoProjectSpace\Application\EventHandler\OnProjectIdeaApproved;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;

class Controller extends Package
{
    protected $pkgHandle = 'inno_project_space';
    protected $appVersionRequired = '9.4.0';
    protected $pkgVersion = '0.1.0';

    public function getPackageDescription(): string
    {
        return t('Protected collaboration spaces for approved project ideas.');
    }

    public function getPackageName(): string
    {
        return t('Innovationsbox – Project Space');
    }

    public function on_start(): void
    {
        $this->app->bind(ProjectSpaceRepositoryInterface::class, ConcreteProjectSpaceRepository::class);

        $router = $this->app->make(Router::class);
        $router->register('/api/project-space', [ProjectSpaceApiController::class, 'index'], 'api.project_space.index', ['GET']);
        $router->register('/api/project-space/{id}', [ProjectSpaceApiController::class, 'show'], 'api.project_space.show', ['GET']);
        $router->register('/api/project-space/{id}/join', [ProjectSpaceApiController::class, 'join'], 'api.project_space.join', ['POST']);
        $router->register('/api/project-space/{id}/invite', [ProjectSpaceApiController::class, 'invite'], 'api.project_space.invite', ['POST']);

        Events::addListener(
            'innovationsbox.project_idea.approved',
            [$this->app->make(OnProjectIdeaApproved::class), 'handle']
        );
    }

    public function install(): void
    {
        parent::install();
    }

    public function upgrade(): void
    {
        parent::upgrade();
    }
}
```

- [ ] **Step 10: Delete old directories**

```bash
rm -rf public/packages/inno_project_space/src/Domain/Port
rm -rf public/packages/inno_project_space/src/Domain/Event
rm -rf public/packages/inno_project_space/src/Infrastructure
```

- [ ] **Step 11: Run all unit tests — expect all green**

```bash
composer dump-autoload && ./vendor/bin/pest tests/unit/ --no-coverage
```

Expected: 15 tests passed (green)

- [ ] **Step 12: Commit**

```bash
git add public/packages/inno_project_space/ tests/unit/inno_project_space/
git commit -m "refactor(inno_project_space): restructure to hexagonal Ports & Adapters"
```

---

## Task 7: Set up Playwright E2E tests

**Files:**
- Create: `tests/e2e/playwright.config.ts`
- Create: `tests/e2e/fixtures/helpers.ts`
- Create: `tests/e2e/specs/submit-idea.spec.ts`
- Create: `tests/e2e/specs/moderation-flow.spec.ts`
- Modify: `package.json`

- [ ] **Step 1: Install Playwright**

```bash
npm install --save-dev @playwright/test dotenv
npx playwright install chromium
```

Expected: Playwright installed, Chromium browser downloaded

- [ ] **Step 2: Create `tests/e2e/playwright.config.ts`**

```typescript
import { defineConfig } from '@playwright/test';
import * as dotenv from 'dotenv';
import * as path from 'path';

dotenv.config({ path: path.resolve(__dirname, '../../.env') });

const BASE_URL = `http://${process.env.PROJECT_NAME ?? 'innovationsbox'}.local:${process.env.APP_PORT ?? '8080'}`;

export default defineConfig({
  testDir: './specs',
  timeout: 30_000,
  retries: 1,
  use: {
    baseURL: BASE_URL,
    headless: true,
    extraHTTPHeaders: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
  },
  projects: [
    { name: 'chromium', use: { browserName: 'chromium' } },
  ],
});
```

- [ ] **Step 3: Create `tests/e2e/fixtures/helpers.ts`**

```typescript
import { APIRequestContext } from '@playwright/test';

export async function submitIdea(
  request: APIRequestContext,
  data: { title: string; description: string; category: string }
): Promise<string> {
  const response = await request.post('/api/project-idea', { data });
  if (!response.ok()) {
    throw new Error(`submitIdea failed: ${response.status()} ${await response.text()}`);
  }
  const body = await response.json();
  return body.data.id as string;
}

export async function getPendingReviewForIdea(
  request: APIRequestContext,
  ideaId: string
): Promise<{ id: string; ideaId: string } | undefined> {
  const response = await request.get('/api/moderation');
  const body = await response.json();
  return (body.data as Array<{ id: string; ideaId: string }>).find(r => r.ideaId === ideaId);
}

export async function approveReview(
  request: APIRequestContext,
  reviewId: string,
  notes = 'Approved by E2E test'
): Promise<void> {
  const response = await request.post(`/api/moderation/${reviewId}/approve`, {
    data: { notes },
  });
  if (!response.ok()) {
    throw new Error(`approveReview failed: ${response.status()}`);
  }
}
```

- [ ] **Step 4: Create `tests/e2e/specs/submit-idea.spec.ts`**

```typescript
import { test, expect } from '@playwright/test';
import { submitIdea } from '../fixtures/helpers';

test('POST /api/project-idea returns 201 with an idea id', async ({ request }) => {
  const ideaId = await submitIdea(request, {
    title: 'E2E Test Idea',
    description: 'Submitted via Playwright E2E test',
    category: 'tech',
  });

  expect(ideaId).toBeTruthy();
  expect(typeof ideaId).toBe('string');
});

test('GET /api/project-idea returns array of ideas', async ({ request }) => {
  const response = await request.get('/api/project-idea');

  expect(response.status()).toBe(200);
  const body = await response.json();
  expect(Array.isArray(body.data)).toBe(true);
});

test('GET /api/project-idea/{id} returns 404 for unknown id', async ({ request }) => {
  const response = await request.get('/api/project-idea/nonexistent_id_xyz');
  expect(response.status()).toBe(404);
});
```

- [ ] **Step 5: Create `tests/e2e/specs/moderation-flow.spec.ts`**

```typescript
import { test, expect } from '@playwright/test';
import { submitIdea, getPendingReviewForIdea, approveReview } from '../fixtures/helpers';

test('full flow: submit → moderation queue → approve → project space created', async ({ request }) => {
  // 1. Submit a project idea
  const ideaId = await submitIdea(request, {
    title: 'Full Flow E2E Idea',
    description: 'Testing the complete approval flow',
    category: 'environment',
  });
  expect(ideaId).toBeTruthy();

  // 2. Verify moderation review was created
  const review = await getPendingReviewForIdea(request, ideaId);
  expect(review).toBeTruthy();
  expect(review!.ideaId).toBe(ideaId);

  // 3. Approve the review
  await approveReview(request, review!.id, 'Approved in E2E test');

  // 4. Verify project space was created for this idea
  const spacesResponse = await request.get('/api/project-space');
  expect(spacesResponse.status()).toBe(200);
  const spacesBody = await spacesResponse.json();
  const space = (spacesBody.data as Array<{ projectIdeaId: string }>).find(
    s => s.projectIdeaId === ideaId
  );
  expect(space).toBeTruthy();
});

test('reject flow: submit → moderation queue → reject', async ({ request }) => {
  const ideaId = await submitIdea(request, {
    title: 'Idea To Reject',
    description: 'This idea will be rejected',
    category: 'other',
  });

  const review = await getPendingReviewForIdea(request, ideaId);
  expect(review).toBeTruthy();

  const rejectResponse = await request.post(`/api/moderation/${review!.id}/reject`, {
    data: { notes: 'Not relevant enough' },
  });
  expect(rejectResponse.status()).toBe(200);
  const body = await rejectResponse.json();
  expect(body.status).toBe('rejected');
});
```

- [ ] **Step 6: Add `test:e2e` script to `package.json`**

Open `package.json` and add to the `scripts` section:

```json
"test:e2e": "playwright test --config=tests/e2e/playwright.config.ts"
```

- [ ] **Step 7: Verify Playwright config is valid**

```bash
npx playwright test --config=tests/e2e/playwright.config.ts --list
```

Expected: Lists all test cases from `submit-idea.spec.ts` and `moderation-flow.spec.ts` (no errors parsing config)

- [ ] **Step 8: Commit**

```bash
git add tests/e2e/ package.json package-lock.json
git commit -m "test(e2e): add Playwright setup with submit-idea and moderation-flow specs"
```

---

## Task 8: Update CLAUDE.md

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Update the packages table in CLAUDE.md**

Replace the existing packages table and hexagonal architecture section. Find the section starting with `### Packages and communication` and update the package list to include `inno_shared`, and update the REST API column for the existing packages:

```markdown
### Packages and communication

Each package is a self-contained Concrete CMS package with handle = folder name. Packages communicate **event-driven** (Symfony EventDispatcher via `Events` facade) or via **REST API** (`/api/*`). They never import each other's infrastructure — only `inno_shared` event contracts.

| Package (`public/packages/`) | Handle | REST API | Emits events (via `inno_shared`) | Listens to events |
|---|---|---|---|---|
| `inno_frontend` | `inno_frontend` | — | — | — |
| `inno_shared` | `inno_shared` | — | — | — |
| `inno_project_idea` | `inno_project_idea` | `/api/project-idea` | `ProjectIdeaSubmitted`, `ProjectIdeaApproved`, `ProjectIdeaRejected` | `ModerationDecisionMade` |
| `inno_project_space` | `inno_project_space` | `/api/project-space` | `ProjectSpaceCreated` | `ProjectIdeaApproved` |
| `inno_moderation` | `inno_moderation` | `/api/moderation` | `ModerationDecisionMade` | `ProjectIdeaSubmitted` |
| `inno_notification` | `inno_notification` | `/api/notification` | — | `ProjectIdeaSubmitted`, `ProjectIdeaApproved`, `ProjectIdeaRejected` |

All event classes live in `public/packages/inno_shared/src/Event/`. Each class has a `NAME` constant (e.g. `ProjectIdeaSubmitted::NAME = 'innovationsbox.project_idea.submitted'`). Listeners are registered in each package's `on_start()`.
```

- [ ] **Step 2: Update the hexagonal architecture layout section**

Find the section `### Hexagonal architecture per package` and replace it:

```markdown
### Hexagonal architecture per package

Each domain package (`inno_project_idea`, `inno_project_space`, `inno_moderation`, `inno_notification`) follows genuine Hexagonal Architecture (Ports & Adapters). `Application/` is the hexagon centre.

```
src/
  Domain/          ← Pure PHP: entities, enums, value objects (no framework dependencies, no events)
  Application/
    UseCase/       ← Command + Handler pairs; handlers implement Port/In/ interfaces
    EventHandler/  ← Cross-package event listeners (import events from inno_shared only)
  Port/
    In/            ← Primary ports: one interface per use case (e.g. SubmitProjectIdeaPort)
    Out/           ← Secondary ports: repository + service interfaces
  Adapter/
    In/
      Http/        ← REST controllers (extend AbstractController, inject Port/In interfaces)
    Out/
      Persistence/ ← Concrete CMS DB implementations (inject Connection)
      Email/       ← Email adapter (notification package only)
```

Dependency rule: `Adapter/In → Port/In → Application/ → Domain/` and `Adapter/Out ← Port/Out ← Application/`.
No package imports from another domain package. All cross-package events are in `inno_shared`.

DI bindings (interface → implementation) are set up in each package's `on_start()` via `$this->app->bind()` — both `Port/Out/` and `Port/In/` interfaces are bound.
```

- [ ] **Step 3: Update the testing section (add after Commands)**

Add a `### Testing` section to the Commands block:

```markdown
### Testing

```bash
composer test           # Run unit tests (Pest, tests/unit/)
composer test:unit      # Same as above
composer test:integration  # Run integration tests (tests/integration/)
npm run test:e2e        # Run Playwright E2E tests (tests/e2e/) — requires local CMS running
```

All tests live under `tests/` at the repo root:
- `tests/unit/` — pure PHP, no CMS bootstrap; mocks `Port/Out/` interfaces via Mockery
- `tests/integration/` — real DB/email; CMS bootstrapped
- `tests/e2e/` — Playwright targeting `http://$PROJECT_NAME.local:$APP_PORT`
```

- [ ] **Step 4: Run the full unit test suite one final time**

```bash
composer dump-autoload && composer test
```

Expected: 15 tests passed (green)

- [ ] **Step 5: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: update CLAUDE.md to reflect hexagonal architecture and test structure"
```
