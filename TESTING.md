# Testing Strategy

## Test Pyramid

```
         ╱ E2E (Playwright) ╲         — Critical user flows
        ╱ Storybook Smoke    ╲        — Component rendering
       ╱ Integration (Pest)    ╲      — Real DB, CMS bootstrapped
      ╱ Unit (Pest + Mockery)    ╲    — Pure PHP, mocked ports
```

## Test Types

### Unit Tests (PHP)

**Location:** `tests/unit/`
**Framework:** Pest v1 + Mockery
**Run:** `composer test` or `composer test:unit`

Unit tests verify domain logic and application use cases in isolation. They mock `Port/Out/` interfaces (repositories, services) and test against `Port/In/` interfaces.

**How to write a unit test:**

```php
<?php
// tests/unit/inno_project_idea/Domain/ProjectIdeaTest.php
declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;

test('submit() creates idea with Submitted status', function () {
    $idea = ProjectIdea::submit('id_1', 'Title', 'Description', 'tech', 'user_1');
    expect($idea->getStatus())->toBe(IdeaStatus::Submitted);
});
```

**For use case handlers — mock the ports:**

```php
<?php
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

afterEach(fn () => Mockery::close());

test('handle() saves and dispatches', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $repo->shouldReceive('save')->once();
    $dispatcher->shouldReceive('dispatch')->once();

    $handler = new SubmitProjectIdeaHandler($repo, $dispatcher);
    $handler->handle(new SubmitProjectIdeaCommand('Title', 'Desc', 'cat', 'user_1'));
});
```

### Integration Tests (PHP)

**Location:** `tests/integration/`
**Run:** `composer test:integration`
**Requires:** Running MySQL + Concrete CMS bootstrapped

Integration tests verify that adapters work correctly with real infrastructure. Not run in the PR pipeline — reserved for staging post-deployment.

### Storybook Smoke Tests

**Location:** `tests/e2e/specs/storybook/`
**Run:** Requires PHP story server running on port 6007
**CI:** Run in pipeline Stage 3

These tests verify that all component partials render without errors by calling the PHP story server's HTTP API:

```typescript
test('button: primary renders with correct class', async ({ request }) => {
    const res = await request.get('http://localhost:6007/?id=atoms/button&variant=primary&label=Test');
    expect(res.status()).toBe(200);
    expect(await res.text()).toContain('class="button button--primary"');
});
```

Interactive components (dropdown, tabs, modal, toggle, checkbox, radio, form-field, search-bar, pagination) have dedicated spec files that assert ARIA wiring, disabled/error states, and conditional rendering (e.g. clear button only shown when value is present).

### Accessibility Gate (axe-core)

**Location:** `tests/e2e/specs/storybook/axe.spec.ts`
**Run:** Automatic as part of the Playwright E2E suite
**CI:** Run in pipeline Stage 3

Iterates every Storybook story, renders its HTML via the PHP story server wrapped in a minimal page document, and runs `@axe-core/playwright` filtered to `critical` and `serious` impacts. Tag filter: `wcag2a`, `wcag2aa`, `wcag21a`, `wcag21aa`. The three rules globally disabled in Storybook preview (`landmark-one-main`, `page-has-heading-one`, `region`) are also disabled here so the gate aligns with the UI addon behaviour.

Fixes belong at the component level — do not disable rules to make the gate pass.

### E2E Tests (Playwright)

**Location:** `tests/e2e/specs/`
**Run (Docker):** `docker compose -f docker-compose.test.yml up -d --build --wait && E2E_DOCKER=true npx playwright test --config=tests/e2e/playwright.config.ts --project=e2e`
**Run (local dev):** `npm run test:e2e` (requires running CMS at `$PROJECT_NAME.local:$APP_PORT`)
**CI:** Run in pipeline Stage 5

End-to-end tests verify critical user flows through the browser GUI — filling forms, clicking buttons, navigating pages — exactly as a real user would. No direct API calls (except Mailpit for email verification). The Docker environment spins up a full CMS + MySQL + Mailpit stack with seeded test data. See `docs/e2e-testing.md` for the complete guide including test users, seed data, and helper functions.

## Coverage

- **Threshold:** 80% line coverage, enforced in CI
- **Scope:** `public/packages/inno_*/src/` (all 5 DDD packages)
- **Run locally:** `composer test:coverage`
- **Driver:** pcov (in CI), xdebug (locally if installed)

## Folder Structure

```
tests/
  unit/
    inno_project_idea/
      Domain/                    ← Entity tests
      Application/               ← Use case handler tests
    inno_moderation/
      Domain/
      Application/
    inno_notification/
      Application/               ← Event handler tests
    inno_project_space/
      Domain/
      Application/
    helpers/                     ← ConcreteComposer helper tests
  integration/
    inno_project_idea/Adapter/Out/Persistence/
    inno_moderation/Adapter/Out/Persistence/
    inno_notification/Adapter/Out/Email/
    inno_project_space/Adapter/Out/Persistence/
  e2e/
    playwright.config.ts
    fixtures/helpers.ts          ← Browser login, CKEditor, Mailpit helpers (no API helpers)
    specs/
      00-smoke.spec.ts           ← Environment health, login smoke, badge counts
      01-auth.spec.ts            ← Register → validate → login → profile → forgot password → delete account
      02-idea-to-space.spec.ts   ← Submit idea → approve → project space created
      03-moderation-feedback.spec.ts  ← Submit → feedback → edit/resubmit → approve
      04-project-space.spec.ts   ← Join, accept/reject, status updates, collaboard, leave/remove
      05-expert-flow.spec.ts     ← Request expert → accept/decline
      06-moderation-admin.spec.ts ← Category CRUD, user management, expert flag, reject, deactivate
      auth-flow.spec.ts          ← Login/logout/avatar page checks
      expert-pool.spec.ts        ← Expert pool visibility (anonymous vs logged-in)
      profile-access.spec.ts     ← Profile page access control
      projektraeume.spec.ts      ← Project spaces list: filters, search, cards
      projektraum.spec.ts        ← Project space detail: header, timeline, sidebar
      storybook/
        smoke.spec.ts
        atoms.spec.ts
        molecules-organisms.spec.ts
        templates-pages.spec.ts
```

## Naming Conventions

- Test files: `*Test.php` (Pest), `*.spec.ts` (Playwright)
- Test names: describe the behavior, not the method: `'submit() creates idea with Submitted status'`
- One assertion focus per test — test one behavior, not multiple
- Use `afterEach(fn () => Mockery::close())` in every test file that uses Mockery
