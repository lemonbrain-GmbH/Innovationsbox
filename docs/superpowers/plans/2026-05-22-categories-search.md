# Categories & Search Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add dynamic categories (CRUD in moderation, readable by all packages) and text search to project spaces, with filtering on the Projektraeume overview page.

**Architecture:** Categories are owned by `inno_moderation` (DB table, CRUD API). A `CategoryReaderPort` interface in `inno_shared` allows other packages to read categories without importing moderation code. `ProjectIdeaApproved` event carries `categoryId` so spaces inherit their idea's category. Frontend adds search input, category filter pills, and category badges on cards.

**Tech Stack:** PHP 8.2, Pest (testing), Concrete CMS 9.4 (DI, routing, DB), TypeScript, SCSS, Laravel Mix (webpack)

**Spec:** `docs/superpowers/specs/2026-05-22-categories-search-design.md`

---

## File Map

### New Files

| File | Responsibility |
|------|----------------|
| `public/packages/inno_shared/src/Port/CategoryReaderPort.php` | Read-only interface for category list |
| `public/packages/inno_moderation/src/Domain/Model/Category.php` | Category domain entity |
| `public/packages/inno_moderation/src/Port/In/ManageCategoryPort.php` | Primary port: category CRUD |
| `public/packages/inno_moderation/src/Port/Out/CategoryRepositoryPort.php` | Secondary port: persistence |
| `public/packages/inno_moderation/src/Application/UseCase/ManageCategory/CreateCategoryCommand.php` | Command for creating |
| `public/packages/inno_moderation/src/Application/UseCase/ManageCategory/UpdateCategoryCommand.php` | Command for updating |
| `public/packages/inno_moderation/src/Application/UseCase/ManageCategory/DeleteCategoryCommand.php` | Command for deleting |
| `public/packages/inno_moderation/src/Application/UseCase/ManageCategory/ManageCategoryHandler.php` | Handler implementing ManageCategoryPort |
| `public/packages/inno_moderation/src/Adapter/In/Http/CategoryApiController.php` | REST controller |
| `public/packages/inno_moderation/src/Adapter/Out/Persistence/ConcreteCategoryRepository.php` | DB implementation |
| `tests/unit/inno_moderation/Domain/CategoryTest.php` | Entity tests |
| `tests/unit/inno_moderation/Application/ManageCategoryHandlerTest.php` | Handler tests |

### Modified Files

| File | Change |
|------|--------|
| `public/packages/inno_shared/src/Domain/Event/ProjectIdeaApproved.php` | Add `categoryId` parameter |
| `public/packages/inno_moderation/controller.php` | DB table, routes, DI bindings, seed data |
| `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php` | Add `categoryId` field |
| `public/packages/inno_project_space/src/Application/EventHandler/OnProjectIdeaApproved.php` | Pass `categoryId` |
| `public/packages/inno_project_space/src/Port/Out/ProjectSpaceRepositoryInterface.php` | Update `findPublic()` signature |
| `public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php` | Category column, search, category filter |
| `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php` | Category/search params, categories endpoint, category in response |
| `public/packages/inno_project_space/controller.php` | Migration, route, DI |
| `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php` | Update factory calls with categoryId |
| `tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php` | Assert categoryId propagation |
| `public/packages/inno_frontend/single_pages/projektraeume.php` | Search input, category filter container |
| `resources/js/single_pages/projektraeume.ts` | Search, category filter, category badge |
| `resources/css/single_pages/projektraeume.scss` | Search field, category filter styles |
| `public/packages/inno_frontend/components/molecules/project-card/project-card.scss` | Category badge style |
| `resources/js/single_pages/projektraum.ts` | Category badge in detail header |

---

## Task 1: Category Domain Entity (`inno_moderation`)

**Files:**
- Create: `public/packages/inno_moderation/src/Domain/Model/Category.php`
- Create: `tests/unit/inno_moderation/Domain/CategoryTest.php`

- [ ] **Step 1: Write the Category entity test**

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Domain\Model\Category;

test('create() builds a Category with generated slug', function () {
    $cat = Category::create('cat_1', 'Landwirtschaft');

    expect($cat->getId())->toBe('cat_1')
        ->and($cat->getName())->toBe('Landwirtschaft')
        ->and($cat->getSlug())->toBe('landwirtschaft')
        ->and($cat->getSortOrder())->toBe(0);
});

test('create() with explicit slug uses that slug', function () {
    $cat = Category::create('cat_2', 'Bauen', 'bauen-und-renovieren');

    expect($cat->getSlug())->toBe('bauen-und-renovieren');
});

test('create() with sort order', function () {
    $cat = Category::create('cat_3', 'Mobilität', null, 5);

    expect($cat->getSortOrder())->toBe(5);
});

test('rename() updates name and slug', function () {
    $cat = Category::create('cat_1', 'Old Name');
    $cat->rename('New Name');

    expect($cat->getName())->toBe('New Name')
        ->and($cat->getSlug())->toBe('new-name');
});

test('rename() with explicit slug', function () {
    $cat = Category::create('cat_1', 'Old');
    $cat->rename('New Name', 'custom-slug');

    expect($cat->getSlug())->toBe('custom-slug');
});

test('reorder() updates sort order', function () {
    $cat = Category::create('cat_1', 'Test');
    $cat->reorder(3);

    expect($cat->getSortOrder())->toBe(3);
});

test('fromPersistence() reconstructs entity', function () {
    $created = new DateTimeImmutable('2026-01-15 10:00:00');
    $updated = new DateTimeImmutable('2026-02-20 14:30:00');
    $cat = Category::fromPersistence('cat_1', 'Tourismus', 'tourismus', 2, $created, $updated);

    expect($cat->getId())->toBe('cat_1')
        ->and($cat->getName())->toBe('Tourismus')
        ->and($cat->getSlug())->toBe('tourismus')
        ->and($cat->getSortOrder())->toBe(2)
        ->and($cat->getCreatedAt())->toBe($created)
        ->and($cat->getUpdatedAt())->toBe($updated);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/unit/inno_moderation/Domain/CategoryTest.php`
Expected: FAIL — class `Category` not found

- [ ] **Step 3: Implement Category entity**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Domain\Model;

use DateTimeImmutable;

final class Category
{
    private function __construct(
        private readonly string $id,
        private string $name,
        private string $slug,
        private int $sortOrder,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(string $id, string $name, ?string $slug = null, int $sortOrder = 0): self
    {
        $now = new DateTimeImmutable();

        return new self($id, $name, $slug ?? self::generateSlug($name), $sortOrder, $now, $now);
    }

    public static function fromPersistence(
        string $id,
        string $name,
        string $slug,
        int $sortOrder,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $slug, $sortOrder, $createdAt, $updatedAt);
    }

    public function rename(string $name, ?string $slug = null): void
    {
        $this->name = $name;
        $this->slug = $slug ?? self::generateSlug($name);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function reorder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getSlug(): string { return $this->slug; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    private static function generateSlug(string $name): string
    {
        $slug = mb_strtolower($name);
        $slug = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $slug);
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/unit/inno_moderation/Domain/CategoryTest.php`
Expected: All 7 tests PASS

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_moderation/src/Domain/Model/Category.php tests/unit/inno_moderation/Domain/CategoryTest.php
git commit -m "feat(moderation): add Category domain entity with tests"
```

---

## Task 2: Category Ports & Shared Interface

**Files:**
- Create: `public/packages/inno_shared/src/Port/CategoryReaderPort.php`
- Create: `public/packages/inno_moderation/src/Port/In/ManageCategoryPort.php`
- Create: `public/packages/inno_moderation/src/Port/Out/CategoryRepositoryPort.php`

- [ ] **Step 1: Create the shared read-only port**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Port;

interface CategoryReaderPort
{
    /**
     * @return array<array{id: string, name: string, slug: string}>
     */
    public function findAllCategories(): array;
}
```

- [ ] **Step 2: Create the moderation repository port**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Port\Out;

use Concrete\Package\InnoModeration\Domain\Model\Category;

interface CategoryRepositoryPort
{
    public function findById(string $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    /** @return Category[] */
    public function findAll(): array;

    public function save(Category $category): void;

    public function delete(string $id): void;

    public function countSpacesByCategoryId(string $categoryId): int;
}
```

- [ ] **Step 3: Create the primary port (use case interface)**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Port\In;

use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\CreateCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\UpdateCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\DeleteCategoryCommand;
use Concrete\Package\InnoModeration\Domain\Model\Category;

interface ManageCategoryPort
{
    public function create(CreateCategoryCommand $command): Category;

    public function update(UpdateCategoryCommand $command): Category;

    public function delete(DeleteCategoryCommand $command): void;

    /** @return Category[] */
    public function list(): array;
}
```

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_shared/src/Port/CategoryReaderPort.php \
        public/packages/inno_moderation/src/Port/In/ManageCategoryPort.php \
        public/packages/inno_moderation/src/Port/Out/CategoryRepositoryPort.php
git commit -m "feat(shared,moderation): add category port interfaces"
```

---

## Task 3: Category Commands & Handler

**Files:**
- Create: `public/packages/inno_moderation/src/Application/UseCase/ManageCategory/CreateCategoryCommand.php`
- Create: `public/packages/inno_moderation/src/Application/UseCase/ManageCategory/UpdateCategoryCommand.php`
- Create: `public/packages/inno_moderation/src/Application/UseCase/ManageCategory/DeleteCategoryCommand.php`
- Create: `public/packages/inno_moderation/src/Application/UseCase/ManageCategory/ManageCategoryHandler.php`
- Create: `tests/unit/inno_moderation/Application/ManageCategoryHandlerTest.php`

- [ ] **Step 1: Create Command classes**

`CreateCategoryCommand.php`:
```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ManageCategory;

final readonly class CreateCategoryCommand
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public int $sortOrder = 0,
    ) {}
}
```

`UpdateCategoryCommand.php`:
```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ManageCategory;

final readonly class UpdateCategoryCommand
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $slug = null,
        public ?int $sortOrder = null,
    ) {}
}
```

`DeleteCategoryCommand.php`:
```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ManageCategory;

final readonly class DeleteCategoryCommand
{
    public function __construct(
        public string $id,
    ) {}
}
```

- [ ] **Step 2: Write the handler test**

```php
<?php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\CreateCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\UpdateCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\DeleteCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\ManageCategoryHandler;
use Concrete\Package\InnoModeration\Domain\Model\Category;
use Concrete\Package\InnoModeration\Port\Out\CategoryRepositoryPort;
use Psr\Log\LoggerInterface;

afterEach(fn () => Mockery::close());

function createHandler(?CategoryRepositoryPort $repo = null, ?LoggerInterface $logger = null): ManageCategoryHandler
{
    return new ManageCategoryHandler(
        $repo ?? Mockery::mock(CategoryRepositoryPort::class),
        $logger ?? Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing(),
    );
}

test('create() saves a new category and returns it', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('save')->once()->with(Mockery::type(Category::class));
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $result = $handler->create(new CreateCategoryCommand('Mobilität'));

    expect($result->getName())->toBe('Mobilität')
        ->and($result->getSlug())->toBe('mobilitaet');
});

test('create() with explicit slug', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('save')->once();
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $result = $handler->create(new CreateCategoryCommand('Bauen', 'bauen-renovieren', 2));

    expect($result->getSlug())->toBe('bauen-renovieren')
        ->and($result->getSortOrder())->toBe(2);
});

test('update() renames category', function () {
    $cat = Category::create('cat_1', 'Old Name');
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findById')->with('cat_1')->andReturn($cat);
    $repo->shouldReceive('save')->once();
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $result = $handler->update(new UpdateCategoryCommand('cat_1', name: 'New Name'));

    expect($result->getName())->toBe('New Name');
});

test('update() changes sort order only', function () {
    $cat = Category::create('cat_1', 'Bauen');
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findById')->with('cat_1')->andReturn($cat);
    $repo->shouldReceive('save')->once();
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $result = $handler->update(new UpdateCategoryCommand('cat_1', sortOrder: 5));

    expect($result->getName())->toBe('Bauen')
        ->and($result->getSortOrder())->toBe(5);
});

test('update() throws when category not found', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findById')->with('missing')->andReturn(null);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $handler->update(new UpdateCategoryCommand('missing', name: 'X'));
})->throws(RuntimeException::class, 'Category not found');

test('delete() removes category', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findById')->with('cat_1')->andReturn(Category::create('cat_1', 'X'));
    $repo->shouldReceive('countSpacesByCategoryId')->with('cat_1')->andReturn(0);
    $repo->shouldReceive('delete')->once()->with('cat_1');
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $handler->delete(new DeleteCategoryCommand('cat_1'));
});

test('delete() throws when category has assigned spaces', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findById')->with('cat_1')->andReturn(Category::create('cat_1', 'X'));
    $repo->shouldReceive('countSpacesByCategoryId')->with('cat_1')->andReturn(3);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $handler->delete(new DeleteCategoryCommand('cat_1'));
})->throws(RuntimeException::class, 'Cannot delete category with assigned project spaces');

test('list() returns all categories', function () {
    $repo = Mockery::mock(CategoryRepositoryPort::class);
    $repo->shouldReceive('findAll')->andReturn([
        Category::create('cat_1', 'Bauen'),
        Category::create('cat_2', 'Wohnen'),
    ]);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

    $handler = new ManageCategoryHandler($repo, $logger);
    $result = $handler->list();

    expect($result)->toHaveCount(2);
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `./vendor/bin/pest tests/unit/inno_moderation/Application/ManageCategoryHandlerTest.php`
Expected: FAIL — class `ManageCategoryHandler` not found

- [ ] **Step 4: Implement ManageCategoryHandler**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ManageCategory;

use Concrete\Package\InnoModeration\Domain\Model\Category;
use Concrete\Package\InnoModeration\Port\In\ManageCategoryPort;
use Concrete\Package\InnoModeration\Port\Out\CategoryRepositoryPort;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class ManageCategoryHandler implements ManageCategoryPort
{
    public function __construct(
        private readonly CategoryRepositoryPort $repository,
        private readonly LoggerInterface $logger,
    ) {}

    public function create(CreateCategoryCommand $command): Category
    {
        $category = Category::create(
            id: uniqid('cat_', true),
            name: $command->name,
            slug: $command->slug,
            sortOrder: $command->sortOrder,
        );
        $this->repository->save($category);
        $this->logger->info('Category created', ['id' => $category->getId(), 'name' => $category->getName()]);

        return $category;
    }

    public function update(UpdateCategoryCommand $command): Category
    {
        $category = $this->repository->findById($command->id);
        if ($category === null) {
            throw new RuntimeException('Category not found');
        }

        if ($command->name !== null) {
            $category->rename($command->name, $command->slug);
        }
        if ($command->sortOrder !== null) {
            $category->reorder($command->sortOrder);
        }

        $this->repository->save($category);
        $this->logger->info('Category updated', ['id' => $category->getId()]);

        return $category;
    }

    public function delete(DeleteCategoryCommand $command): void
    {
        $category = $this->repository->findById($command->id);
        if ($category === null) {
            throw new RuntimeException('Category not found');
        }

        $count = $this->repository->countSpacesByCategoryId($command->id);
        if ($count > 0) {
            throw new RuntimeException('Cannot delete category with assigned project spaces');
        }

        $this->repository->delete($command->id);
        $this->logger->info('Category deleted', ['id' => $command->id]);
    }

    /** @return Category[] */
    public function list(): array
    {
        return $this->repository->findAll();
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/unit/inno_moderation/Application/ManageCategoryHandlerTest.php`
Expected: All 8 tests PASS

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_moderation/src/Application/UseCase/ManageCategory/ \
        tests/unit/inno_moderation/Application/ManageCategoryHandlerTest.php
git commit -m "feat(moderation): add category CRUD handler with tests"
```

---

## Task 4: Category Persistence & Controller (`inno_moderation`)

**Files:**
- Create: `public/packages/inno_moderation/src/Adapter/Out/Persistence/ConcreteCategoryRepository.php`
- Create: `public/packages/inno_moderation/src/Adapter/In/Http/CategoryApiController.php`
- Modify: `public/packages/inno_moderation/controller.php`

- [ ] **Step 1: Implement ConcreteCategoryRepository**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoModeration\Domain\Model\Category;
use Concrete\Package\InnoModeration\Port\Out\CategoryRepositoryPort;
use Concrete\Package\InnoShared\Port\CategoryReaderPort;
use DateTimeImmutable;

final class ConcreteCategoryRepository implements CategoryRepositoryPort, CategoryReaderPort
{
    public function __construct(private readonly Connection $db) {}

    public function findById(string $id): ?Category
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ib_categories WHERE id = ?', [$id]);

        return $row !== false ? $this->hydrate($row) : null;
    }

    public function findBySlug(string $slug): ?Category
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ib_categories WHERE slug = ?', [$slug]);

        return $row !== false ? $this->hydrate($row) : null;
    }

    /** @return Category[] */
    public function findAll(): array
    {
        $rows = $this->db->fetchAllAssociative('SELECT * FROM ib_categories ORDER BY sort_order ASC, name ASC');

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    /** @return array<array{id: string, name: string, slug: string}> */
    public function findAllCategories(): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT id, name, slug FROM ib_categories ORDER BY sort_order ASC, name ASC',
        );
    }

    public function save(Category $category): void
    {
        $data = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'sort_order' => $category->getSortOrder(),
            'created_at' => $category->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $category->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        $exists = $this->db->fetchOne('SELECT 1 FROM ib_categories WHERE id = ?', [$category->getId()]);
        if ($exists !== false) {
            $this->db->update('ib_categories', $data, ['id' => $category->getId()]);
        } else {
            $this->db->insert('ib_categories', $data);
        }
    }

    public function delete(string $id): void
    {
        $this->db->delete('ib_categories', ['id' => $id]);
    }

    public function countSpacesByCategoryId(string $categoryId): int
    {
        return (int) $this->db->fetchOne(
            'SELECT COUNT(*) FROM ib_project_spaces WHERE category_id = ?',
            [$categoryId],
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Category
    {
        return Category::fromPersistence(
            id: (string) $row['id'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            sortOrder: (int) $row['sort_order'],
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            updatedAt: new DateTimeImmutable((string) $row['updated_at']),
        );
    }
}
```

- [ ] **Step 2: Implement CategoryApiController**

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\In\Http;

use Concrete\Core\Http\Request;
use Concrete\Package\InnoModeration\Port\In\ManageCategoryPort;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\CreateCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\UpdateCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\DeleteCategoryCommand;
use Concrete\Package\InnoShared\Adapter\In\Http\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

final class CategoryApiController extends AbstractController
{
    public function __construct(
        private readonly ManageCategoryPort $manageCategoryPort,
    ) {}

    public function index(): JsonResponse
    {
        $this->requirePermission('moderation.review');
        $categories = $this->manageCategoryPort->list();

        return new JsonResponse([
            'data' => array_map(fn ($c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'sortOrder' => $c->getSortOrder(),
            ], $categories),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requirePermission('moderation.review');
        $name = (string) $request->request->get('name', '');
        $slug = $request->request->get('slug');
        $sortOrder = (int) $request->request->get('sort_order', 0);

        if ($name === '') {
            return new JsonResponse(['error' => 'Name is required'], 400);
        }

        $category = $this->manageCategoryPort->create(
            new CreateCategoryCommand($name, $slug !== null ? (string) $slug : null, $sortOrder),
        );

        return new JsonResponse([
            'data' => [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'sortOrder' => $category->getSortOrder(),
            ],
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->requirePermission('moderation.review');
        $name = $request->request->get('name');
        $slug = $request->request->get('slug');
        $sortOrder = $request->request->get('sort_order');

        try {
            $category = $this->manageCategoryPort->update(new UpdateCategoryCommand(
                $id,
                $name !== null ? (string) $name : null,
                $slug !== null ? (string) $slug : null,
                $sortOrder !== null ? (int) $sortOrder : null,
            ));
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse([
            'data' => [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'sortOrder' => $category->getSortOrder(),
            ],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->requirePermission('moderation.review');

        try {
            $this->manageCategoryPort->delete(new DeleteCategoryCommand($id));
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'assigned') ? 409 : 404;

            return new JsonResponse(['error' => $e->getMessage()], $code);
        }

        return new JsonResponse(null, 204);
    }
}
```

- [ ] **Step 3: Update moderation controller.php — add DB table, routes, bindings, seed**

Add to `on_start()` method, after existing bindings:

```php
// Category bindings
$this->app->bind(CategoryRepositoryPort::class, ConcreteCategoryRepository::class);
$this->app->bind(CategoryReaderPort::class, ConcreteCategoryRepository::class);
$this->app->bind(ManageCategoryPort::class, ManageCategoryHandler::class);

// Category routes
$router->register('/api/moderation/category', [CategoryApiController::class, 'index'], 'api.moderation.category.index', [], [], '', [], ['GET']);
$router->register('/api/moderation/category', [CategoryApiController::class, 'store'], 'api.moderation.category.store', [], [], '', [], ['POST']);
$router->register('/api/moderation/category/{id}', [CategoryApiController::class, 'update'], 'api.moderation.category.update', [], [], '', [], ['PUT']);
$router->register('/api/moderation/category/{id}', [CategoryApiController::class, 'destroy'], 'api.moderation.category.destroy', [], [], '', [], ['DELETE']);
```

Add to `install()` method:

```php
$db->executeStatement("
    CREATE TABLE IF NOT EXISTS ib_categories (
        id VARCHAR(64) NOT NULL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_sort (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Seed initial categories
$now = date('Y-m-d H:i:s');
$seeds = [
    ['cat_bauen', 'Bauen', 'bauen', 0],
    ['cat_wohnen', 'Wohnen', 'wohnen', 1],
    ['cat_landwirtschaft', 'Landwirtschaft', 'landwirtschaft', 2],
    ['cat_tourismus', 'Tourismus', 'tourismus', 3],
    ['cat_mobilitaet', 'Mobilität', 'mobilitaet', 4],
];
foreach ($seeds as [$id, $name, $slug, $order]) {
    $db->executeStatement(
        'INSERT IGNORE INTO ib_categories (id, name, slug, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
        [$id, $name, $slug, $order, $now, $now],
    );
}
```

Add to `upgrade()` method:

```php
$db = $this->app->make('database')->connection();
$sm = $db->getSchemaManager();
if (!$sm->tablesExist(['ib_categories'])) {
    // Same CREATE TABLE + seed as install()
}
```

Add use statements at top of controller.php:

```php
use Concrete\Package\InnoModeration\Adapter\In\Http\CategoryApiController;
use Concrete\Package\InnoModeration\Adapter\Out\Persistence\ConcreteCategoryRepository;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\ManageCategoryHandler;
use Concrete\Package\InnoModeration\Port\In\ManageCategoryPort;
use Concrete\Package\InnoModeration\Port\Out\CategoryRepositoryPort;
use Concrete\Package\InnoShared\Port\CategoryReaderPort;
```

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_moderation/src/Adapter/ \
        public/packages/inno_moderation/controller.php
git commit -m "feat(moderation): add category persistence, API controller, DB migration"
```

---

## Task 5: Extend ProjectIdeaApproved Event & ProjectSpace Domain

**Files:**
- Modify: `public/packages/inno_shared/src/Domain/Event/ProjectIdeaApproved.php`
- Modify: `public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php`
- Modify: `tests/unit/inno_project_space/Domain/ProjectSpaceTest.php`

- [ ] **Step 1: Add categoryId to ProjectIdeaApproved event**

Replace the constructor and add getter in `ProjectIdeaApproved.php`:

```php
public function __construct(
    private readonly string $ideaId,
    private readonly string $submitterId,
    private readonly string $categoryId = '',
) {}

public function getIdeaId(): string { return $this->ideaId; }
public function getSubmitterId(): string { return $this->submitterId; }
public function getCategoryId(): string { return $this->categoryId; }
```

- [ ] **Step 2: Add categoryId to ProjectSpace entity**

In `ProjectSpace.php`, add `private string $categoryId` to the private constructor:

```php
private function __construct(
    private readonly string $id,
    private readonly string $projectIdeaId,
    private string $title,
    private string $description,
    private SpaceStatus $status,
    private readonly string $initiatorId,
    private readonly string $categoryId,
    private readonly DateTimeImmutable $createdAt,
    private DateTimeImmutable $updatedAt,
) {}
```

Update `createFromApprovedIdea()` factory:

```php
public static function createFromApprovedIdea(
    string $id,
    string $projectIdeaId,
    string $title,
    string $description,
    string $initiatorId,
    string $categoryId = '',
): self {
    $now = new DateTimeImmutable();
    $space = new self($id, $projectIdeaId, $title, $description, SpaceStatus::Draft, $initiatorId, $categoryId, $now, $now);
    $space->memberIds[] = $initiatorId;

    return $space;
}
```

Update `fromPersistence()` factory — add `string $categoryId = ''` parameter after `$initiatorId`, pass to constructor.

Add getter:

```php
public function getCategoryId(): string { return $this->categoryId; }
```

- [ ] **Step 3: Update existing ProjectSpace tests**

In `ProjectSpaceTest.php`, update every call to `ProjectSpace::createFromApprovedIdea()` to include `categoryId: 'cat_bauen'` parameter. Update every `fromPersistence()` call similarly.

Add one new test:

```php
test('createFromApprovedIdea() stores categoryId', function () {
    $space = ProjectSpace::createFromApprovedIdea(
        id: 'space_1',
        projectIdeaId: 'idea_1',
        title: 'Test',
        description: 'Desc',
        initiatorId: 'user_1',
        categoryId: 'cat_mobilitaet',
    );

    expect($space->getCategoryId())->toBe('cat_mobilitaet');
});
```

- [ ] **Step 4: Run tests**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Domain/ProjectSpaceTest.php`
Expected: All tests PASS

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_shared/src/Domain/Event/ProjectIdeaApproved.php \
        public/packages/inno_project_space/src/Domain/Model/ProjectSpace.php \
        tests/unit/inno_project_space/Domain/ProjectSpaceTest.php
git commit -m "feat(shared,project-space): add categoryId to event and domain model"
```

---

## Task 6: Event Handler & Repository Updates (`inno_project_space`)

**Files:**
- Modify: `public/packages/inno_project_space/src/Application/EventHandler/OnProjectIdeaApproved.php`
- Modify: `public/packages/inno_project_space/src/Port/Out/ProjectSpaceRepositoryInterface.php`
- Modify: `public/packages/inno_project_space/src/Adapter/Out/Persistence/ConcreteProjectSpaceRepository.php`
- Modify: `public/packages/inno_project_space/controller.php`
- Modify: `tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php`

- [ ] **Step 1: Update OnProjectIdeaApproved to pass categoryId**

In `handle()` method, add `categoryId` parameter to `createFromApprovedIdea()`:

```php
$space = ProjectSpace::createFromApprovedIdea(
    id: uniqid('space_', true),
    projectIdeaId: $event->getIdeaId(),
    title: "Project Space – {$event->getIdeaId()}",
    description: '',
    initiatorId: $event->getSubmitterId(),
    categoryId: $event->getCategoryId(),
);
```

- [ ] **Step 2: Update OnProjectIdeaApprovedTest**

Update the test to assert `categoryId` is propagated:

```php
test('handle() creates a space with categoryId from event', function () {
    $repo = Mockery::mock(ProjectSpaceRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
    $transaction = Mockery::mock(TransactionInterface::class);

    $transaction->shouldReceive('transactional')->andReturnUsing(fn (callable $fn) => $fn());
    $repo->shouldReceive('save')->once()->with(Mockery::on(function (ProjectSpace $space) {
        return $space->getCategoryId() === 'cat_mobilitaet';
    }));
    $dispatcher->shouldReceive('dispatch')->once();

    $handler = new OnProjectIdeaApproved($repo, $dispatcher, $logger, $transaction);
    $handler->handle(new ProjectIdeaApproved('idea_1', 'user_1', 'cat_mobilitaet'));
});
```

- [ ] **Step 3: Run test**

Run: `./vendor/bin/pest tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php`
Expected: PASS

- [ ] **Step 4: Update repository interface — add category and search params**

In `ProjectSpaceRepositoryInterface.php`, update `findPublic()`:

```php
/**
 * @return array{data: ProjectSpace[], total: int}
 */
public function findPublic(?SpaceStatus $status, int $page, int $limit, ?string $categorySlug = null, ?string $search = null): array;
```

- [ ] **Step 5: Update ConcreteProjectSpaceRepository**

In `findPublic()`, add category and search filtering:

```php
public function findPublic(?SpaceStatus $status, int $page, int $limit, ?string $categorySlug = null, ?string $search = null): array
{
    $qb = $this->db->createQueryBuilder();
    $qb->select('s.*')
        ->from('ib_project_spaces', 's');

    if ($status !== null) {
        $qb->andWhere('s.status = :status')->setParameter('status', $status->value);
    } else {
        $qb->andWhere('s.status IN (:statuses)')->setParameter('statuses', ['active', 'completed'], Connection::PARAM_STR_ARRAY);
    }

    if ($categorySlug !== null) {
        $qb->innerJoin('s', 'ib_categories', 'c', 's.category_id = c.id')
            ->andWhere('c.slug = :categorySlug')
            ->setParameter('categorySlug', $categorySlug);
    }

    if ($search !== null && $search !== '') {
        $qb->andWhere('(s.title LIKE :search OR s.description LIKE :search)')
            ->setParameter('search', '%' . $search . '%');
    }

    // Count total
    $countQb = clone $qb;
    $countQb->select('COUNT(*)');
    $total = (int) $countQb->executeQuery()->fetchOne();

    // Paginate
    $offset = ($page - 1) * $limit;
    $qb->orderBy('s.created_at', 'DESC')
        ->setFirstResult($offset)
        ->setMaxResults($limit);

    $rows = $qb->executeQuery()->fetchAllAssociative();
    $spaces = array_map(fn (array $row) => $this->hydrate($row), $rows);

    return ['data' => $spaces, 'total' => $total];
}
```

Add `category_id` to `save()` — add to the `$data` array:

```php
'category_id' => $space->getCategoryId() !== '' ? $space->getCategoryId() : null,
```

Add `categoryId` to `hydrate()`:

```php
categoryId: (string) ($row['category_id'] ?? ''),
```

- [ ] **Step 6: Add category_id column migration in project_space controller.php**

In `upgrade()`:

```php
$db = $this->app->make('database')->connection();
$columns = array_map(fn ($c) => $c->getName(), $db->getSchemaManager()->listTableColumns('ib_project_spaces'));
if (!in_array('category_id', $columns, true)) {
    $db->executeStatement('ALTER TABLE ib_project_spaces ADD COLUMN category_id VARCHAR(64) NULL AFTER initiator_id');
    $db->executeStatement('CREATE INDEX idx_category ON ib_project_spaces (category_id)');
}
```

In `install()`, add `category_id VARCHAR(64) NULL` to the CREATE TABLE statement and add `INDEX idx_category (category_id)`.

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_project_space/src/ \
        public/packages/inno_project_space/controller.php \
        tests/unit/inno_project_space/Application/OnProjectIdeaApprovedTest.php
git commit -m "feat(project-space): add category filtering, search, and DB migration"
```

---

## Task 7: API Endpoints for Categories & Search (`inno_project_space`)

**Files:**
- Modify: `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`
- Modify: `public/packages/inno_project_space/controller.php`

- [ ] **Step 1: Add CategoryReaderPort injection to ProjectSpaceApiController**

Add to constructor:

```php
private readonly CategoryReaderPort $categoryReader,
```

Add use statement:

```php
use Concrete\Package\InnoShared\Port\CategoryReaderPort;
```

- [ ] **Step 2: Add categories() endpoint**

```php
public function categories(): JsonResponse
{
    return new JsonResponse(['data' => $this->categoryReader->findAllCategories()]);
}
```

- [ ] **Step 3: Update index() to accept category and search params**

```php
public function index(Request $request): JsonResponse
{
    $statusParam = $request->query->get('status');
    $status = $statusParam !== null ? SpaceStatus::from((string) $statusParam) : null;
    $page = (int) $request->query->get('page', 1);
    $limit = (int) $request->query->get('limit', 12);
    $categorySlug = $request->query->get('category');
    $search = $request->query->get('search');

    $result = $this->repository->findPublic(
        $status,
        $page,
        $limit,
        $categorySlug !== null ? (string) $categorySlug : null,
        $search !== null ? (string) $search : null,
    );
    // ... rest of existing response building, add category fields to card data ...
}
```

In the card data mapping within `index()`, add:

```php
'categoryId' => $space->getCategoryId(),
```

In the response of `show()`, add:

```php
'categoryId' => $space->getCategoryId(),
```

- [ ] **Step 4: Resolve category name/slug in responses**

Build a category lookup map at the start of `index()`:

```php
$categoryMap = [];
foreach ($this->categoryReader->findAllCategories() as $cat) {
    $categoryMap[$cat['id']] = $cat;
}
```

Then in the card mapping:

```php
$catInfo = $categoryMap[$space->getCategoryId()] ?? null;
// Add to card data:
'categoryId' => $space->getCategoryId(),
'categoryName' => $catInfo['name'] ?? '',
'categorySlug' => $catInfo['slug'] ?? '',
```

Do the same for `show()`.

- [ ] **Step 5: Register route in controller.php**

Add to `on_start()`:

```php
$router->register('/api/project-space/categories', "{$ctrl}::categories", 'api.project_space.categories', [], [], '', [], ['GET']);
```

This route must be registered **before** the `/{id}` route to avoid route conflicts.

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php \
        public/packages/inno_project_space/controller.php
git commit -m "feat(project-space): add categories endpoint, category/search query params"
```

---

## Task 8: Frontend — Search & Category Filter on Projekträume

**Files:**
- Modify: `public/packages/inno_frontend/single_pages/projektraeume.php`
- Modify: `resources/js/single_pages/projektraeume.ts`
- Modify: `resources/css/single_pages/projektraeume.scss`

- [ ] **Step 1: Add search input and category filter container to PHP template**

In `projektraeume.php`, after the subtitle and before the status filters:

```php
<div class="projektraeume-page__search">
    <input type="search" class="projektraeume-page__search-input" id="project-search"
           placeholder="<?= t('Projekte durchsuchen...') ?>" aria-label="<?= t('Suche') ?>">
</div>
```

After the status filter `<nav>`, add:

```php
<nav class="projektraeume-page__filters projektraeume-page__filters--categories" id="category-filters" aria-label="<?= t('Kategorie-Filter') ?>">
    <button class="unstyled-button projektraeume-page__filter projektraeume-page__filter--active" data-category="" type="button"><?= t('Alle') ?></button>
</nav>
```

- [ ] **Step 2: Add styles for search and category filters**

In `projektraeume.scss`, after `&__subtitle`:

```scss
&__search {
    margin-bottom: var(--space-4);
}

&__search-input {
    width: 100%;
    padding: var(--space-3) var(--space-4);
    border: 1px solid var(--color-border-default, #d8d8d8);
    border-radius: var(--radius-full, 9999px);
    font-size: 0.875rem;
    background: var(--color-bg-surface, #fff);
    color: var(--color-text-primary);
    transition: border-color 150ms ease;

    @media (prefers-reduced-motion: reduce) {
        transition: none;
    }

    &::placeholder {
        color: var(--color-text-tertiary);
    }

    &:focus {
        outline: none;
        border-color: var(--color-action-primary, #1a5632);
    }
}

&__filters--categories {
    margin-bottom: var(--space-4);
}
```

- [ ] **Step 3: Update TypeScript — add search, category state, fetch categories**

In `projektraeume.ts`, add state variables after existing ones:

```typescript
let currentCategory = '';
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
let currentSearch = '';
```

Update `fetchSpaces()` to include category and search:

```typescript
async function fetchSpaces(status: string, page: number, category: string, search: string): Promise<ProjectSpacesResponse> {
    const params = new URLSearchParams({ page: String(page), limit: String(LIMIT) });
    if (status) params.set('status', status);
    if (category) params.set('category', category);
    if (search) params.set('search', search);
    const response = await fetch(`${API_BASE}?${params}`);
    return response.json() as Promise<ProjectSpacesResponse>;
}
```

Add `categoryName` and `categorySlug` to the `ProjectSpaceCard` interface:

```typescript
categoryId: string;
categoryName: string;
categorySlug: string;
```

Add category badge to `renderCard()`, in the header div after the status badge:

```typescript
const categoryBadge = space.categoryName
    ? `<span class="project-card__badge project-card__badge--category">${escapeHtml(space.categoryName)}</span>`
    : '';
```

Insert `${categoryBadge}` after the status badge span in the header.

Add functions to load and render category filters:

```typescript
interface CategoryData {
    id: string;
    name: string;
    slug: string;
}

async function fetchCategories(): Promise<CategoryData[]> {
    const response = await fetch(`${API_BASE}/categories`);
    const json = (await response.json()) as { data: CategoryData[] };
    return json.data;
}

async function initCategoryFilters(): Promise<void> {
    const container = document.getElementById('category-filters');
    if (!container) return;

    const categories = await fetchCategories();
    const buttons = categories
        .map((c) => `<button class="unstyled-button projektraeume-page__filter" data-category="${escapeHtml(c.slug)}" type="button">${escapeHtml(c.name)}</button>`)
        .join('');
    container.insertAdjacentHTML('beforeend', buttons);

    container.addEventListener('click', (e) => {
        const btn = (e.target as HTMLElement).closest<HTMLElement>('[data-category]');
        if (!btn) return;

        currentCategory = btn.dataset['category'] ?? '';
        currentPage = 1;
        container.querySelectorAll('[data-category]').forEach((el) => {
            el.classList.remove('projektraeume-page__filter--active');
        });
        btn.classList.add('projektraeume-page__filter--active');
        void loadSpaces();
    });
}
```

Add search initialization:

```typescript
function initSearch(): void {
    const input = document.getElementById('project-search') as HTMLInputElement | null;
    if (!input) return;

    input.addEventListener('input', () => {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = input.value.trim();
            currentPage = 1;
            void loadSpaces();
        }, 300);
    });
}
```

Update `loadSpaces()` to pass all filters:

```typescript
const result = await fetchSpaces(currentStatus, currentPage, currentCategory, currentSearch);
```

Update `DOMContentLoaded` handler:

```typescript
document.addEventListener('DOMContentLoaded', () => {
    initFilters();
    initSearch();
    void initCategoryFilters();
    void loadSpaces();
});
```

- [ ] **Step 4: Add category badge style**

In `project-card.scss`, add after the existing `&__badge--warning` block:

```scss
&--category {
    color: var(--color-text-secondary, #555);
    background: var(--color-bg-muted, #f0f0f0);
    font-size: 0.625rem;
}
```

- [ ] **Step 5: Build and verify**

Run: `npm run development`
Expected: `webpack compiled successfully`

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_frontend/single_pages/projektraeume.php \
        resources/js/single_pages/projektraeume.ts \
        resources/css/single_pages/projektraeume.scss \
        public/packages/inno_frontend/components/molecules/project-card/project-card.scss
git commit -m "feat(frontend): add search field, category filters, and category badges on cards"
```

---

## Task 9: Frontend — Category Badge on Detail Page

**Files:**
- Modify: `resources/js/single_pages/projektraum.ts`

- [ ] **Step 1: Add category to SpaceDetail interface**

```typescript
categoryId: string;
categoryName: string;
categorySlug: string;
```

- [ ] **Step 2: Update renderHeader() to show category badge**

In `renderHeader()`, after setting the status badge, add:

```typescript
if (statusEl && space.categoryName) {
    statusEl.innerHTML += ` <span class="badge badge--neutral badge--sm">${escapeHtml(space.categoryName)}</span>`;
}
```

- [ ] **Step 3: Build and verify**

Run: `npm run development`
Expected: `webpack compiled successfully`

- [ ] **Step 4: Commit**

```bash
git add resources/js/single_pages/projektraum.ts
git commit -m "feat(frontend): show category badge on project detail page"
```

---

## Task 10: Update ProjectIdea Event Dispatch

**Files:**
- Modify: `public/packages/inno_project_idea/src/Application/EventHandler/OnModerationDecisionMade.php`

- [ ] **Step 1: Read the current OnModerationDecisionMade handler**

Read: `public/packages/inno_project_idea/src/Application/EventHandler/OnModerationDecisionMade.php`

This handler dispatches `ProjectIdeaApproved`. It needs to pass the idea's `categoryId` (from `$idea->getCategory()`) to the new `ProjectIdeaApproved` constructor.

- [ ] **Step 2: Update the event dispatch**

Where the handler creates `new ProjectIdeaApproved(...)`, add the category:

```php
new ProjectIdeaApproved(
    $idea->getId(),
    $idea->getSubmitterId(),
    $idea->getCategory(),  // categoryId from the idea
)
```

Note: The existing `ProjectIdea` entity already stores `category` as a string. Since we're migrating to `category_id`, the getter `getCategory()` already returns the value stored in the `category` field. When the migration in Task 11 renames the DB column, this value will be the category ID.

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_project_idea/src/Application/EventHandler/OnModerationDecisionMade.php
git commit -m "feat(project-idea): pass categoryId through ProjectIdeaApproved event"
```

---

## Task 11: Migrate ProjectIdea category field to category_id

**Files:**
- Modify: `public/packages/inno_project_idea/controller.php`
- Modify: `public/packages/inno_project_idea/src/Adapter/Out/Persistence/ConcreteProjectIdeaRepository.php`

- [ ] **Step 1: Add migration in project_idea controller.php upgrade()**

```php
$db = $this->app->make('database')->connection();
$columns = array_map(fn ($c) => $c->getName(), $db->getSchemaManager()->listTableColumns('ib_project_ideas'));
if (in_array('category', $columns, true) && !in_array('category_id', $columns, true)) {
    $db->executeStatement('ALTER TABLE ib_project_ideas CHANGE category category_id VARCHAR(64) NULL');
    $db->executeStatement('CREATE INDEX idx_category ON ib_project_ideas (category_id)');
}
```

- [ ] **Step 2: Update install() CREATE TABLE**

Replace `category VARCHAR(255)` with `category_id VARCHAR(64) NULL` and add `INDEX idx_category (category_id)`.

- [ ] **Step 3: Update repository hydration/save to use category_id column**

In `ConcreteProjectIdeaRepository.php`, update column references from `category` to `category_id` in both `hydrate()` and `save()` methods.

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_project_idea/controller.php \
        public/packages/inno_project_idea/src/Adapter/Out/Persistence/ConcreteProjectIdeaRepository.php
git commit -m "feat(project-idea): migrate category to category_id column"
```

---

## Task 12: Run Full Test Suite & Final Build

- [ ] **Step 1: Run all unit tests**

Run: `composer test`
Expected: All tests pass

- [ ] **Step 2: Run linting**

Run: `npm run lint:all && composer lint:php`
Expected: No errors

- [ ] **Step 3: Run production build**

Run: `npm run production`
Expected: `webpack compiled successfully`

- [ ] **Step 4: Manual verification checklist**

1. Open `http://innovationsbox.internal:8080/projektraeume`
2. Verify search input is visible and filters results on typing
3. Verify category pills are rendered from API
4. Verify clicking a category filters the grid
5. Verify category + status filters work together
6. Verify category badge appears on each project card
7. Open a project detail page — verify category badge in header
8. Test API: `GET /api/project-space/categories` returns 5 categories
9. Test API: `GET /api/project-space?category=bauen` filters correctly
10. Test API: `GET /api/project-space?search=solar` searches title+description

- [ ] **Step 5: Commit any fixes from manual testing**

---

## Note: Idea Submission Form

The spec mentions adding a category `<select>` dropdown to the idea submission form. However, the frontend form for idea submission does not exist yet (only the backend API at `POST /api/project-idea` exists, which already accepts a `category` field). When the idea submission UI is built, it should:

1. Fetch categories via `GET /api/project-space/categories`
2. Render a required `<select>` dropdown with those categories
3. Submit the selected `category_id` as the `category` field in the POST request

This is deferred until the idea submission frontend is implemented.
