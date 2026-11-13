# Comprehensive Quality Audit — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Raise security, test, Storybook/accessibility, and documentation quality to a production-ready gate before continuing feature work.

**Architecture:** Bottom-up approach across 5 phases: security hardening first (CSRF, XSS, dependency vulns), then test infrastructure (coverage enforcement, integration bootstrap, unit test patterns), then Storybook/a11y (token consistency, WCAG audit, axe-core CI), then documentation reconciliation (doc-vs-code accuracy), then final verification.

**Tech Stack:** PHP 8.3 (Pest, Mockery, PHPStan), TypeScript (Playwright, axe-core), SCSS (design tokens), Concrete CMS 9.5+, Storybook 8.6, Laravel Mix, Bitbucket Pipelines

---

## Phase 1: Security Hardening

### Task 1: CSRF Token Infrastructure for API Endpoints

All 4 API controllers accept state-changing POST/PUT/DELETE requests without CSRF validation. The frontend uses native `fetch()` without sending any CSRF token. We need a shared CSRF middleware trait and frontend token injection.

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/Http/CsrfValidationTrait.php`
- Modify: `public/packages/inno_moderation/src/Adapter/In/Http/ModerationApiController.php`
- Modify: `public/packages/inno_moderation/src/Adapter/In/Http/CategoryApiController.php`
- Modify: `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php`
- Modify: `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`
- Modify: `public/packages/inno_notification/src/Adapter/In/Http/NotificationApiController.php`
- Modify: `public/packages/inno_frontend/themes/innovationsbox/elements/header.php` (token meta tag)
- Create: `resources/js/api-client.ts` (shared fetch wrapper with CSRF)
- Modify: All 8 files in `resources/js/single_pages/*.ts`
- Test: `tests/unit/inno_shared/Infrastructure/Http/CsrfValidationTraitTest.php`

- [ ] **Step 1: Write test for CsrfValidationTrait**

```php
<?php
// tests/unit/inno_shared/Infrastructure/Http/CsrfValidationTraitTest.php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Http\CsrfValidationTrait;
use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;

// Create a concrete class that uses the trait for testing
$traitUser = new class {
    use CsrfValidationTrait;

    public function publicValidate(Request $request): void
    {
        $this->validateCsrfToken($request);
    }
};

afterEach(fn() => Mockery::close());

test('validateCsrfToken passes when token header matches session token', function () use ($traitUser) {
    $token = bin2hex(random_bytes(16));

    $request = Request::create('/api/test', 'POST');
    $request->headers->set('X-CSRF-Token', $token);

    // Mock the Concrete CMS token service
    $tokenService = Mockery::mock(\Concrete\Core\Validation\CSRF\Token::class);
    $tokenService->shouldReceive('validate')
        ->with('api', $token)
        ->andReturn(true);

    $app = Mockery::mock(\Concrete\Core\Application\Application::class);
    $app->shouldReceive('make')
        ->with('token')
        ->andReturn($tokenService);

    // Bind to the facade
    \Concrete\Core\Support\Facade\Application::setFacadeApplication($app);

    $traitUser->publicValidate($request);
    // No exception = pass
    expect(true)->toBeTrue();
});

test('validateCsrfToken throws on missing token', function () use ($traitUser) {
    $request = Request::create('/api/test', 'POST');

    $traitUser->publicValidate($request);
})->throws(ValidationException::class, 'CSRF token missing');

test('validateCsrfToken throws on invalid token', function () use ($traitUser) {
    $request = Request::create('/api/test', 'POST');
    $request->headers->set('X-CSRF-Token', 'bad-token');

    $tokenService = Mockery::mock(\Concrete\Core\Validation\CSRF\Token::class);
    $tokenService->shouldReceive('validate')
        ->with('api', 'bad-token')
        ->andReturn(false);

    $app = Mockery::mock(\Concrete\Core\Application\Application::class);
    $app->shouldReceive('make')
        ->with('token')
        ->andReturn($tokenService);

    \Concrete\Core\Support\Facade\Application::setFacadeApplication($app);

    $traitUser->publicValidate($request);
})->throws(ValidationException::class, 'CSRF token invalid');

test('validateCsrfToken skips GET requests', function () use ($traitUser) {
    $request = Request::create('/api/test', 'GET');
    $traitUser->publicValidate($request);
    expect(true)->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/unit/inno_shared/Infrastructure/Http/CsrfValidationTraitTest.php`
Expected: FAIL — class `CsrfValidationTrait` not found

- [ ] **Step 3: Implement CsrfValidationTrait**

```php
<?php
// public/packages/inno_shared/src/Infrastructure/Http/CsrfValidationTrait.php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Http;

use Concrete\Core\Support\Facade\Application;
use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;

trait CsrfValidationTrait
{
    protected function validateCsrfToken(Request $request): void
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
            return;
        }

        $token = $request->headers->get('X-CSRF-Token', '');
        if ($token === '' || $token === null) {
            throw new ValidationException('CSRF token missing', ['field' => 'X-CSRF-Token']);
        }

        $app = Application::getFacadeApplication();
        $tokenService = $app->make('token');

        if (!$tokenService->validate('api', $token)) {
            throw new ValidationException('CSRF token invalid', ['field' => 'X-CSRF-Token']);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/unit/inno_shared/Infrastructure/Http/CsrfValidationTraitTest.php`
Expected: PASS (all 4 tests)

- [ ] **Step 5: Add CSRF meta tag to header**

In `public/packages/inno_frontend/themes/innovationsbox/elements/header.php`, add inside `<head>`:

```php
<?php
$tokenService = app('token');
$csrfToken = $tokenService->generate('api');
?>
<meta name="csrf-token" content="<?= h($csrfToken) ?>">
```

- [ ] **Step 6: Create shared API client with CSRF**

```typescript
// resources/js/api-client.ts

function getCsrfToken(): string {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta?.getAttribute('content') ?? '';
}

export async function apiFetch(url: string, options: RequestInit = {}): Promise<Response> {
  const headers = new Headers(options.headers);

  if (!headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json');
  }

  const method = (options.method ?? 'GET').toUpperCase();
  if (method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS') {
    headers.set('X-CSRF-Token', getCsrfToken());
  }

  return fetch(url, { ...options, headers });
}
```

- [ ] **Step 7: Integrate CsrfValidationTrait into all controllers**

For each of the 5 API controllers, add the `use CsrfValidationTrait;` statement and call `$this->validateCsrfToken($request)` at the start of every POST/PUT/DELETE method.

Example for `ModerationApiController.php`:
```php
use Concrete\Package\InnoShared\Infrastructure\Http\CsrfValidationTrait;

class ModerationApiController extends AbstractController
{
    use CsrfValidationTrait;
    // ... existing constructor ...

    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->requirePermission('moderation.review');
            // ... rest unchanged ...
```

Apply the same pattern to:
- `CategoryApiController.php`: store(), update(), destroy()
- `ProjectIdeaApiController.php`: store(), update(), destroy()
- `ProjectSpaceApiController.php`: addCollaboard(), removeCollaboard(), addStatusUpdate(), editStatusUpdate(), deleteStatusUpdate(), joinRequest(), handleJoinRequest(), changeStatus(), invite(), requestExpert(), handleExpertRequest()
- `NotificationApiController.php`: markAsRead() (the POST endpoint)

- [ ] **Step 8: Replace fetch() calls in all single_pages with apiFetch()**

For each of the 8 files in `resources/js/single_pages/`, replace `fetch(` calls that use POST/PUT/DELETE with `apiFetch(` from the new module. Add the import at the top:

```typescript
import { apiFetch } from '../api-client';
```

Then replace e.g.:
```typescript
// Before
const res = await fetch(API_BASE, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: ... });

// After
const res = await apiFetch(API_BASE, { method: 'POST', body: ... });
```

Files to update:
- `moderation.ts` (0 POST calls — only GET, no changes needed)
- `moderation-detail.ts` (POST calls for approve/reject/feedback)
- `moderation-kategorien.ts` (POST/PUT/DELETE for category CRUD)
- `projektidee-einreichen.ts` (POST for submit)
- `projektidee-meine.ts` (PUT/DELETE calls)
- `projektraum.ts` (POST/PUT/DELETE calls — ~9 fetch calls)
- `projektraeume.ts` (mostly GET — check for any POST)
- `expert-anfragen.ts` (POST calls)

- [ ] **Step 9: Run all existing tests**

Run: `./vendor/bin/pest --testsuite=Unit && npm run typecheck`
Expected: All pass

- [ ] **Step 10: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Http/CsrfValidationTrait.php \
        resources/js/api-client.ts \
        tests/unit/inno_shared/Infrastructure/Http/CsrfValidationTraitTest.php \
        public/packages/inno_*/src/Adapter/In/Http/*.php \
        public/packages/inno_frontend/themes/innovationsbox/elements/header.php \
        resources/js/single_pages/*.ts
git commit -m "feat(security): add CSRF token validation to all API endpoints"
```

---

### Task 2: Frontend XSS Prevention — Shared escapeHtml and DOMPurify

Every single_page TS file defines its own local `escapeHtml()` function and uses `innerHTML` for rendering. We need to centralize escaping, add DOMPurify for any user-generated rich content, and audit all innerHTML usage.

**Files:**
- Create: `resources/js/utils/escape-html.ts`
- Modify: All 8 files in `resources/js/single_pages/*.ts` (remove local escapeHtml, import shared)
- Test: Manual — TypeScript compiler verifies all imports resolve

- [ ] **Step 1: Install DOMPurify**

Run: `npm install dompurify && npm install -D @types/dompurify`

- [ ] **Step 2: Create shared escape-html utility**

```typescript
// resources/js/utils/escape-html.ts

import DOMPurify from 'dompurify';

/**
 * Escape a plain-text string for safe insertion into HTML.
 * Uses DOM text node encoding (no regex).
 */
export function escapeHtml(text: string): string {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Sanitize HTML that may contain user-generated rich content.
 * Strips scripts, event handlers, and dangerous attributes.
 */
export function sanitizeHtml(dirty: string): string {
  return DOMPurify.sanitize(dirty, {
    ALLOWED_TAGS: ['b', 'i', 'em', 'strong', 'a', 'p', 'br', 'ul', 'ol', 'li'],
    ALLOWED_ATTR: ['href', 'target', 'rel'],
  });
}
```

- [ ] **Step 3: Replace local escapeHtml in all single_pages**

For each file that defines a local `escapeHtml()` function, remove the local definition and add:
```typescript
import { escapeHtml } from '../utils/escape-html';
```

Files with local escapeHtml: `moderation.ts`, `moderation-detail.ts`, `moderation-kategorien.ts`, `projektidee-einreichen.ts`, `projektidee-meine.ts`, `projektraum.ts`, `projektraeume.ts`, `expert-anfragen.ts`

- [ ] **Step 4: Audit innerHTML usage for user content injection**

Review all `innerHTML` assignments. The current pattern is safe when:
- Static HTML templates use `escapeHtml()` for all interpolated values
- URL params use `encodeURIComponent()`

Flag any case where user content is inserted without escapeHtml. If any rich-text fields exist (idea descriptions with formatting), use `sanitizeHtml()` instead.

- [ ] **Step 5: Run typecheck and lint**

Run: `npm run typecheck && npm run lint`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/js/utils/escape-html.ts resources/js/single_pages/*.ts package.json package-lock.json
git commit -m "feat(security): centralize escapeHtml, add DOMPurify for HTML sanitization"
```

---

### Task 3: Security Headers Middleware

No security headers are configured beyond what Concrete CMS provides by default.

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/Http/SecurityHeadersMiddleware.php`
- Modify: `public/packages/inno_shared/controller.php` (register middleware in on_start)

- [ ] **Step 1: Create security headers middleware**

```php
<?php
// public/packages/inno_shared/src/Infrastructure/Http/SecurityHeadersMiddleware.php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Response $response): Response
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
```

- [ ] **Step 2: Register middleware in inno_shared controller.php on_start()**

In `public/packages/inno_shared/controller.php`, add to `on_start()`:

```php
$this->app['director']->addListener('on_before_render', function ($event) {
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $response = $event->getResponse();
    if ($response) {
        $middleware = new \Concrete\Package\InnoShared\Infrastructure\Http\SecurityHeadersMiddleware();
        $middleware->handle($request, $response);
    }
});
```

Note: The exact Concrete CMS event hook for response modification depends on the version. If `on_before_render` doesn't provide access to the response object, use `application/config/concrete.php` to set headers via middleware config instead. Check Concrete CMS docs for v9.5+ response middleware pattern.

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Http/SecurityHeadersMiddleware.php \
        public/packages/inno_shared/controller.php
git commit -m "feat(security): add security response headers middleware"
```

---

### Task 4: npm Vulnerability Remediation

40 npm vulns (6 critical, 2 high). Most are transitive from `@concretecms/bedrock` and `laravel-mix`. `.nsprc` already excludes 5 known unfixable ones.

**Files:**
- Modify: `package.json`
- Modify: `.nsprc`
- Create: `SECURITY-KNOWN-ISSUES.md`

- [ ] **Step 1: Run npm audit to get current state**

Run: `npm audit 2>&1 | head -100`

- [ ] **Step 2: Attempt to fix auto-fixable vulnerabilities**

Run: `npm audit fix`

This will upgrade direct dependencies where safe (patch/minor). Review what changed.

- [ ] **Step 3: Assess remaining vulnerabilities**

For each remaining vuln, categorize:
- **Production-facing**: bootstrap 3 XSS (Popover/Tooltip) — check if these components are used in the theme
- **Build-time only**: babel, webpack-dev-server, laravel-mix transitives — lower risk
- **Dev-only**: CKEditor 4 (from @concretecms/bedrock) — only in CMS admin, not custom code

- [ ] **Step 4: Check if vulnerable bootstrap components are used**

Run: `grep -r "popover\|tooltip\|Popover\|Tooltip" public/packages/inno_frontend/ resources/` to determine if the vulnerable bootstrap 3 Popover/Tooltip components are used in custom code.

- [ ] **Step 5: Update .nsprc with documented exclusions**

Add any newly triaged transitive vulnerabilities to `.nsprc` with clear comments explaining why they're excluded and their risk level.

- [ ] **Step 6: Create SECURITY-KNOWN-ISSUES.md**

```markdown
# Known Security Issues

This document tracks known vulnerabilities that cannot be immediately resolved.
Last reviewed: 2026-06-02

## npm Dependencies (transitive, from @concretecms/bedrock and laravel-mix)

| Advisory | Package | Severity | Risk Assessment | Mitigation |
|----------|---------|----------|-----------------|------------|
| GHSA-xxx | bootstrap 3 (XSS in Popover/Tooltip) | Moderate | Low — these components are not used in custom theme code | Upgrade when @concretecms/bedrock supports bootstrap 5 |
| GHSA-xxx | CKEditor 4 (XSS) | Critical | Low — only exposed in CMS admin panel, not in custom code | Upgrade when Concrete CMS 10 ships with CKEditor 5 |
| GHSA-xxx | elliptic | Critical | Low — build-time only, no runtime crypto operations | Excluded in .nsprc |
| GHSA-xxx | webpack-dev-server | High | None — dev-only dependency | Excluded in .nsprc |

## Composer Dependencies

_None at time of writing. Run `composer audit` to verify._

## Review Schedule

Review this document monthly. Remove entries when upstream fixes become available.
```

Fill in actual GHSA IDs from the npm audit output.

- [ ] **Step 7: Verify npm audit passes**

Run: `npm run audit:npm`
Expected: PASS (all remaining vulns excluded in .nsprc)

- [ ] **Step 8: Run composer audit**

Run: `composer audit --abandoned=ignore`
Expected: PASS (no actionable vulnerabilities)

- [ ] **Step 9: Commit**

```bash
git add package.json package-lock.json .nsprc SECURITY-KNOWN-ISSUES.md
git commit -m "fix(security): remediate npm vulnerabilities, document known issues"
```

---

### Task 5: Verify Semgrep SAST and detect-secrets

**Files:**
- Modify: `bitbucket-pipelines.yml` (already installs semgrep — verify)
- No file changes needed if tooling works

- [ ] **Step 1: Test semgrep locally**

Run: `npx semgrep --version 2>&1 || echo "Not installed locally"`

If not installed: semgrep runs in CI via `pip3 install semgrep` (line 127 of bitbucket-pipelines.yml). Local runs are optional. Document this in CONTRIBUTING.md.

- [ ] **Step 2: Verify detect-secrets hook**

Run: `cat .husky/pre-commit` and check if detect-secrets is configured.
Run: `detect-secrets --version 2>&1 || echo "Not installed"`

If detect-secrets is in the pre-commit hook but not installed locally, document the installation in CONTRIBUTING.md.

- [ ] **Step 3: Review .env.dist for secrets**

Read `.env.dist` — it should contain only placeholder values. Current state: confirmed safe (empty DB placeholders, no real credentials). No changes needed.

- [ ] **Step 4: Commit if any changes**

```bash
git add -A && git diff --cached --quiet || git commit -m "docs: document semgrep and detect-secrets setup"
```

---

### Task 6: Input Validation Audit

API controllers accept user input with minimal validation. Add explicit validation for all user-facing inputs.

**Files:**
- Create: `public/packages/inno_shared/src/Infrastructure/Validation/InputValidator.php`
- Modify: `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php`
- Modify: `public/packages/inno_moderation/src/Adapter/In/Http/CategoryApiController.php`
- Modify: `public/packages/inno_project_space/src/Adapter/In/Http/ProjectSpaceApiController.php`
- Test: `tests/unit/inno_shared/Infrastructure/Validation/InputValidatorTest.php`

- [ ] **Step 1: Write test for InputValidator**

```php
<?php
// tests/unit/inno_shared/Infrastructure/Validation/InputValidatorTest.php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Validation\InputValidator;
use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

test('requireString returns trimmed value', function () {
    $validator = new InputValidator(['title' => '  Hello  ']);
    expect($validator->requireString('title', 1, 255))->toBe('Hello');
});

test('requireString throws on empty', function () {
    $validator = new InputValidator(['title' => '  ']);
    $validator->requireString('title', 1, 255);
})->throws(ValidationException::class);

test('requireString throws on too long', function () {
    $validator = new InputValidator(['title' => str_repeat('a', 256)]);
    $validator->requireString('title', 1, 255);
})->throws(ValidationException::class);

test('requireString throws on missing key', function () {
    $validator = new InputValidator([]);
    $validator->requireString('title', 1, 255);
})->throws(ValidationException::class);

test('optionalString returns null when missing', function () {
    $validator = new InputValidator([]);
    expect($validator->optionalString('notes', 1000))->toBeNull();
});

test('optionalString returns trimmed value when present', function () {
    $validator = new InputValidator(['notes' => ' hello ']);
    expect($validator->optionalString('notes', 1000))->toBe('hello');
});

test('requireEnum throws on invalid value', function () {
    $validator = new InputValidator(['status' => 'invalid']);
    $validator->requireEnum('status', ['active', 'archived']);
})->throws(ValidationException::class);

test('requireEnum returns valid value', function () {
    $validator = new InputValidator(['status' => 'active']);
    expect($validator->requireEnum('status', ['active', 'archived']))->toBe('active');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/unit/inno_shared/Infrastructure/Validation/InputValidatorTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Implement InputValidator**

```php
<?php
// public/packages/inno_shared/src/Infrastructure/Validation/InputValidator.php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Validation;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InputValidator
{
    public function __construct(private readonly array $data) {}

    public function requireString(string $field, int $minLength, int $maxLength): string
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value === '') {
            throw new ValidationException("{$field} is required.", ['field' => $field]);
        }
        if (mb_strlen($value) < $minLength) {
            throw new ValidationException("{$field} must be at least {$minLength} characters.", ['field' => $field]);
        }
        if (mb_strlen($value) > $maxLength) {
            throw new ValidationException("{$field} must not exceed {$maxLength} characters.", ['field' => $field]);
        }
        return $value;
    }

    public function optionalString(string $field, int $maxLength): ?string
    {
        if (!isset($this->data[$field]) || trim((string) $this->data[$field]) === '') {
            return null;
        }
        $value = trim((string) $this->data[$field]);
        if (mb_strlen($value) > $maxLength) {
            throw new ValidationException("{$field} must not exceed {$maxLength} characters.", ['field' => $field]);
        }
        return $value;
    }

    public function requireEnum(string $field, array $allowed): string
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if (!in_array($value, $allowed, true)) {
            $list = implode(', ', $allowed);
            throw new ValidationException("{$field} must be one of: {$list}.", ['field' => $field]);
        }
        return $value;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/unit/inno_shared/Infrastructure/Validation/InputValidatorTest.php`
Expected: PASS (all 8 tests)

- [ ] **Step 5: Apply InputValidator to ProjectIdeaApiController::store()**

In `public/packages/inno_project_idea/src/Adapter/In/Http/ProjectIdeaApiController.php`, replace the manual `trim()` validation with:

```php
use Concrete\Package\InnoShared\Infrastructure\Validation\InputValidator;

// Inside store() method, after parsing $body:
$v = new InputValidator($body);
$title = $v->requireString('title', 3, 255);
$description = $v->requireString('description', 10, 5000);
$categoryId = $v->optionalString('categoryId', 100);
```

Apply the same pattern to `update()` and to all other controllers that accept user input. The specific fields and lengths depend on each endpoint's domain requirements.

- [ ] **Step 6: Run all tests**

Run: `./vendor/bin/pest --testsuite=Unit`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Validation/InputValidator.php \
        tests/unit/inno_shared/Infrastructure/Validation/InputValidatorTest.php \
        public/packages/inno_*/src/Adapter/In/Http/*.php
git commit -m "feat(security): add InputValidator for API endpoint input validation"
```

---

### Task 7: Verify PHP Template Output Escaping

Audit all PHP component and template files for consistent use of `h()` helper.

**Files:**
- Potentially modify: any PHP template missing `h()` calls

- [ ] **Step 1: Search for unescaped output in PHP templates**

Run grep across all component and template PHP files for `<?=` without `h()`:
```bash
grep -rn '<?=' public/packages/inno_frontend/components/ public/packages/inno_frontend/themes/ public/packages/inno_frontend/single_pages/ public/packages/inno_frontend/blocks/ | grep -v 'h(' | grep -v 'htmlspecialchars'
```

- [ ] **Step 2: Fix any unescaped outputs**

For each match, determine if the value is:
- User-supplied data → wrap in `h()`
- Static HTML/markup → safe, leave as-is
- Already escaped upstream → document why

- [ ] **Step 3: Commit if changes**

```bash
git add -A && git diff --cached --quiet || git commit -m "fix(security): escape unescaped output in PHP templates"
```

---

## Phase 2: Test Infrastructure

### Task 8: Enforce Coverage Threshold in CI

Coverage is enforced via `composer test:coverage` (`--min=80`) but `composer test` only runs `@test:unit` without coverage. The CI pipeline does enforce it (line 83 of `bitbucket-pipelines.yml`). We need to verify and make `composer test` locally also enforce coverage.

**Files:**
- Modify: `composer.json` (scripts section)

- [ ] **Step 1: Update composer test script to include coverage**

In `composer.json`, change:
```json
"test": ["@test:unit"]
```
to:
```json
"test": ["@test:coverage"]
```

This ensures local `composer test` also enforces the 80% minimum.

- [ ] **Step 2: Run to verify threshold is enforced**

Run: `composer test`
Expected: Tests pass with coverage report, and if below 80%, the command fails.

- [ ] **Step 3: Commit**

```bash
git add composer.json
git commit -m "fix(test): enforce 80% coverage threshold in composer test"
```

---

### Task 9: Integration Test Bootstrap

The `tests/integration/run_integration_tests.php` file runs inside Concrete CMS via `c5:exec` and tests cross-cutting concerns. But there are no Pest-based integration tests. We need a Pest bootstrap that can run against a real DB.

**Files:**
- Create: `tests/integration/bootstrap.php`
- Create: `tests/integration/Pest.php`
- Create: `tests/integration/inno_moderation/Persistence/ConcreteModerationReviewRepositoryTest.php`
- Modify: `phpunit.xml` (verify integration suite config)

- [ ] **Step 1: Create integration test bootstrap**

```php
<?php
// tests/integration/bootstrap.php

declare(strict_types=1);

/**
 * Integration test bootstrap.
 *
 * Requires a running Concrete CMS instance with database access.
 * Set CONCRETE5_ENV=live and ensure application/config/live.database.php exists.
 *
 * Run: composer test:integration
 * Prerequisites: make install (or equivalent CMS setup)
 */

// Load the CMS bootstrap
$cmsRoot = dirname(__DIR__, 2) . '/public';
$dispatcher = $cmsRoot . '/concrete/dispatcher.php';

if (!file_exists($dispatcher)) {
    echo "ERROR: Concrete CMS not found at {$cmsRoot}.\n";
    echo "Run 'composer install' and 'make install' first.\n";
    exit(1);
}

// Boot the CMS application
define('DIR_BASE', $cmsRoot);
require $dispatcher;

$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
```

- [ ] **Step 2: Create Pest.php for integration suite**

```php
<?php
// tests/integration/Pest.php

declare(strict_types=1);

// No additional Pest config needed — the bootstrap handles CMS initialization.
```

- [ ] **Step 3: Write exemplary integration test for moderation repository**

```php
<?php
// tests/integration/inno_moderation/Persistence/ConcreteModerationReviewRepositoryTest.php

declare(strict_types=1);

use Concrete\Core\Support\Facade\Application;
use Concrete\Package\InnoModeration\Adapter\Out\Persistence\ConcreteModerationReviewRepository;

beforeEach(function () {
    $this->app = Application::getFacadeApplication();
    $this->db = $this->app->make('database/connection');
    $this->repo = new ConcreteModerationReviewRepository($this->db);

    // Begin transaction — rolled back in afterEach for isolation
    $this->db->beginTransaction();
});

afterEach(function () {
    $this->db->rollBack();
});

test('findPending returns empty array when no reviews exist', function () {
    // Truncate within the transaction
    $this->db->executeStatement('DELETE FROM ib_moderation_reviews');

    $result = $this->repo->findPending();
    expect($result)->toBeArray()->toBeEmpty();
});

test('save persists and findById retrieves a review', function () {
    // This test depends on the actual repository method signatures.
    // Adjust to match ConcreteModerationReviewRepository::save() and ::findById()
    $this->markTestSkipped('Implement after verifying repository method signatures');
});
```

- [ ] **Step 4: Verify phpunit.xml integration suite config**

Read `phpunit.xml` and confirm the Integration testsuite points to `tests/integration/` with the correct bootstrap. If needed, update:

```xml
<testsuite name="Integration">
    <directory>tests/integration</directory>
</testsuite>
```

And ensure the integration bootstrap is referenced:
```xml
<phpunit bootstrap="tests/integration/bootstrap.php">
```

Note: The unit and integration suites need separate bootstraps. Update phpunit.xml to support this — either via separate config files or by making the integration suite use its own bootstrap.

- [ ] **Step 5: Update docs/testing.md with integration test setup**

Add a section documenting:
- Prerequisites (running CMS, database)
- How to run: `composer test:integration`
- The transaction-rollback pattern for test isolation
- How to add new integration tests

- [ ] **Step 6: Commit**

```bash
git add tests/integration/ phpunit.xml docs/testing.md
git commit -m "feat(test): add integration test bootstrap with exemplary repository test"
```

---

### Task 10: Unit Test Coverage for Critical Paths

Write unit tests for the highest-risk untested code: event handlers and use case handlers that currently have zero tests.

**Files:**
- Create: `tests/unit/inno_notification/Application/OnProjectIdeaApprovedTest.php`
- Create: `tests/unit/inno_notification/Application/OnProjectIdeaRejectedTest.php`
- Create: `tests/unit/inno_moderation/Application/OnProjectIdeaSubmittedTest.php`
- Create: `tests/unit/inno_moderation/Application/OnProjectIdeaResubmittedTest.php`
- Create: `tests/unit/inno_project_idea/Application/OnModerationDecisionMadeTest.php`
- Create: `tests/unit/inno_project_idea/Application/OnModerationFeedbackRequestedTest.php`
- Create: `tests/unit/inno_project_idea/Application/OnProjectSpaceCreatedTest.php`

- [ ] **Step 1: Write test for OnProjectIdeaApproved notification handler**

Follow the established pattern from `OnProjectIdeaSubmittedTest.php`:

```php
<?php
// tests/unit/inno_notification/Application/OnProjectIdeaApprovedTest.php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaApproved;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

afterEach(fn() => Mockery::close());

test('handle() sends approval email to submitter', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $config = Mockery::mock(ConfigInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    // Read the actual handler to determine what config keys and email params it uses.
    // Adjust these mocks to match the real implementation.
    $emailService->shouldReceive('send')
        ->once()
        ->with(
            Mockery::type('string'),  // recipient
            Mockery::on(fn($s) => str_contains($s, 'Solar Panels')),  // subject contains idea title
            Mockery::type('string'),  // body
        );

    $logger->shouldReceive('info')->once();

    $handler = new OnProjectIdeaApproved($emailService, $config, $logger);
    $handler->handle(new ProjectIdeaApproved(
        ideaId: 'idea_1',
        submitterUserId: 'user_1',
        title: 'Solar Panels',
    ));
});
```

Note: Read the actual `OnProjectIdeaApproved.php` handler before writing this test. The constructor params, event constructor, and mock expectations must match the real implementation exactly. The pattern above follows `OnProjectIdeaSubmittedTest.php` — adjust as needed.

- [ ] **Step 2: Write test for OnProjectIdeaRejected notification handler**

Same pattern as Step 1 but for the rejection handler. The event will include a rejection reason.

```php
<?php
// tests/unit/inno_notification/Application/OnProjectIdeaRejectedTest.php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaRejected;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

afterEach(fn() => Mockery::close());

test('handle() sends rejection email with reason to submitter', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $config = Mockery::mock(ConfigInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    // Read the actual handler to determine exact params
    $emailService->shouldReceive('send')->once();
    $logger->shouldReceive('info')->once();

    $handler = new OnProjectIdeaRejected($emailService, $config, $logger);
    $handler->handle(new ProjectIdeaRejected(
        ideaId: 'idea_1',
        submitterUserId: 'user_1',
        title: 'Solar Panels',
        reason: 'Not climate-relevant',
    ));
});
```

- [ ] **Step 3: Write test for moderation OnProjectIdeaSubmitted handler**

```php
<?php
// tests/unit/inno_moderation/Application/OnProjectIdeaSubmittedTest.php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Application\EventHandler\OnProjectIdeaSubmitted;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;

afterEach(fn() => Mockery::close());

test('handle() creates a pending moderation review for submitted idea', function () {
    $repo = Mockery::mock(ModerationReviewRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    // Read the actual handler to determine constructor params and method calls
    $repo->shouldReceive('save')->once()->with(Mockery::on(function ($review) {
        return $review->getIdeaId() === 'idea_1';
    }));

    $logger->shouldReceive('info')->once();

    $handler = new OnProjectIdeaSubmitted($repo, $logger);
    $handler->handle(new ProjectIdeaSubmitted('idea_1', 'user_1', 'Solar Panels'));
});
```

- [ ] **Step 4: Write test for moderation OnProjectIdeaResubmitted handler**

Same pattern — handler resets review status to pending after feedback was addressed.

- [ ] **Step 5: Write test for project idea OnModerationDecisionMade handler**

This is the most complex handler — it applies moderator overrides, updates idea status, and dispatches follow-up events.

```php
<?php
// tests/unit/inno_project_idea/Application/OnModerationDecisionMadeTest.php

declare(strict_types=1);

use Concrete\Package\InnoProjectIdea\Application\EventHandler\OnModerationDecisionMade;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationDecisionMade;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Core\Support\Facade\Events;

afterEach(fn() => Mockery::close());

test('handle() approves idea and dispatches ProjectIdeaApproved event', function () {
    $repo = Mockery::mock(ProjectIdeaRepositoryInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    // Read actual handler — adjust constructor, method calls, event dispatch
    $repo->shouldReceive('findById')->with('idea_1')->andReturn(/* mock idea */);
    $repo->shouldReceive('save')->once();
    $logger->shouldReceive('info');

    // The handler likely dispatches events via Events facade — mock accordingly
    $handler = new OnModerationDecisionMade($repo, $logger);
    // Construct event with actual params from ModerationDecisionMade class
    $handler->handle(new ModerationDecisionMade(
        reviewId: 'review_1',
        ideaId: 'idea_1',
        approved: true,
        reviewerId: 'moderator_1',
        notes: 'Looks good',
    ));
});

test('handle() rejects idea and dispatches ProjectIdeaRejected event', function () {
    // Similar pattern with approved: false
});
```

- [ ] **Step 6: Write tests for OnModerationFeedbackRequested and OnProjectSpaceCreated**

Follow the same pattern for the remaining 2 event handlers in the project_idea package.

- [ ] **Step 7: Run all tests with coverage**

Run: `composer test`
Expected: All pass with coverage trending upward toward 80%

- [ ] **Step 8: Commit**

```bash
git add tests/unit/inno_notification/ tests/unit/inno_moderation/ tests/unit/inno_project_idea/
git commit -m "test: add unit tests for all event handlers across packages"
```

---

### Task 11: Expand Storybook Smoke Test Coverage

Currently 15 Storybook test specs cover a subset of the 63 components. The `axe.spec.ts` already tests all stories dynamically, but the smoke/rendering tests need expanding.

**Files:**
- Modify: `tests/e2e/specs/storybook/atoms.spec.ts` (verify coverage)
- Modify: `tests/e2e/specs/storybook/molecules-organisms.spec.ts` (verify coverage)

- [ ] **Step 1: Inventory current coverage**

Read `tests/e2e/specs/storybook/atoms.spec.ts` and `molecules-organisms.spec.ts` to see which components they test. Compare against the full component list.

- [ ] **Step 2: Identify gaps**

Check if `atoms.spec.ts` covers all 24 atoms and `molecules-organisms.spec.ts` covers all 26 molecules + 9 organisms. The individual spec files (checkbox, dropdown, modal, etc.) provide deeper testing for interactive components.

- [ ] **Step 3: Add missing component smoke tests**

For any component not covered in any spec, add a basic rendering test to the appropriate file. Follow the pattern used in existing specs — load the component via the PHP story server and verify it renders without error.

- [ ] **Step 4: Run Storybook tests**

Run: `npm run storybook:build && php -S localhost:6007 storybook-server/index.php & sleep 2 && npx playwright test tests/e2e/specs/storybook/ --config=tests/e2e/playwright.config.ts`
Expected: All pass

- [ ] **Step 5: Commit**

```bash
git add tests/e2e/specs/storybook/
git commit -m "test: expand Storybook smoke tests to cover all 63 components"
```

---

### Task 12: CI Gate Alignment

Verify the pipeline runs all checks added in Phase 1 and 2.

**Files:**
- Potentially modify: `bitbucket-pipelines.yml`

- [ ] **Step 1: Verify Stage 3 enforces coverage**

Read `bitbucket-pipelines.yml` line 83: `./vendor/bin/pest --testsuite=Unit --coverage --min=80` — this is correct.

- [ ] **Step 2: Verify Stage 4 runs semgrep**

Line 127: `semgrep --config=auto --error resources/js/ public/packages/inno_*/src/ storybook-server/` — this is correct.

- [ ] **Step 3: Verify new TypeScript files are included in linting**

The `npm run lint` command uses `eslint resources/js/ --ext .ts` which will pick up the new `resources/js/api-client.ts` and `resources/js/utils/escape-html.ts`. No changes needed.

- [ ] **Step 4: Commit if any pipeline changes**

```bash
git add bitbucket-pipelines.yml 2>/dev/null && git diff --cached --quiet || git commit -m "ci: align pipeline with new security and test checks"
```

---

## Phase 3: Storybook & Accessibility

### Task 13: Design Token Consistency Audit

Verify all component SCSS uses design tokens exclusively.

**Files:**
- Potentially modify: any component SCSS files with hardcoded values

- [ ] **Step 1: Search for hardcoded colors in component SCSS**

Run:
```bash
grep -rn '#[0-9a-fA-F]\{3,8\}\b' public/packages/inno_frontend/components/ --include='*.scss' | grep -v '// token-exception'
grep -rn 'rgb\s*(' public/packages/inno_frontend/components/ --include='*.scss'
grep -rn 'rgba\s*(' public/packages/inno_frontend/components/ --include='*.scss'
```

- [ ] **Step 2: Search for hardcoded spacing/font-size**

Run:
```bash
grep -rn '[0-9]\+px' public/packages/inno_frontend/components/ --include='*.scss' | grep -v 'var(' | grep -v '// px-exception' | grep -v 'border'
```

Note: some `px` values are intentional (1px borders, 2px focus rings). Only flag spacing/margin/padding/font-size values.

- [ ] **Step 3: Replace hardcoded values with tokens**

For each hardcoded value found, replace with the appropriate token:
- Colors → `var(--color-*)`
- Spacing → `var(--space-*)`
- Font sizes → `var(--font-size-*)` (if token exists) or use the typography scale
- Border radius → `var(--radius-*)`

- [ ] **Step 4: Search for competing variable definitions**

```bash
grep -rn 'var(--' resources/css/ --include='*.scss' | grep -v '_generated-variables' | grep -v 'helper/' | grep -v 'node_modules'
```

Verify no SCSS file defines CSS custom properties that compete with `_generated-variables.scss`.

- [ ] **Step 5: Verify token regeneration**

Run: `npm run tokens && git diff resources/css/global/_generated-variables.scss`
Expected: No diff (tokens SCSS matches tokens.json)

- [ ] **Step 6: Commit if changes**

```bash
git add public/packages/inno_frontend/components/ resources/css/
git diff --cached --quiet || git commit -m "fix(design): replace hardcoded values with design tokens"
```

---

### Task 14: Component Drift Audit (Storybook ↔ CMS)

Verify every CMS block template uses component partials and no HTML bypasses the component library.

**Files:**
- No new files expected — audit only, fix drift if found

- [ ] **Step 1: Verify all block templates use component partials**

```bash
# All block templates should include from DIR_COMPONENTS
for f in $(find public/packages/inno_frontend/blocks -name 'view.php'); do
  if ! grep -q 'DIR_COMPONENTS\|include.*components' "$f"; then
    echo "DRIFT: $f does not use component partials"
  fi
done
```

- [ ] **Step 2: Verify single_pages use component partials where applicable**

```bash
for f in $(find public/packages/inno_frontend/single_pages -name '*.php'); do
  echo "=== $f ==="
  grep -c 'DIR_COMPONENTS' "$f" || echo "  (no component includes)"
done
```

Single pages that render UI should use component partials. Pages that are purely JS-driven (content rendered via TypeScript with innerHTML) are acceptable but should be noted.

- [ ] **Step 3: Check webpack mapping completeness**

Read `webpack.mix.js` and verify every component with block-specific assets has an entry in the mapping table. Currently 5 blocks are mapped.

- [ ] **Step 4: Fix any drift found**

If a block template has inline HTML that should use a component, refactor it to use `include DIR_COMPONENTS . '/path/component.php'`.

- [ ] **Step 5: Commit if changes**

```bash
git add public/packages/inno_frontend/
git diff --cached --quiet || git commit -m "fix(storybook): resolve component drift between Storybook and CMS blocks"
```

---

### Task 15: axe-core CI Gate — Verify and Strengthen

The axe-core gate already exists in `tests/e2e/specs/storybook/axe.spec.ts`. It filters to critical/serious only. We need to verify it works and consider including moderate violations.

**Files:**
- Potentially modify: `tests/e2e/specs/storybook/axe.spec.ts`

- [ ] **Step 1: Build Storybook and run axe tests**

Run:
```bash
npm run storybook:build
php -S localhost:6007 storybook-server/index.php &
sleep 2
npx playwright test tests/e2e/specs/storybook/axe.spec.ts --config=tests/e2e/playwright.config.ts --reporter=line
```

- [ ] **Step 2: Review results**

If all pass: the current gate is working. Check how many stories are tested (should be close to 63 components × N variants).

- [ ] **Step 3: Consider including moderate violations**

Currently the gate only fails on `critical` or `serious`. To strengthen:

```typescript
// In axe.spec.ts, change the filter to include moderate:
const actionable = results.violations.filter(
  (v) => v.impact === 'critical' || v.impact === 'serious' || v.impact === 'moderate',
);
```

Only make this change if the current moderate violations count is zero or near-zero. If there are many, fix the violations first, then tighten the gate.

- [ ] **Step 4: Document disabled rules**

Add a comment block to `axe.spec.ts` explaining why the 3 rules are disabled:

```typescript
/**
 * Disabled rules rationale:
 * - landmark-one-main: Components are tested in isolation, not as full pages
 * - page-has-heading-one: Same — individual components don't need h1
 * - region: Same — component snippets aren't wrapped in landmark regions
 */
```

- [ ] **Step 5: Commit if changes**

```bash
git add tests/e2e/specs/storybook/axe.spec.ts
git diff --cached --quiet || git commit -m "fix(a11y): strengthen axe-core CI gate, document disabled rules"
```

---

### Task 16: WCAG 2.1 AA Manual Audit

This task requires browser testing. Systematically check all interactive components for keyboard, focus, contrast, and screen reader accessibility.

**Files:**
- Create: `docs/a11y-audit-2026-06.md` (findings and fixes)
- Potentially modify: component PHP and SCSS files

- [ ] **Step 1: Start Storybook**

Run: `npm run storybook`
Open: `http://localhost:6006`

- [ ] **Step 2: Keyboard navigation audit**

Test each interactive component in Storybook:
- **Tab order**: Can you reach all interactive elements with Tab/Shift+Tab?
- **Activation**: Can buttons be activated with Enter/Space?
- **Selection**: Can select/radio/checkbox be operated with Arrow keys and Space?
- **Escape**: Does modal close with Escape? Does dropdown close with Escape?
- **Focus visible**: Is there a visible focus indicator on every interactive element?

Components to test: button, checkbox, radio, select, toggle, input, textarea, dropdown, modal, tabs, pagination, search-bar, navigation-drawer, form-field, link

Document findings in `docs/a11y-audit-2026-06.md`.

- [ ] **Step 3: Focus management audit**

- **Modal**: Does opening the modal trap focus inside? Does closing restore focus to the trigger element?
- **Dropdown**: Does opening move focus to first item? Does closing restore focus?
- **Navigation drawer**: Is focus managed on open/close?

- [ ] **Step 4: Color contrast audit**

Check token color palette combinations:
- Primary text on white background
- Secondary text on white background
- Inverse text on primary background
- Status colors (error, warning, success, info) text on white
- Link text on white background

Use browser DevTools or axe-core contrast checker. All must meet 4.5:1 for normal text, 3:1 for large text (18px+ or 14px+ bold).

- [ ] **Step 5: Screen reader audit (optional but recommended)**

If a screen reader is available (VoiceOver, NVDA):
- Verify form fields announce their labels
- Verify error messages are announced via aria-live or aria-describedby
- Verify modals announce their title on open
- Verify status messages (loading spinners, success toasts) use appropriate ARIA roles

- [ ] **Step 6: Fix violations found**

For each violation, fix the component PHP and/or SCSS:
- Missing focus ring → add `@include a11y.focus-visible-ring`
- Missing aria attribute → add to PHP template
- Contrast failure → adjust token colors or component-specific overrides

- [ ] **Step 7: Commit**

```bash
git add docs/a11y-audit-2026-06.md public/packages/inno_frontend/components/
git commit -m "fix(a11y): WCAG 2.1 AA audit findings and fixes"
```

---

### Task 17: Corporate Design Consistency Check

Verify visual consistency across all component variants.

**Files:**
- Potentially modify: component SCSS files

- [ ] **Step 1: Check primary action color consistency**

```bash
grep -rn 'color-action-primary\|color-brand-primary' public/packages/inno_frontend/components/ --include='*.scss' | head -30
```

Verify all "primary" variants across components (button primary, link primary, badge primary, etc.) use the same token.

- [ ] **Step 2: Check typography scale consistency**

```bash
grep -rn 'font-size\|font-weight' public/packages/inno_frontend/components/ --include='*.scss' | grep -v 'var(' | head -30
```

Any hardcoded font sizes should use tokens.

- [ ] **Step 3: Check spacing consistency**

Verify margin/padding values use the spacing scale (`--space-1` through `--space-10`). Flag any arbitrary values like `17px` or `23px`.

- [ ] **Step 4: Visual review in Storybook**

Browse all components in Storybook and verify:
- Consistent visual language (same border radius, shadow, spacing rhythm)
- Color palette is cohesive
- No off-brand colors or fonts

- [ ] **Step 5: Commit if changes**

```bash
git add public/packages/inno_frontend/components/
git diff --cached --quiet || git commit -m "fix(design): align component variants with corporate design tokens"
```

---

## Phase 4: Documentation Reconciliation

### Task 18: OpenAPI Spec Sync

Verify the committed OpenAPI spec matches the code.

**Files:**
- Modify: `docs/api/openapi.yaml` and `docs/api/openapi.json` (if drift found)

- [ ] **Step 1: Regenerate OpenAPI spec**

Run: `composer openapi:generate`

- [ ] **Step 2: Check for drift**

Run: `git diff docs/api/`

If there's a diff, the committed spec was outdated. Review the changes to ensure they're correct.

- [ ] **Step 3: Lint the spec**

Run: `npm run lint:api`
Expected: PASS (no Spectral violations)

- [ ] **Step 4: Verify all endpoints have OpenAPI attributes**

```bash
# List all public methods in API controllers
grep -rn 'public function' public/packages/inno_*/src/Adapter/In/Http/*.php

# List all OA\Get/Post/Put/Delete attributes
grep -rn '#\[OA\\' public/packages/inno_*/src/Adapter/In/Http/*.php
```

Every public endpoint method should have corresponding OpenAPI attributes.

- [ ] **Step 5: Commit if changes**

```bash
git add docs/api/
git diff --cached --quiet || git commit -m "docs(api): sync OpenAPI spec with codebase"
```

---

### Task 19: Arc42 Typst Documentation Accuracy

Verify the architecture documentation matches reality.

**Files:**
- Potentially modify: typst files in `docs/typst/innovationsbox-dokumentation/src/product/arc42/`

- [ ] **Step 1: Verify event table**

Read the arc42 chapter that documents events (likely `05_building_block_view.typ` or `06_runtime_view.typ`) and compare against the 17 actual event classes in `public/packages/inno_shared/src/Domain/Event/`. The CLAUDE.md table only lists 3 events per package — the actual count is 17 events total:

Events to verify:
- ExpertRequestAccepted, ExpertRequestDeclined, ExpertRequested
- JoinRequestAccepted, JoinRequestReceived, JoinRequestRejected
- MemberInvited
- ModerationDecisionMade, ModerationFeedbackRequested
- ProjectIdeaApproved, ProjectIdeaRejected, ProjectIdeaResubmitted, ProjectIdeaSubmitted
- ProjectSpaceActivated, ProjectSpaceCompleted, ProjectSpaceCreated
- StatusUpdatePublished

- [ ] **Step 2: Verify package communication table**

Check the building block view for the package table. Compare against actual routes registered in each package's `controller.php`:

- inno_moderation: `/api/moderation` (GET, show, approve, reject, feedback) + `/api/moderation/category` (CRUD)
- inno_project_idea: `/api/project-idea` (CRUD + mine)
- inno_project_space: `/api/project-space` (many endpoints: collaboard, status updates, join requests, expert requests, invites)
- inno_notification: `/api/notification` (GET, mark-read)

- [ ] **Step 3: Verify deployment view**

Check `07_deployment_view.typ` — should describe the 4-stage PR pipeline, tag-push deployment, staging branch deployment, and weekly dependency report.

- [ ] **Step 4: Try compiling typst docs**

Run: `cd docs/typst/innovationsbox-dokumentation && typst compile main.typ 2>&1 | head -20`

If typst is not installed locally, note this and skip. The pipeline builds it separately.

- [ ] **Step 5: Fix any inaccuracies found**

Update the typst files to reflect current reality.

- [ ] **Step 6: Commit if changes**

```bash
git add docs/typst/
git diff --cached --quiet || git commit -m "docs(arc42): update architecture docs to match current codebase"
```

---

### Task 20: Markdown Documentation Completeness

Verify all markdown docs are accurate against code.

**Files:**
- Potentially modify: `docs/cross-cutting-concerns/*.md`, `docs/testing.md`, `README.md`, `CONTRIBUTING.md`, `SECURITY.md`, `CLAUDE.md`

- [ ] **Step 1: Audit cross-cutting concerns docs**

For each file in `docs/cross-cutting-concerns/`:
1. Read the doc
2. Check if the described patterns match actual code in `inno_shared/src/Infrastructure/`
3. Flag any aspirational content not yet implemented

Key checks:
- `04-security-auth.md` — should now mention CSRF validation (added in Phase 1)
- `02-logging.md` — should describe SensitiveDataProcessor
- `05-caching.md` — should match actual ConcreteCmsCache implementation
- `07-monitoring-metrics.md` — should match LogBasedMetrics and ConcreteCmsHealthCheck

- [ ] **Step 2: Update docs/testing.md**

Add sections for:
- Integration test setup (from Task 9)
- Coverage enforcement (`composer test` now enforces 80%)
- E2E test prerequisites (Playwright, local CMS)
- Storybook component test coverage

- [ ] **Step 3: Verify README.md accuracy**

Run each command listed in README.md to verify it works:
- `npm install`, `npm run development`, `npm run production`
- `composer install`, `composer test`
- `npm run storybook`

Flag any commands that fail or have changed.

- [ ] **Step 4: Update CONTRIBUTING.md**

Verify it mentions:
- The 4-stage CI pipeline
- All lint commands (`npm run lint:all`, `composer lint:php`)
- Test commands (`composer test`, `npm run test:e2e`)
- Pre-commit hooks (husky + lint-staged + detect-secrets)

- [ ] **Step 5: Update SECURITY.md**

Add mention of:
- CSRF protection (added in Phase 1)
- Security headers middleware (added in Phase 1)
- InputValidator for API endpoints (added in Phase 1)

- [ ] **Step 6: Update CLAUDE.md**

Verify and update:
- Package event table — should include all 17 events (currently lists fewer)
- Component library counts — verify after Phase 3
- Commands section — add any new commands introduced
- Testing section — update with coverage enforcement details

- [ ] **Step 7: Check for broken links**

```bash
# Find all markdown internal links and verify targets exist
grep -roh '\[.*\]([^)]*\.md[^)]*)' docs/ README.md CONTRIBUTING.md SECURITY.md CLAUDE.md | \
  sed 's/.*(\(.*\))/\1/' | sort -u | while read link; do
    if [ ! -f "$link" ]; then
      echo "BROKEN: $link"
    fi
  done
```

- [ ] **Step 8: Commit**

```bash
git add docs/ README.md CONTRIBUTING.md SECURITY.md CLAUDE.md
git diff --cached --quiet || git commit -m "docs: reconcile all documentation with current codebase"
```

---

## Phase 5: Final Verification

### Task 21: Full CI Simulation

Run the complete pipeline locally to verify everything passes.

- [ ] **Step 1: Stage 1 — Lint & Format**

Run:
```bash
npm run lint && npm run format && npm run lint:scss && composer lint:php
```
Expected: All pass

- [ ] **Step 2: Stage 2 — Type Check**

Run: `npm run typecheck`
Expected: PASS

- [ ] **Step 3: Stage 3 — Test & Coverage**

Run:
```bash
composer test
npm run tokens && npm run production
npm run storybook:build
php -S localhost:6007 storybook-server/index.php &
sleep 2
npx playwright test tests/e2e/specs/storybook/ --config=tests/e2e/playwright.config.ts --reporter=line
kill %1
```
Expected: All pass, coverage >= 80%

- [ ] **Step 4: Stage 4 — Security**

Run:
```bash
npm run audit:npm
composer audit --abandoned=ignore
```
Expected: All pass

- [ ] **Step 5: Production build**

Run: `npm run production`
Expected: Clean output, no warnings

- [ ] **Step 6: Commit any final fixes**

```bash
git add -A && git diff --cached --quiet || git commit -m "fix: final verification fixes"
```

---

### Task 22: Quality Gate Checklist

Final pass confirming every item.

- [ ] **Step 1: Run through the checklist**

Verify each item from the design spec's Phase 5.5 checklist:

- [ ] All npm/composer vulnerabilities resolved or documented
- [ ] CSRF token validation on all state-changing API endpoints
- [ ] Input validation on all user-facing API inputs
- [ ] No unescaped user content in PHP templates or JS
- [ ] Security headers configured
- [ ] Semgrep operational in CI, detect-secrets documented
- [ ] No hardcoded credentials in .env.dist
- [ ] Test coverage threshold enforced in CI at 80%
- [ ] Integration test bootstrap working with exemplary tests
- [ ] Critical unit test paths covered (event handlers)
- [ ] Storybook smoke tests cover all 63 components
- [ ] axe-core accessibility gate in CI — zero critical/serious violations
- [ ] WCAG 2.1 AA manual audit complete
- [ ] Design tokens used consistently
- [ ] All CMS block templates use component partials
- [ ] OpenAPI spec matches code
- [ ] Arc42 typst docs accurate
- [ ] All markdown docs accurate
- [ ] CLAUDE.md fully accurate
- [ ] No broken links, no orphaned docs
- [ ] Full CI pipeline passes green locally
- [ ] Production build clean
- [ ] Storybook build clean

- [ ] **Step 2: Final commit**

```bash
git add -A && git diff --cached --quiet || git commit -m "chore: complete quality gate audit"
```
