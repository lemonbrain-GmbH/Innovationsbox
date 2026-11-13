# OpenAPI Integration Design Spec

**Date:** 2026-04-17
**Status:** Approved
**Scope:** Integrate OpenAPI documentation into the Innovationsbox project — code-first with `zircote/swagger-php`, Swagger UI at `/api/docs`, spec linting and contract testing in CI.

---

## Table of Contents

1. [Decisions](#1-decisions)
2. [Top-Level Configuration & Spec Generation](#2-top-level-configuration--spec-generation)
3. [Controller Attributes](#3-controller-attributes)
4. [Reusable Schemas](#4-reusable-schemas)
5. [Swagger UI](#5-swagger-ui)
6. [Pipeline Integration](#6-pipeline-integration)
7. [Contract Testing](#7-contract-testing)
8. [Documentation Updates](#8-documentation-updates)
9. [File Structure](#9-file-structure)
10. [API Endpoint Inventory](#10-api-endpoint-inventory)

---

## 1. Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Approach | Code-first (`zircote/swagger-php`) | Auto-generated spec, always in sync with code |
| Annotation style | PHP 8 attributes (`#[OA\...]`) | Native language feature, type-safe, IDE support |
| Auth (now) | Session/cookie (`cookieAuth`) | Already implemented via `AuthInterface` |
| Auth (future) | OAuth 2.0 Bearer tokens | Documented in spec for external integrators, not yet implemented |
| Swagger UI | `/api/docs`, config-gated | Available in dev/staging, disabled in production |
| Spec in repo | Generated + committed | Frontend can grab without running backend |
| Staleness check | CI regenerates and diffs | Fails if committed spec doesn't match generated |
| Spec linting | Spectral | Style rules, completeness checks |
| Contract testing | Pest/c5:exec integration tests | Validates real responses against spec schemas |
| CMS integration | Independent | Not using CMS's built-in SpecGenerator |

---

## 2. Top-Level Configuration & Spec Generation

### OpenApiConfig class

`public/packages/inno_shared/src/Infrastructure/OpenApi/OpenApiConfig.php`:

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

### Spec generation

Composer script in `composer.json`:

```json
"openapi:generate": [
    "./vendor/bin/openapi public/packages/inno_*/src/ -o docs/api/openapi.yaml --format yaml",
    "./vendor/bin/openapi public/packages/inno_*/src/ -o docs/api/openapi.json --format json"
]
```

Output committed to `docs/api/openapi.yaml` and `docs/api/openapi.json`.

---

## 3. Controller Attributes

Every controller method gets `#[OA\...]` attributes. Pattern:

- `#[OA\Get]` / `#[OA\Post]` / `#[OA\Put]` with `path`, `summary`, `tags`
- `security: [['cookieAuth' => []]]` on endpoints requiring auth
- `#[OA\Parameter]` for path params
- `#[OA\RequestBody]` with `content: new OA\JsonContent(ref: '...')`
- `#[OA\Response]` for every possible status code, referencing reusable schemas

Every endpoint documents all its response codes:
- 200/201 for success
- 400 for validation errors (`ValidationException`)
- 401 for unauthenticated (`AuthException` with `authenticated: false`)
- 403 for unauthorized (`AuthException` with `authenticated: true`)
- 404 for not found (`NotFoundException`)
- 500 for server errors (unexpected exceptions)
- 501 for not-implemented stubs

These map directly to the exception hierarchy from the cross-cutting concerns.

### Example: ProjectIdeaApiController::store()

```php
#[OA\Post(
    path: '/project-idea',
    summary: 'Submit a new project idea',
    security: [['cookieAuth' => []]],
    tags: ['Project Ideas'],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: '#/components/schemas/SubmitProjectIdeaRequest'),
)]
#[OA\Response(
    response: 201,
    description: 'Idea created',
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
#[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
public function store(Request $request): JsonResponse
```

### Endpoints with no auth

Read-only public endpoints (like `GET /project-idea`) omit the `security` attribute entirely.

---

## 4. Reusable Schemas

All schemas live in `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/`. Each is a standalone PHP class with `#[OA\Schema]`.

### ErrorResponse

```php
#[OA\Schema(
    schema: 'ErrorResponse',
    description: 'Standard error response envelope',
    required: ['error'],
)]
class ErrorResponse
{
    #[OA\Property(
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'The requested resource was not found.'),
            new OA\Property(property: 'code', type: 'integer', example: 404),
        ],
        type: 'object',
    )]
    public object $error;
}
```

### ProjectIdeaSummary

```php
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

### ProjectIdeaDetail

```php
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

### SubmitProjectIdeaRequest

```php
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

### ModerationReviewSummary

```php
#[OA\Schema(schema: 'ModerationReviewSummary', required: ['id', 'ideaId'])]
class ModerationReviewSummary
{
    #[OA\Property(type: 'string', example: 'review_6627a1c4d5e62')]
    public string $id;

    #[OA\Property(type: 'string', example: 'idea_6627a1b2e3f41')]
    public string $ideaId;
}
```

### ModerationNotesRequest

```php
#[OA\Schema(schema: 'ModerationNotesRequest')]
class ModerationNotesRequest
{
    #[OA\Property(type: 'string', example: 'Looks good, approved for publication.')]
    public string $notes;
}
```

### ProjectSpaceDetail

```php
#[OA\Schema(schema: 'ProjectSpaceDetail', required: ['id', 'projectIdeaId', 'members'])]
class ProjectSpaceDetail
{
    #[OA\Property(type: 'string', example: 'space_6627a1d5e6f73')]
    public string $id;

    #[OA\Property(type: 'string', example: 'idea_6627a1b2e3f41')]
    public string $projectIdeaId;

    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'), example: ['user_42', 'user_55'])]
    public array $members;
}
```

### HealthResponse

```php
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

### StatusResponse

```php
#[OA\Schema(schema: 'StatusResponse', required: ['status'])]
class StatusResponse
{
    #[OA\Property(type: 'string', example: 'approved')]
    public string $status;
}
```

---

## 5. Swagger UI

### Routes

Registered in `inno_shared/controller.php`:

- `GET /api/docs` — serves the Swagger UI HTML page
- `GET /api/docs/openapi.json` — serves the generated spec file

### Config gate

`api.docs_enabled` config key:
- Development: `true` (default)
- Production: `false`

When disabled, both routes return 404.

### HTML template

`public/packages/inno_shared/src/Infrastructure/OpenApi/View/swagger-ui.php`:

A self-contained HTML page (~30 lines) that loads Swagger UI from CDN (`unpkg.com/swagger-ui-dist@5`) and points it at `/api/docs/openapi.json`.

The "Authorize" button works with `cookieAuth` — the developer logs into the CMS in the same browser, and the session cookie is sent with "Try it out" requests automatically.

### No extra dependencies

Swagger UI loaded from CDN. No npm package, no local assets to maintain.

---

## 6. Pipeline Integration

### Stage 1 (lint & format): Spectral linting

`.spectral.yaml` at repo root:

```yaml
extends: ["spectral:oas"]
rules:
  operation-description: warn
  operation-operationId: off
  oas3-api-servers: error
  info-contact: off
```

npm script:

```json
"lint:api": "spectral lint docs/api/openapi.yaml"
```

Added to `lint:all` chain. Installed as dev dependency: `@stoplight/spectral-cli`.

### Stage 2 (type check): Staleness check

```bash
composer openapi:generate
git diff --exit-code docs/api/openapi.yaml docs/api/openapi.json
```

Fails if committed spec doesn't match freshly generated spec.

### Stage 3 (test): Contract tests

Contract tests run alongside existing integration tests via `c5:exec`. See Section 7.

---

## 7. Contract Testing

Extend `tests/integration/run_integration_tests.php` with a new section:

1. Read `docs/api/openapi.json` into memory
2. For each implemented endpoint, make a real HTTP request via PHP's `file_get_contents()` or `curl_exec()` to `http://localhost:80` (the CMS running in the same Docker network)
3. Validate:
   - Response status code is one of the documented codes for that endpoint
   - Response body structure matches the schema defined in the spec (check required properties exist, types match)

The validation is lightweight JSON schema checking — parse the OpenAPI spec, extract the response schema for the endpoint + status code, verify the actual response body conforms.

This does not cover edge cases exhaustively — it verifies the happy path and error responses match the documented contract. If a response has a field not in the schema, or is missing a required field, the test fails.

Run command (same as existing integration tests):

```bash
docker exec -w /usr/local/htdocs/project innovationsbox_php-fpm \
  php public/concrete/bin/concrete5 c5:exec tests/integration/run_integration_tests.php
```

---

## 8. Documentation Updates

### New: `docs/api/README.md`

Developer guide covering:
- How to access Swagger UI locally (`/api/docs` with `api.docs_enabled: true`)
- How to add a new endpoint (add attributes → regenerate → commit)
- How to add a new schema (create class in `OpenApi/Schema/`)
- Auth flow: login via CMS → session cookie → API requests
- OAuth 2.0 Bearer token flow (documented for future use)
- How to import `openapi.yaml` into Postman/Insomnia/Bruno
- How contract tests work and how to fix failures

### Update: `CLAUDE.md`

Add to commands section:

```bash
composer openapi:generate   # Regenerate OpenAPI spec from code attributes
npm run lint:api            # Lint OpenAPI spec with Spectral
```

### Update: `docs/cross-cutting-concerns/03-error-handling.md`

Add note that the `ErrorResponse` schema is defined as an OpenAPI component and used consistently across all endpoints.

### Update: `docs/cross-cutting-concerns/04-security-auth.md`

Add note about the `cookieAuth` OpenAPI security scheme and the planned OAuth 2.0 Bearer token support.

### Update: `docs/cross-cutting-concerns/technical-spec.typ`

Add "API Contract" section:
- Versioning strategy: single version, no `/v1/` prefix. Breaking changes bump `info.version` in `OpenApiConfig.php`.
- Auth model: session/cookie now, OAuth 2.0 documented for future
- Error response format: `ErrorResponse` schema with `error.message` and `error.code`

---

## 9. File Structure

### New files

```
public/packages/inno_shared/src/Infrastructure/OpenApi/
  OpenApiConfig.php
  Schema/
    ErrorResponse.php
    ProjectIdeaSummary.php
    ProjectIdeaDetail.php
    SubmitProjectIdeaRequest.php
    ModerationReviewSummary.php
    ModerationNotesRequest.php
    ProjectSpaceDetail.php
    HealthResponse.php
    StatusResponse.php
  View/
    swagger-ui.php

docs/api/
  openapi.yaml                  (generated, committed)
  openapi.json                  (generated, committed)
  README.md

.spectral.yaml
```

### Modified files

```
composer.json                   — add zircote/swagger-php, openapi:generate script
package.json                    — add @stoplight/spectral-cli, lint:api script, update lint:all
public/packages/inno_shared/controller.php  — register /api/docs routes
public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php  — add OA attributes
public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php     — add OA attributes
public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php — add OA attributes
public/packages/inno_notification/src/Adapter/In/Http/NotificationApiController.php  — add OA attributes
tests/integration/run_integration_tests.php — add contract test section
CLAUDE.md                       — add new commands
docs/cross-cutting-concerns/03-error-handling.md    — add OpenAPI reference
docs/cross-cutting-concerns/04-security-auth.md     — add OpenAPI reference
docs/cross-cutting-concerns/technical-spec.typ      — add API contract section
```

---

## 10. API Endpoint Inventory

Complete list of endpoints to document:

| Path | Method | Tag | Auth | Status | Summary |
|---|---|---|---|---|---|
| `/health` | GET | Health | No | Implemented | System health check |
| `/project-idea` | GET | Project Ideas | No | Implemented | List all ideas |
| `/project-idea` | POST | Project Ideas | `idea.submit` | Implemented | Submit a new idea |
| `/project-idea/{id}` | GET | Project Ideas | No | Implemented | Get idea details |
| `/project-idea/{id}` | PUT | Project Ideas | - | Not implemented (501) | Update an idea |
| `/moderation` | GET | Moderation | `moderation.review` | Implemented | List pending reviews |
| `/moderation/{id}/approve` | POST | Moderation | `moderation.review` | Implemented | Approve an idea |
| `/moderation/{id}/reject` | POST | Moderation | `moderation.review` | Implemented | Reject an idea |
| `/notification` | GET | Notifications | - | TODO | List notifications |
| `/notification/{id}/read` | POST | Notifications | - | TODO | Mark notification as read |
| `/project-space` | GET | Project Spaces | - | TODO | List project spaces |
| `/project-space/{id}` | GET | Project Spaces | No | Implemented | Get space details |
| `/project-space/{id}/join` | POST | Project Spaces | `space.join` | Implemented | Join a space |
| `/project-space/{id}/invite` | POST | Project Spaces | - | Not implemented (501) | Invite to a space |

Paths in the spec are relative to the `/api` server base path.

All TODO/not-implemented endpoints are documented in the spec with their current response (empty array or 501) so the spec reflects reality.
