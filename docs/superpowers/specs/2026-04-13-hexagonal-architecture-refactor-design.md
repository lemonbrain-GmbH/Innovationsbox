# Hexagonal Architecture Refactor Design

**Date:** 2026-04-13
**Branch:** feature/ddd-package-struktur
**Status:** Completed (2026-04-13)

## Context

The codebase was documented as "hexagonal architecture" but the actual structure (`Domain → Application → Infrastructure` layers) is Clean Architecture (Uncle Bob). This spec defines a full refactor to genuine Hexagonal Architecture (Ports & Adapters, Cockburn/Graca style) across all five domain packages, plus the introduction of a shared kernel package.

---

## 1. Package Inventory

Six packages total (one new):

| Package handle | Role |
|---|---|
| `inno_shared` | Shared kernel: cross-package event contracts only |
| `inno_project_idea` | Project idea lifecycle |
| `inno_project_space` | Collaboration spaces |
| `inno_moderation` | Review queue |
| `inno_notification` | Email/system notifications |
| `inno_frontend` | Theme + UI (unchanged structurally) |

`inno_shared` contains **only event contracts** — the PHP classes for events that cross package boundaries. No domain logic, no use cases, no infrastructure. Each domain package may depend on `inno_shared`; no domain package may depend on another domain package.

---

## 2. Folder Structure Per Package

Every domain package (`inno_project_idea`, `inno_moderation`, `inno_project_space`, `inno_notification`) follows this layout:

```
src/
  Domain/               # Pure PHP: entities, enums, value objects, domain services
                        # No framework dependencies. No ports here.

  Application/          # The hexagon center
    UseCase/            # Command + Handler pairs (use case implementations)
    EventHandler/       # Inbound cross-package event handlers

  Port/
    In/                 # Primary ports: one interface per use case
                        # e.g. SubmitProjectIdeaPort { handle(Command): Result }
    Out/                # Secondary ports: repository + service interfaces
                        # e.g. ProjectIdeaRepositoryInterface, EmailServiceInterface

  Adapter/
    In/                 # Primary/driving adapters
      Http/             # REST controllers (extend AbstractController)
    Out/                # Secondary/driven adapters
      Persistence/      # Concrete CMS DB implementations
      Email/            # Email adapter (notification package only)
```

`inno_shared` has a flat structure:

```
src/
  Event/
    ProjectIdeaSubmitted.php
    ProjectIdeaApproved.php
    ProjectIdeaRejected.php
    ModerationDecisionMade.php
    ProjectSpaceCreated.php
```

---

## 3. Dependency Rules & Cross-Package Communication

The dependency arrow always points inward:

```
Adapter/In  →  Port/In  →  Application/  →  Domain/
Adapter/Out ←  Port/Out ←  Application/
                              ↑
                         inno_shared (events only)
```

Concrete rules:

1. `Domain/` has zero imports from any other package or framework (pure PHP only — no event classes live here; all events in this system cross package boundaries and belong in `inno_shared`)
2. `Port/In/` and `Port/Out/` import only from `Domain/` within the same package
3. `Application/UseCase/` implements `Port/In/` interfaces, uses `Port/Out/` interfaces — no direct adapter imports
4. `Application/EventHandler/` imports event classes from `inno_shared` only, uses `Port/Out/` interfaces to act
5. `Adapter/In/Http/` injects `Port/In/` interfaces (not `Application/` handler classes directly)
6. `Adapter/Out/` implements `Port/Out/` interfaces — may import framework (Concrete CMS `Connection`, etc.) freely
7. No package imports from another domain package's `Domain/`, `Application/`, `Adapter/`, or `Port/`

Event flow:

```
Adapter/In/Http → Port/In → Application/UseCase → dispatch(inno_shared event)
                                                          ↓
                               other package's Application/EventHandler reacts
                                                          ↓
                                              uses own Port/Out → Adapter/Out
```

---

## 4. Namespace Mapping

Namespace root per package stays the same (`Concrete\Package\InnoProjectIdea\...` etc.). Only path segments inside `src/` change:

| Current | New |
|---|---|
| `...\Domain\Port\Repository\ProjectIdeaRepositoryInterface` | `...\Port\Out\ProjectIdeaRepositoryInterface` |
| `...\Domain\Port\Service\EmailServiceInterface` | `...\Port\Out\EmailServiceInterface` |
| `...\Domain\Event\ProjectIdeaSubmitted` | `Concrete\Package\InnoShared\Event\ProjectIdeaSubmitted` |
| `...\Infrastructure\Persistence\ConcreteProjectIdeaRepository` | `...\Adapter\Out\Persistence\ConcreteProjectIdeaRepository` |
| `...\Infrastructure\Http\Api\ProjectIdeaApiController` | `...\Adapter\In\Http\ProjectIdeaApiController` |
| `...\Infrastructure\Email\ConcreteEmailService` | `...\Adapter\Out\Email\ConcreteEmailService` |
| *(missing)* | `...\Port\In\SubmitProjectIdeaPort` (new) |
| *(missing)* | `...\Port\In\ReviewProjectIdeaPort` (new) |

Primary port example:

```php
// Port/In/SubmitProjectIdeaPort.php
interface SubmitProjectIdeaPort {
    public function handle(SubmitProjectIdeaCommand $command): ProjectIdea;
}

// Application/UseCase/SubmitProjectIdea/SubmitProjectIdeaHandler.php
class SubmitProjectIdeaHandler implements SubmitProjectIdeaPort { ... }
```

Controllers inject `SubmitProjectIdeaPort`, not the handler class directly.

Each package's `composer.json` `autoload.psr-4` gets the updated namespace-to-folder mapping. The `on_start()` DI bindings in `controller.php` are updated to bind `Port/In/` interfaces to `Application/UseCase/` handlers, in addition to existing `Port/Out/` bindings.

---

## 5. Testing Structure

All tests live at the repo root `tests/` folder — nothing inside package `src/` siblings.

```
tests/
  unit/
    inno_project_idea/
      Domain/
      Application/
    inno_moderation/
      Domain/
      Application/
    inno_notification/
      Domain/
      Application/
    inno_project_space/
      Domain/
      Application/
  integration/
    inno_project_idea/
      Adapter/Out/Persistence/
    inno_moderation/
      Adapter/Out/Persistence/
    inno_notification/
      Adapter/Out/Email/
  e2e/
    playwright.config.ts
    fixtures/           # shared setup: DB seed, login helpers
    specs/
      submit-idea.spec.ts
      moderation-flow.spec.ts
      project-space-created.spec.ts
      notification-sent.spec.ts
```

Test commands:

- `composer test` (Pest) — runs `tests/unit/` and `tests/integration/`
- `npm run test:e2e` — runs `tests/e2e/` via Playwright against `http://$PROJECT_NAME.local:$APP_PORT`

Testing boundaries:

- **Unit (`Domain/`, `Application/`):** pure PHP, mock `Port/Out/` interfaces, no framework bootstrap needed
- **Integration (`Adapter/Out/`):** real DB connection or mail trap, Concrete CMS bootstrapped
- **HTTP adapter (`Adapter/In/Http/`):** mock `Port/In/` interface, assert JSON shape and status codes only
- **E2E (Playwright):** golden paths across all packages — submit → moderation → approve → project space created → notification sent

---

## 6. What Changes, What Stays

**Changes:**
- `Infrastructure/` removed from all packages — replaced by `Adapter/In/` and `Adapter/Out/`
- `Domain/Port/` removed — replaced by top-level `Port/In/` and `Port/Out/`
- Domain event classes that cross package boundaries moved to new `inno_shared` package
- Controllers updated to inject `Port/In/` interfaces
- Event handlers updated to import from `inno_shared`
- All namespaces updated accordingly
- `inno_shared` package scaffold created with `controller.php` and `composer.json`
- CLAUDE.md updated to reflect the true hexagonal structure

**Stays the same:**
- Business logic in `Application/UseCase/` and `Application/EventHandler/` — no behaviour changes
- `Domain/` entities and enums — no changes
- Event names (string constants like `innovationsbox.project_idea.submitted`) — unchanged
- `on_start()` DI wiring pattern — extended, not replaced
- Concrete CMS routing, package handles, frontend package
