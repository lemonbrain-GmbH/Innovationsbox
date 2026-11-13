# Security and Authentication

## Overview

Authentication and authorisation are provided through `AuthInterface` and `SessionInterface`. These interfaces give domain and application code a stable API that is independent of Concrete CMS's user and group system.

In addition to the CMS-level security baseline, the platform adds three application-level security utilities in `inno_shared`: `CsrfValidationTrait` for API endpoint CSRF protection, `SecurityHeadersMiddleware` for response hardening headers, and `InputValidator` for structured API input sanitisation. DOMPurify is used on the frontend to prevent XSS when rendering user-supplied HTML fragments.

## AuthInterface

```php
namespace Concrete\Package\InnoShared\Infrastructure\Contract;

use Concrete\Package\InnoShared\Infrastructure\ValueObject\UserContext;

interface AuthInterface
{
    /**
     * Return the current user's context, or null if not authenticated.
     */
    public function getCurrentUser(): ?UserContext;

    /**
     * Return true if a user is currently authenticated.
     */
    public function isAuthenticated(): bool;

    /**
     * Return true if the current user holds the given permission key.
     */
    public function hasPermission(string $permission): bool;

    /**
     * Return true if the current user holds the given role.
     */
    public function hasRole(string $role): bool;

    /**
     * Return the current user's ID, or null if not authenticated.
     */
    public function getUserId(): ?int;
}
```

## SessionInterface

```php
namespace Concrete\Package\InnoShared\Infrastructure\Contract;

interface SessionInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    /**
     * Invalidate the current session and regenerate the session ID.
     */
    public function invalidate(): void;
}
```

## UserContext Value Object

`UserContext` carries the current user's data through the application layer. It is immutable.

```php
namespace Concrete\Package\InnoShared\Infrastructure\ValueObject;

final class UserContext
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $name,
        /** @var string[] */
        public readonly array $roles,
    ) {}

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, strict: true);
    }
}
```

## Adapter: ConcreteCmsAuth

`ConcreteCmsAuth` wraps `Concrete\Core\User\User`. It maps the German Concrete CMS group names used on this platform to the English role slugs used throughout the application code.

```php
namespace Concrete\Package\InnoShared\Infrastructure\Adapter;

use Concrete\Core\User\User;
use Concrete\Package\InnoShared\Infrastructure\Contract\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\ValueObject\UserContext;

final class ConcreteCmsAuth implements AuthInterface
{
    private const ROLE_MAP = [
        'Mitglied'       => 'member',
        'Entwickler'     => 'developer',
        'Initiator'      => 'initiator',
        'Moderator'      => 'moderator',
        'Administrator'  => 'administrator',
    ];

    public function isAuthenticated(): bool
    {
        return (new User())->isRegistered();
    }

    public function getCurrentUser(): ?UserContext
    {
        $user = new User();

        if (!$user->isRegistered()) {
            return null;
        }

        return new UserContext(
            id: (int) $user->getUserID(),
            email: $user->getUserInfoObject()->getUserEmail(),
            name: $user->getUserName(),
            roles: $this->mapRoles($user->getUserGroups()),
        );
    }

    public function getUserId(): ?int
    {
        return $this->getCurrentUser()?->id;
    }

    public function hasRole(string $role): bool
    {
        return $this->getCurrentUser()?->hasRole($role) ?? false;
    }

    public function hasPermission(string $permission): bool
    {
        return isset(PermissionMap::PERMISSION_ROLES[$permission])
            && array_intersect(
                PermissionMap::PERMISSION_ROLES[$permission],
                $this->getCurrentUser()?->roles ?? []
            ) !== [];
    }

    private function mapRoles(array $cmsGroups): array
    {
        return array_values(array_filter(
            array_map(
                fn(string $group) => self::ROLE_MAP[$group] ?? null,
                array_column($cmsGroups, 'gName')
            )
        ));
    }
}
```

## Role Mapping

| CMS group (German) | Application role |
|--------------------|-----------------|
| `Mitglied` | `member` |
| `Entwickler` | `developer` |
| `Initiator` | `initiator` |
| `Moderator` | `moderator` |
| `Administrator` | `administrator` |

Use only the application role slugs in code. Never reference German group names outside the adapter.

## Permission Map

| Permission key | Granted to roles |
|----------------|-----------------|
| `idea.submit` | `member`, `developer`, `initiator`, `administrator` |
| `idea.view` | `member`, `developer`, `initiator`, `moderator`, `administrator` |
| `idea.vote` | `member`, `developer`, `initiator`, `administrator` |
| `moderation.review` | `moderator`, `administrator` |
| `space.join` | `member`, `developer`, `initiator`, `administrator` |
| `space.manage` | `developer`, `initiator`, `administrator` |

Permissions are evaluated by `ConcreteCmsAuth::hasPermission()` against the role map above, not against Concrete CMS's native permission system. This keeps permission logic testable without a running CMS.

## Security Flow

```
HTTP Request
    |
    v
Concrete CMS middleware
  - Session resumption
  - Output escaping on CMS-rendered pages
    |
    v
Controller (Adapter/In/Http/)
  - CsrfValidationTrait::validateCsrfToken()  ← throws AuthException (403) on bad/missing token
  - SecurityHeadersMiddleware::applyHeaders()  ← adds hardening headers to response
  - InputValidator                             ← validates + sanitises request body fields
  - Injects AuthInterface
  - Calls hasPermission() or hasRole()
  - Throws AuthException on failure
    |
    v
Global exception handler (inno_shared)
  - Converts AuthException    → HTTP 401 or 403 JSON response
  - Converts ValidationException → HTTP 422 JSON response
```

## CSRF Validation on API Endpoints

All state-changing API endpoints (POST, PUT, DELETE, PATCH) must validate a CSRF token. `CsrfValidationTrait` (in `inno_shared`) provides a single `validateCsrfToken(Request $request)` method that reads the `X-CSRF-Token` header and verifies it against the Concrete CMS token service with action `'api'`. Safe methods (GET, HEAD, OPTIONS) are skipped automatically.

```php
// In any REST controller:
use Concrete\Package\InnoShared\Infrastructure\Http\CsrfValidationTrait;

final class ModerationController extends AbstractController
{
    use CsrfValidationTrait;

    public function approve(Request $request, string $id): void
    {
        $this->validateCsrfToken($request); // throws AuthException (403) on failure
        // ... business logic
    }
}
```

On the frontend, `apiFetch()` (`resources/js/api-client.ts`) is a drop-in replacement for `fetch()` that automatically reads the token from the `<meta name="csrf-token">` tag injected by the CMS and adds it as the `X-CSRF-Token` header on every non-GET request. All single-page TypeScript files use `apiFetch` instead of raw `fetch`.

## Security Headers Middleware

`SecurityHeadersMiddleware` (`inno_shared`) is applied by all API controllers before returning a response. It sets:

| Header | Value |
|--------|-------|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `SAMEORIGIN` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (HTTPS only) |

## Input Validation

`InputValidator` (`inno_shared/Infrastructure/Validation/`) wraps raw request data and provides typed accessor methods that throw `ValidationException` on constraint failure. Controllers construct it from the decoded JSON body:

```php
$validator = new InputValidator($request->toArray());
$title     = $validator->requireString('title', minLength: 5, maxLength: 200);
$category  = $validator->requireEnum('category', $allowedCategories);
$note      = $validator->optionalString('note', maxLength: 1000);
```

`ValidationException` is converted to a `422 Unprocessable Entity` JSON response by the global exception handler.

## Frontend XSS Prevention

User-supplied content that is rendered as HTML (e.g. idea descriptions, status update bodies) is sanitised with **DOMPurify** before insertion into the DOM. The utility wrapper is at `resources/js/utils/escape-html.ts`; it uses a restricted allow-list of safe formatting tags and strips all event handlers and JavaScript URLs.

## CMS-Level Security (Configured, Not Abstracted)

The following security measures are provided by Concrete CMS and are configured once at the platform level. They do not require abstraction interfaces because they apply uniformly across all routes.

| Concern | CMS mechanism | Configuration location |
|---------|--------------|----------------------|
| CSRF protection | `\Concrete\Core\Validation\CSRF\Token` — token embedded in all forms, validated on POST | CMS core, always enabled |
| Output escaping | `h()` helper and Twig auto-escaping in templates | Applied in all block templates and single pages |
| SQL injection prevention | Doctrine DBAL / `Connection` with prepared statements | Used in all `Adapter/Out/Persistence/` classes |
| Session timeout | Idle session expiry | `auth.session_timeout` via `ConfigInterface` |
| Failed login lockout | Concrete CMS login throttling | `auth.max_failed_logins` via `ConfigInterface` |
| Secure headers | `Strict-Transport-Security`, `X-Content-Type-Options`, `X-Frame-Options` | Web server / CMS security config |

## Usage

Inject `AuthInterface` in controllers. Throw `AuthException` when a check fails — do not return error arrays or redirect manually.

```php
namespace Concrete\Package\InnoModeration\Adapter\In\Http;

use Concrete\Package\InnoShared\Infrastructure\Contract\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Concrete\Package\InnoModeration\Port\In\ListPendingIdeasPort;

final class ModerationController extends AbstractController
{
    public function __construct(
        private readonly AuthInterface $auth,
        private readonly ListPendingIdeasPort $listPendingIdeas,
    ) {}

    public function index(): void
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthException(
                message: 'User is not authenticated.',
                authenticated: false,
            );
        }

        if (!$this->auth->hasPermission('moderation.review')) {
            throw new AuthException(
                message: 'User lacks moderation.review permission.',
                authenticated: true,
                context: ['user_id' => $this->auth->getUserId()],
            );
        }

        $ideas = $this->listPendingIdeas->execute();

        $this->renderJson(['ideas' => array_map(fn($i) => $i->toArray(), $ideas)]);
    }
}
```

The global exception handler (see [03-error-handling.md](03-error-handling.md)) converts `AuthException` to a 401 or 403 JSON response automatically. Controllers never format error responses themselves.
