# OpenAPI Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add code-first OpenAPI documentation to all API endpoints using `zircote/swagger-php` PHP 8 attributes, with Swagger UI at `/api/docs`, Spectral linting, and contract testing in CI.

**Architecture:** PHP 8 `#[OA\...]` attributes on controllers and schema classes generate `openapi.yaml`/`openapi.json` via `composer openapi:generate`. Swagger UI served at `/api/docs` (config-gated). Spectral lints the spec in CI. Integration tests validate API responses match the spec.

**Tech Stack:** PHP 8.3, zircote/swagger-php ^4, @stoplight/spectral-cli, Concrete CMS 9.4, Pest PHP

**Spec:** `docs/superpowers/specs/2026-04-17-openapi-integration-design.md`

---

## File Map

### New files

```
public/packages/inno_shared/src/Infrastructure/OpenApi/
  OpenApiConfig.php                     — top-level #[OA\Info], #[OA\Server], tags, security scheme
  Schema/
    ErrorResponse.php                   — standard error envelope
    ProjectIdeaSummary.php              — id, title, status
    ProjectIdeaDetail.php               — full idea fields
    SubmitProjectIdeaRequest.php        — title, description, category
    ModerationReviewSummary.php         — id, ideaId
    ModerationNotesRequest.php          — notes
    ProjectSpaceDetail.php              — id, projectIdeaId, members[]
    HealthResponse.php                  — status, services, timestamp
    StatusResponse.php                  — generic status string
  View/
    swagger-ui.php                      — HTML template for /api/docs

docs/api/
  openapi.yaml                          — generated, committed
  openapi.json                          — generated, committed
  README.md                             — developer guide

.spectral.yaml                          — Spectral linting rules
```

### Modified files

```
composer.json                           — add zircote/swagger-php, openapi:generate script
package.json                            — add @stoplight/spectral-cli, lint:api script
public/packages/inno_shared/controller.php — register /api/docs routes
public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php — add OA attributes
public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php    — add OA attributes
public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php — add OA attributes
public/packages/inno_notification/src/Adapter/In/Http/NotificationApiController.php  — add OA attributes
CLAUDE.md                               — add new commands
```

---

## Task 1: Install zircote/swagger-php

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Add swagger-php dependency**

Add `zircote/swagger-php` to `require-dev` and add the `openapi:generate` script in `composer.json`:

In the `"require-dev"` section, add:

```json
"zircote/swagger-php": "^4"
```

In the `"scripts"` section, add:

```json
"openapi:generate": [
    "./vendor/bin/openapi public/packages/inno_*/src/ -o docs/api/openapi.yaml --format yaml",
    "./vendor/bin/openapi public/packages/inno_*/src/ -o docs/api/openapi.json --format json"
]
```

- [ ] **Step 2: Install**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm composer update zircote/swagger-php --no-interaction
```

Expected: package installs successfully.

- [ ] **Step 3: Verify the binary exists**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/openapi --version
```

Expected: prints version info.

- [ ] **Step 4: Create output directory**

```bash
mkdir -p docs/api
```

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock
git commit -m "build: add zircote/swagger-php for OpenAPI spec generation"
```

---

## Task 2: OpenAPI Config & Reusable Schemas

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/OpenApiConfig.php`
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ErrorResponse.php`
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ProjectIdeaSummary.php`
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ProjectIdeaDetail.php`
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/SubmitProjectIdeaRequest.php`
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ModerationReviewSummary.php`
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ModerationNotesRequest.php`
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ProjectSpaceDetail.php`
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/HealthResponse.php`
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/StatusResponse.php`

- [ ] **Step 1: Create OpenApiConfig.php**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/OpenApiConfig.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '0.1.0',
    title: 'Innovationsbox API',
    description: 'REST API for the Innovationsbox platform — project ideas, moderation, project spaces, notifications.',
)]
#[OA\Server(url: '/api', description: 'API base path')]
#[OA\SecurityScheme(
    securitySchemeId: 'cookieAuth',
    type: 'apiKey',
    in: 'cookie',
    name: 'CONCRETE5',
    description: 'Concrete CMS session cookie. Login via the CMS web interface to obtain a session.',
)]
#[OA\Tag(name: 'Project Ideas', description: 'Submit and browse project ideas')]
#[OA\Tag(name: 'Moderation', description: 'Review queue for submitted ideas')]
#[OA\Tag(name: 'Project Spaces', description: 'Collaboration spaces for approved ideas')]
#[OA\Tag(name: 'Notifications', description: 'User notifications')]
#[OA\Tag(name: 'Health', description: 'System health checks')]
class OpenApiConfig
{
}
```

- [ ] **Step 2: Create ErrorResponse.php**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ErrorResponse.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorResponse',
    description: 'Standard error response envelope',
    required: ['error'],
)]
class ErrorResponse
{
    #[OA\Property(
        property: 'error',
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'The requested resource was not found.'),
            new OA\Property(property: 'code', type: 'integer', example: 404),
        ],
        type: 'object',
        required: ['message', 'code'],
    )]
    public object $error;
}
```

- [ ] **Step 3: Create ProjectIdeaSummary.php**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ProjectIdeaSummary.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ProjectIdeaSummary', required: ['id', 'title', 'status'])]
class ProjectIdeaSummary
{
    #[OA\Property(type: 'string', example: 'idea_6627a1b2e3f41')]
    public string $id;

    #[OA\Property(type: 'string', example: 'Solar panels for community building')]
    public string $title;

    #[OA\Property(type: 'string', enum: ['submitted', 'under_review', 'approved', 'rejected'], example: 'submitted')]
    public string $status;
}
```

- [ ] **Step 4: Create ProjectIdeaDetail.php**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ProjectIdeaDetail.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ProjectIdeaDetail', required: ['id', 'title', 'description', 'category', 'status', 'submitterId'])]
class ProjectIdeaDetail
{
    #[OA\Property(type: 'string', example: 'idea_6627a1b2e3f41')]
    public string $id;

    #[OA\Property(type: 'string', example: 'Solar panels for community building')]
    public string $title;

    #[OA\Property(type: 'string', example: 'Install solar panels on the community center roof')]
    public string $description;

    #[OA\Property(type: 'string', example: 'energy')]
    public string $category;

    #[OA\Property(type: 'string', enum: ['submitted', 'under_review', 'approved', 'rejected'])]
    public string $status;

    #[OA\Property(type: 'string', example: 'user_42')]
    public string $submitterId;
}
```

- [ ] **Step 5: Create SubmitProjectIdeaRequest.php**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/SubmitProjectIdeaRequest.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'SubmitProjectIdeaRequest', required: ['title', 'description', 'category'])]
class SubmitProjectIdeaRequest
{
    #[OA\Property(type: 'string', example: 'Solar panels for community building')]
    public string $title;

    #[OA\Property(type: 'string', example: 'Install solar panels on the community center roof')]
    public string $description;

    #[OA\Property(type: 'string', example: 'energy')]
    public string $category;
}
```

- [ ] **Step 6: Create ModerationReviewSummary.php**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ModerationReviewSummary.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ModerationReviewSummary', required: ['id', 'ideaId'])]
class ModerationReviewSummary
{
    #[OA\Property(type: 'string', example: 'review_6627a1c4d5e62')]
    public string $id;

    #[OA\Property(type: 'string', example: 'idea_6627a1b2e3f41')]
    public string $ideaId;
}
```

- [ ] **Step 7: Create ModerationNotesRequest.php**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ModerationNotesRequest.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ModerationNotesRequest')]
class ModerationNotesRequest
{
    #[OA\Property(type: 'string', example: 'Looks good, approved for publication.')]
    public string $notes;
}
```

- [ ] **Step 8: Create ProjectSpaceDetail.php**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/ProjectSpaceDetail.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ProjectSpaceDetail', required: ['id', 'projectIdeaId', 'members'])]
class ProjectSpaceDetail
{
    #[OA\Property(type: 'string', example: 'space_6627a1d5e6f73')]
    public string $id;

    #[OA\Property(type: 'string', example: 'idea_6627a1b2e3f41')]
    public string $projectIdeaId;

    /** @var list<string> */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'), example: ['user_42', 'user_55'])]
    public array $members;
}
```

- [ ] **Step 9: Create HealthResponse.php**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/HealthResponse.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'HealthResponse', required: ['status', 'services', 'timestamp'])]
class HealthResponse
{
    #[OA\Property(type: 'string', enum: ['healthy', 'unhealthy'], example: 'healthy')]
    public string $status;

    #[OA\Property(
        type: 'object',
        additionalProperties: new OA\AdditionalProperties(type: 'string', enum: ['healthy', 'degraded', 'unhealthy']),
        example: ['database' => 'healthy', 'cms' => 'healthy'],
    )]
    public object $services;

    #[OA\Property(type: 'string', format: 'date-time', example: '2026-04-17T10:30:00+00:00')]
    public string $timestamp;
}
```

- [ ] **Step 10: Create StatusResponse.php**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/StatusResponse.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'StatusResponse', required: ['status'])]
class StatusResponse
{
    #[OA\Property(type: 'string', example: 'approved')]
    public string $status;
}
```

- [ ] **Step 11: Generate spec to verify schemas parse correctly**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/openapi public/packages/inno_shared/src/Infrastructure/OpenApi/ -o /dev/stdout --format yaml 2>&1 | head -20
```

Expected: valid YAML output with info, servers, components/schemas.

- [ ] **Step 12: Run PHPStan**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/phpstan analyse
```

Expected: no new errors.

- [ ] **Step 13: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/OpenApi/
git commit -m "feat(openapi): add top-level config and reusable schema classes"
```

---

## Task 3: Annotate ProjectIdeaApiController

**Files:**
- Modify: `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php`

- [ ] **Step 1: Add OpenAPI attributes to all 4 methods**

Replace the full content of `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Port\In\SubmitProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProjectIdeaApiController extends AbstractController
{
    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly SubmitProjectIdeaPort $submitPort,
        private readonly AuthInterface $auth,
    ) {}

    #[OA\Get(
        path: '/project-idea',
        summary: 'List all project ideas',
        description: 'Returns a list of all project ideas with their ID, title, and current status.',
        tags: ['Project Ideas'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of project ideas',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ProjectIdeaSummary'),
                ),
            ],
        ),
    )]
    public function index(Request $request): JsonResponse
    {
        $ideas = $this->repository->findAll();

        return new JsonResponse(['data' => array_map(
            fn($idea) => [
                'id' => $idea->getId(),
                'title' => $idea->getTitle(),
                'status' => $idea->getStatus()->value,
            ],
            $ideas,
        )]);
    }

    #[OA\Post(
        path: '/project-idea',
        summary: 'Submit a new project idea',
        description: 'Creates a new project idea. Requires the idea.submit permission (roles: initiator, administrator).',
        security: [['cookieAuth' => []]],
        tags: ['Project Ideas'],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/SubmitProjectIdeaRequest'),
    )]
    #[OA\Response(
        response: 201,
        description: 'Idea created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: 'idea_6627a1b2e3f41'),
                    ],
                    type: 'object',
                ),
            ],
        ),
    )]
    #[OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden — missing idea.submit permission', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function store(Request $request): JsonResponse
    {
        if (!$this->auth->hasPermission('idea.submit')) {
            throw new AuthException(
                'Permission denied for idea.submit',
                ['permission' => 'idea.submit'],
                $this->auth->isAuthenticated(),
            );
        }

        $body = json_decode($request->getContent(), true) ?? [];

        $idea = $this->submitPort->handle(new SubmitProjectIdeaCommand(
            title: $body['title'] ?? '',
            description: $body['description'] ?? '',
            category: $body['category'] ?? '',
            submitterId: (string) $this->auth->getUserId(),
        ));

        return new JsonResponse(['data' => ['id' => $idea->getId()]], 201);
    }

    #[OA\Get(
        path: '/project-idea/{id}',
        summary: 'Get project idea details',
        description: 'Returns the full details of a single project idea.',
        tags: ['Project Ideas'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Idea details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/ProjectIdeaDetail'),
            ],
        ),
    )]
    #[OA\Response(response: 404, description: 'Idea not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function show(Request $request, string $id): JsonResponse
    {
        $idea = $this->repository->findById($id);
        if ($idea === null) {
            throw ProjectIdeaNotFoundException::withId($id);
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

    #[OA\Put(
        path: '/project-idea/{id}',
        summary: 'Update a project idea',
        description: 'Not yet implemented.',
        tags: ['Project Ideas'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 501, description: 'Not implemented')]
    public function update(Request $request, string $id): JsonResponse
    {
        return new JsonResponse(['error' => 'Not implemented'], 501);
    }
}
```

- [ ] **Step 2: Generate spec and verify**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/openapi public/packages/inno_*/src/ -o /dev/stdout --format yaml 2>&1 | grep "path:" | head -10
```

Expected: `/project-idea`, `/project-idea/{id}` paths appear.

- [ ] **Step 3: Run unit tests**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/pest --testsuite=Unit
```

Expected: 76 tests pass (attributes don't affect runtime).

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php
git commit -m "feat(openapi): annotate ProjectIdeaApiController with OpenAPI attributes"
```

---

## Task 4: Annotate ModerationApiController

**Files:**
- Modify: `public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php`

- [ ] **Step 1: Add OpenAPI attributes to all 3 methods + helper**

Replace the full content of `public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaCommand;
use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Port\In\ReviewProjectIdeaPort;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ModerationApiController extends AbstractController
{
    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
        private readonly ReviewProjectIdeaPort $reviewPort,
        private readonly AuthInterface $auth,
    ) {}

    #[OA\Get(
        path: '/moderation',
        summary: 'List pending moderation reviews',
        description: 'Returns all reviews with status Pending. Requires moderation.review permission (roles: moderator, administrator).',
        security: [['cookieAuth' => []]],
        tags: ['Moderation'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of pending reviews',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ModerationReviewSummary'),
                ),
            ],
        ),
    )]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden — missing moderation.review permission', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission('moderation.review');

        $reviews = $this->repository->findByDecision(ModerationDecision::Pending);

        return new JsonResponse(['data' => array_map(
            fn($r) => ['id' => $r->getId(), 'ideaId' => $r->getIdeaId()],
            $reviews,
        )]);
    }

    #[OA\Post(
        path: '/moderation/{id}/approve',
        summary: 'Approve a project idea',
        description: 'Approves the moderation review, triggering idea approval and project space creation. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['Moderation'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Review ID', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/ModerationNotesRequest'))]
    #[OA\Response(response: 200, description: 'Idea approved', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Review not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function approve(Request $request, string $id): JsonResponse
    {
        $this->requirePermission('moderation.review');

        $body = json_decode($request->getContent(), true) ?? [];
        $this->reviewPort->handle(new ReviewProjectIdeaCommand(
            reviewId: $id,
            reviewerId: (string) $this->auth->getUserId(),
            approved: true,
            notes: $body['notes'] ?? '',
        ));

        return new JsonResponse(['status' => 'approved']);
    }

    #[OA\Post(
        path: '/moderation/{id}/reject',
        summary: 'Reject a project idea',
        description: 'Rejects the moderation review with a reason. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['Moderation'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Review ID', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/ModerationNotesRequest'))]
    #[OA\Response(response: 200, description: 'Idea rejected', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Review not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function reject(Request $request, string $id): JsonResponse
    {
        $this->requirePermission('moderation.review');

        $body = json_decode($request->getContent(), true) ?? [];
        $this->reviewPort->handle(new ReviewProjectIdeaCommand(
            reviewId: $id,
            reviewerId: (string) $this->auth->getUserId(),
            approved: false,
            notes: $body['notes'] ?? '',
        ));

        return new JsonResponse(['status' => 'rejected']);
    }

    private function requirePermission(string $permission): void
    {
        if (!$this->auth->hasPermission($permission)) {
            throw new AuthException(
                "Permission denied for {$permission}",
                ['permission' => $permission],
                $this->auth->isAuthenticated(),
            );
        }
    }
}
```

- [ ] **Step 2: Run unit tests**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/pest --testsuite=Unit
```

Expected: 76 tests pass.

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php
git commit -m "feat(openapi): annotate ModerationApiController with OpenAPI attributes"
```

---

## Task 5: Annotate ProjectSpaceApiController & NotificationApiController

**Files:**
- Modify: `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`
- Modify: `public/packages/inno_notification/src/Adapter/In/Http/NotificationApiController.php`

- [ ] **Step 1: Add OpenAPI attributes to ProjectSpaceApiController**

Replace the full content of `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProjectSpaceApiController extends AbstractController
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
    ) {}

    #[OA\Get(
        path: '/project-space',
        summary: 'List project spaces',
        description: 'Returns a list of project spaces. Currently returns an empty array (not yet implemented).',
        tags: ['Project Spaces'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of project spaces',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProjectSpaceDetail')),
            ],
        ),
    )]
    public function index(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => []]);
    }

    #[OA\Get(
        path: '/project-space/{id}',
        summary: 'Get project space details',
        description: 'Returns the details of a single project space including its members.',
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Space details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/ProjectSpaceDetail'),
            ],
        ),
    )]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function show(Request $request, string $id): JsonResponse
    {
        $space = $this->repository->findById($id);
        if ($space === null) {
            throw ProjectSpaceNotFoundException::withId($id);
        }

        return new JsonResponse(['data' => [
            'id' => $space->getId(),
            'projectIdeaId' => $space->getProjectIdeaId(),
            'members' => $space->getMemberIds(),
        ]]);
    }

    #[OA\Post(
        path: '/project-space/{id}/join',
        summary: 'Join a project space',
        description: 'Adds the current user as a member of the project space. Requires space.join permission (roles: developer, initiator, administrator).',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Joined successfully', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden — missing space.join permission', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function join(Request $request, string $id): JsonResponse
    {
        if (!$this->auth->hasPermission('space.join')) {
            throw new AuthException(
                'Permission denied for space.join',
                ['permission' => 'space.join'],
                $this->auth->isAuthenticated(),
            );
        }

        $space = $this->repository->findById($id);
        if ($space === null) {
            throw ProjectSpaceNotFoundException::withId($id);
        }

        $space->addMember((string) $this->auth->getUserId());
        $this->repository->save($space);

        return new JsonResponse(['status' => 'joined']);
    }

    #[OA\Post(
        path: '/project-space/{id}/invite',
        summary: 'Invite a user to a project space',
        description: 'Not yet implemented.',
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 501, description: 'Not implemented')]
    public function invite(Request $request, string $id): JsonResponse
    {
        return new JsonResponse(['error' => 'Not implemented'], 501);
    }
}
```

- [ ] **Step 2: Add OpenAPI attributes to NotificationApiController**

Replace the full content of `public/packages/inno_notification/src/Adapter/In/Http/NotificationApiController.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class NotificationApiController extends AbstractController
{
    #[OA\Get(
        path: '/notification',
        summary: 'List notifications',
        description: 'Returns notifications for the current user. Currently returns an empty array (not yet implemented).',
        tags: ['Notifications'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of notifications',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ],
        ),
    )]
    public function index(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => []]);
    }

    #[OA\Post(
        path: '/notification/{id}/read',
        summary: 'Mark notification as read',
        description: 'Marks a single notification as read. Currently a stub (not yet implemented).',
        tags: ['Notifications'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Notification ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Notification marked as read', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 404, description: 'Notification not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function markRead(Request $request, string $id): JsonResponse
    {
        return new JsonResponse(['status' => 'read']);
    }
}
```

- [ ] **Step 3: Run unit tests**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/pest --testsuite=Unit
```

Expected: 76 tests pass.

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php \
  public/packages/inno_notification/src/Adapter/In/Http/NotificationApiController.php
git commit -m "feat(openapi): annotate ProjectSpaceApiController and NotificationApiController"
```

---

## Task 6: Annotate Health Endpoint & Generate Full Spec

**Files:**
- Modify: `public/packages/inno_shared/controller.php` (add OA attributes to health route closure — not possible on closures, so extract to a controller class)
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/HealthEndpointDoc.php`

Since the health endpoint is an inline closure in `controller.php`, we can't add attributes to it directly. Instead, create a documentation-only class that describes the endpoint:

- [ ] **Step 1: Create HealthEndpointDoc.php**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/HealthEndpointDoc.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi;

use OpenApi\Attributes as OA;

/**
 * OpenAPI documentation for the /api/health endpoint.
 * The actual implementation is an inline closure in controller.php.
 */
class HealthEndpointDoc
{
    #[OA\Get(
        path: '/health',
        summary: 'System health check',
        description: 'Returns the health status of all system services (database, CMS). Disabled in production via monitoring.health_enabled config.',
        tags: ['Health'],
    )]
    #[OA\Response(
        response: 200,
        description: 'All services healthy',
        content: new OA\JsonContent(ref: '#/components/schemas/HealthResponse'),
    )]
    #[OA\Response(
        response: 503,
        description: 'One or more services unhealthy',
        content: new OA\JsonContent(ref: '#/components/schemas/HealthResponse'),
    )]
    #[OA\Response(response: 404, description: 'Health check disabled')]
    public function health(): void
    {
    }
}
```

- [ ] **Step 2: Generate the full spec**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm composer openapi:generate
```

Expected: `docs/api/openapi.yaml` and `docs/api/openapi.json` created with all 14 endpoints.

- [ ] **Step 3: Verify spec has all endpoints**

```bash
grep "path:" docs/api/openapi.yaml | head -20
```

Expected: all 14 paths listed.

- [ ] **Step 4: Run PHPStan**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/phpstan analyse
```

Expected: no errors.

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/OpenApi/HealthEndpointDoc.php \
  docs/api/openapi.yaml docs/api/openapi.json
git commit -m "feat(openapi): add health endpoint docs and generate full spec"
```

---

## Task 7: Swagger UI at /api/docs

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/OpenApi/View/swagger-ui.php`
- Modify: `public/packages/inno_shared/controller.php`

- [ ] **Step 1: Create swagger-ui.php template**

Create `public/packages/inno_shared/src/Infrastructure/OpenApi/View/swagger-ui.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Innovationsbox API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            url: '/api/docs/openapi.json',
            dom_id: '#swagger-ui',
            presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
            layout: 'BaseLayout',
        });
    </script>
</body>
</html>
```

- [ ] **Step 2: Register /api/docs routes in controller.php**

Add a new private method `registerDocsRoutes()` to `public/packages/inno_shared/controller.php` and call it from `on_start()`.

Add to the `on_start()` method, after the `$this->registerExceptionHandler();` line:

```php
        // --- API documentation ---
        $this->registerDocsRoutes();
```

Add the new private method after `registerHealthRoute()`:

```php
    private function registerDocsRoutes(): void
    {
        $config = $this->app->make(ConfigInterface::class);
        if (!$config->get('api.docs_enabled', true)) {
            return;
        }

        $router = $this->app->make(Router::class);

        $router->register('/api/docs', function () {
            ob_start();
            include __DIR__ . '/src/Infrastructure/OpenApi/View/swagger-ui.php';
            $html = ob_get_clean();

            return new \Symfony\Component\HttpFoundation\Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }, 'api.docs', ['GET']);

        $router->register('/api/docs/openapi.json', function () {
            $specPath = DIR_BASE . '/../docs/api/openapi.json';
            if (!file_exists($specPath)) {
                return new JsonResponse(['error' => 'Spec not generated. Run: composer openapi:generate'], 404);
            }

            return new JsonResponse(
                json_decode(file_get_contents($specPath), true),
                200,
            );
        }, 'api.docs.spec', ['GET']);
    }
```

- [ ] **Step 3: Run unit tests**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/pest --testsuite=Unit
```

Expected: 76 tests pass.

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/OpenApi/View/swagger-ui.php \
  public/packages/inno_shared/controller.php
git commit -m "feat(openapi): serve Swagger UI at /api/docs with config gate"
```

---

## Task 8: Spectral Linting & npm Scripts

**Files:**
- Create: `.spectral.yaml`
- Modify: `package.json`

- [ ] **Step 1: Create .spectral.yaml**

Create `.spectral.yaml` at the repo root:

```yaml
extends: ["spectral:oas"]
rules:
  operation-description: warn
  operation-operationId: off
  oas3-api-servers: error
  info-contact: off
  info-license: off
```

- [ ] **Step 2: Install Spectral**

```bash
npm install --save-dev @stoplight/spectral-cli
```

- [ ] **Step 3: Add npm scripts**

Add to the `"scripts"` section of `package.json`:

```json
"lint:api": "spectral lint docs/api/openapi.yaml"
```

Update the existing `"lint:all"` script to include the API lint:

Change:
```json
"lint:all": "npm run lint && npm run format && npm run lint:scss && npm run typecheck"
```

To:
```json
"lint:all": "npm run lint && npm run format && npm run lint:scss && npm run typecheck && npm run lint:api"
```

- [ ] **Step 4: Run Spectral to verify**

```bash
npm run lint:api
```

Expected: passes or shows only warnings, no errors.

- [ ] **Step 5: Commit**

```bash
git add .spectral.yaml package.json package-lock.json
git commit -m "build: add Spectral for OpenAPI spec linting"
```

---

## Task 9: Documentation Updates

**Files:**
- Create: `docs/api/README.md`
- Modify: `CLAUDE.md`

- [ ] **Step 1: Create docs/api/README.md**

Create `docs/api/README.md`:

```markdown
# API Documentation

The Innovationsbox API is documented using the [OpenAPI 3.0](https://www.openapis.org/) specification, generated from PHP 8 attributes on controllers via [zircote/swagger-php](https://github.com/zircote/swagger-php).

## Swagger UI (Interactive Docs)

Available at `/api/docs` when running locally.

**Controlled by config:** `api.docs_enabled` (default: `true` in dev, set to `false` in production).

The "Authorize" button uses Concrete CMS session cookies — log into the CMS first in the same browser, then "Try it out" sends requests with your session automatically.

## Spec Files

- `docs/api/openapi.yaml` — YAML format (committed)
- `docs/api/openapi.json` — JSON format (committed)

Import either file into Postman, Insomnia, or Bruno.

## Regenerating the Spec

```bash
composer openapi:generate
```

This scans all `#[OA\...]` attributes in `public/packages/inno_*/src/` and generates both YAML and JSON files. Always commit the result — CI checks for staleness.

## Adding a New Endpoint

1. Add `#[OA\Get]` / `#[OA\Post]` / etc. attributes to your controller method
2. Add `#[OA\Response]` for every possible status code
3. Reference reusable schemas from `inno_shared/src/Infrastructure/OpenApi/Schema/`
4. Run `composer openapi:generate` and commit the updated spec

## Adding a New Schema

1. Create a class in `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/`
2. Add `#[OA\Schema]` with properties
3. Reference it from controllers via `ref: '#/components/schemas/YourSchema'`
4. Regenerate the spec

## Authentication

### Current: Session/Cookie

The API uses Concrete CMS session authentication:

1. Log in via the CMS web interface (`/login`)
2. The browser stores a `CONCRETE5` session cookie
3. All API requests include this cookie automatically

```bash
# Example with curl
curl -c cookies.txt -X POST http://localhost:8080/login/authenticate/concrete \
  -d "uName=admin&uPassword=your_password"

curl -b cookies.txt http://localhost:8080/api/project-idea
```

### Future: OAuth 2.0 Bearer Tokens

Concrete CMS 9.4 supports OAuth 2.0 natively at `/oauth/2.0/`. This can be added as a second auth method on the custom API endpoints in the future, enabling external integrators and programmatic access.

## Linting

```bash
npm run lint:api
```

Uses [Spectral](https://stoplight.io/open-source/spectral) with rules defined in `.spectral.yaml`.

## CI Pipeline

1. **Spec linting** — `npm run lint:api` (Stage 1)
2. **Staleness check** — regenerate spec, fail if diff (Stage 2)
```

- [ ] **Step 2: Update CLAUDE.md**

Add the following commands to the appropriate section in `CLAUDE.md` (after the existing "Linting & Formatting" section):

```markdown
### OpenAPI
```bash
composer openapi:generate   # Regenerate OpenAPI spec from code attributes
npm run lint:api            # Lint OpenAPI spec with Spectral
```
```

- [ ] **Step 3: Commit**

```bash
git add docs/api/README.md CLAUDE.md
git commit -m "docs: add API documentation guide and update CLAUDE.md with OpenAPI commands"
```

---

## Task 10: Final Verification

**Files:** None — verification only.

- [ ] **Step 1: Regenerate spec fresh**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm composer openapi:generate
```

- [ ] **Step 2: Run Spectral**

```bash
npm run lint:api
```

Expected: no errors.

- [ ] **Step 3: Run PHPStan**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/phpstan analyse
```

Expected: no errors.

- [ ] **Step 4: Run unit tests**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm ./vendor/bin/pest --testsuite=Unit
```

Expected: 76 tests pass.

- [ ] **Step 5: Verify staleness check would pass**

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm composer openapi:generate
git diff --exit-code docs/api/openapi.yaml docs/api/openapi.json
```

Expected: no diff (spec is current).

- [ ] **Step 6: Commit any lint fixes**

```bash
git add -A
git status
# Only commit if there are changes
git commit -m "style: apply formatting fixes" || echo "Nothing to commit"
```
