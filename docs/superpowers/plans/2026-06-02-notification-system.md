# Notification System — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the notification package with 12 email types, branded HTML templates, and shared ports for user/space resolution.

**Architecture:** Each event gets its own handler in `inno_notification/Application/EventHandler/`. Three new read-only ports in `inno_shared` (UserResolverPort, ProjectSpaceReaderPort, ProjectIdeaReaderPort) provide user email/name lookup and space info without cross-package coupling. A shared `EmailTemplate` class wraps content in branded HTML. All handlers follow the existing pattern: constructor-injected dependencies, `handle(Event $event)` method, logging.

**Tech Stack:** PHP 8.3, Pest + Mockery, Concrete CMS Mail Service, Symfony EventDispatcher

---

## File Structure

| File | Responsibility |
|------|---------------|
| `inno_shared/src/Port/Out/UserResolverPort.php` | Interface: resolve userId → email/name |
| `inno_shared/src/Infrastructure/Adapter/User/ConcreteCmsUserResolver.php` | Implementation via `UserInfo::getByID()` |
| `inno_shared/src/Port/Out/ProjectSpaceReaderPort.php` | Interface: read space name, members, initiator |
| `inno_shared/src/Infrastructure/Adapter/Space/ConcreteProjectSpaceReader.php` | Implementation via DB queries on `ib_project_spaces` / `ib_project_space_members` |
| `inno_shared/src/Port/Out/ProjectIdeaReaderPort.php` | Interface: read idea submitter |
| `inno_shared/src/Infrastructure/Adapter/Idea/ConcreteProjectIdeaReader.php` | Implementation via DB query on `ib_project_ideas` |
| `inno_notification/src/Infrastructure/Email/EmailTemplate.php` | Branded HTML email wrapper |
| `inno_notification/src/Application/EventHandler/On*.php` | 9 new + 3 updated handlers |
| `inno_notification/controller.php` | Register 9 new listeners + bind ports |
| `inno_shared/controller.php` | Bind 3 new ports |
| `tests/unit/inno_shared/Infrastructure/Adapter/User/ConcreteCmsUserResolverTest.php` | Port test |
| `tests/unit/inno_notification/Infrastructure/Email/EmailTemplateTest.php` | Template test |
| `tests/unit/inno_notification/Application/On*Test.php` | Handler tests (9 new + 3 updated) |

---

### Task 1: UserResolverPort + Implementation

**Files:**
- Create: `public/packages/inno_shared/src/Port/Out/UserResolverPort.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/User/ConcreteCmsUserResolver.php`
- Create: `tests/unit/inno_shared/Infrastructure/Adapter/User/ConcreteCmsUserResolverTest.php`
- Modify: `public/packages/inno_shared/controller.php`

- [ ] **Step 1: Write test**

```php
<?php
// tests/unit/inno_shared/Infrastructure/Adapter/User/ConcreteCmsUserResolverTest.php
declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Adapter\User\ConcreteCmsUserResolver;

afterEach(fn() => Mockery::close());

test('resolveEmail returns email for valid user', function () {
    $userInfo = Mockery::mock(\Concrete\Core\User\UserInfo::class);
    $userInfo->shouldReceive('getUserEmail')->andReturn('test@example.com');

    $resolver = new ConcreteCmsUserResolver(function (int $id) use ($userInfo) {
        return $id === 42 ? $userInfo : null;
    });

    expect($resolver->resolveEmail('42'))->toBe('test@example.com');
});

test('resolveEmail returns null for unknown user', function () {
    $resolver = new ConcreteCmsUserResolver(fn (int $id) => null);
    expect($resolver->resolveEmail('999'))->toBeNull();
});

test('resolveName returns display_name when set', function () {
    $userInfo = Mockery::mock(\Concrete\Core\User\UserInfo::class);
    $userInfo->shouldReceive('getAttribute')->with('display_name')->andReturn('Marco Meier');
    $userInfo->shouldReceive('getUserName')->never();

    $resolver = new ConcreteCmsUserResolver(fn (int $id) => $userInfo);
    expect($resolver->resolveName('42'))->toBe('Marco Meier');
});

test('resolveName falls back to username', function () {
    $userInfo = Mockery::mock(\Concrete\Core\User\UserInfo::class);
    $userInfo->shouldReceive('getAttribute')->with('display_name')->andReturn(null);
    $userInfo->shouldReceive('getUserName')->andReturn('marco');

    $resolver = new ConcreteCmsUserResolver(fn (int $id) => $userInfo);
    expect($resolver->resolveName('42'))->toBe('marco');
});

test('resolveName returns fallback for unknown user', function () {
    $resolver = new ConcreteCmsUserResolver(fn (int $id) => null);
    expect($resolver->resolveName('999'))->toBe('Benutzer 999');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/unit/inno_shared/Infrastructure/Adapter/User/ConcreteCmsUserResolverTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create UserResolverPort interface**

```php
<?php
// public/packages/inno_shared/src/Port/Out/UserResolverPort.php
declare(strict_types=1);
namespace Concrete\Package\InnoShared\Port\Out;

interface UserResolverPort
{
    public function resolveEmail(string $userId): ?string;
    public function resolveName(string $userId): string;
}
```

- [ ] **Step 4: Create ConcreteCmsUserResolver implementation**

```php
<?php
// public/packages/inno_shared/src/Infrastructure/Adapter/User/ConcreteCmsUserResolver.php
declare(strict_types=1);
namespace Concrete\Package\InnoShared\Infrastructure\Adapter\User;

use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class ConcreteCmsUserResolver implements UserResolverPort
{
    /** @var \Closure(int): ?\Concrete\Core\User\UserInfo */
    private \Closure $userInfoLoader;

    /**
     * @param null|\Closure(int): ?\Concrete\Core\User\UserInfo $userInfoLoader
     *   Injected for testability. Defaults to UserInfo::getByID().
     */
    public function __construct(?\Closure $userInfoLoader = null)
    {
        $this->userInfoLoader = $userInfoLoader ?? static fn (int $id) => \Concrete\Core\User\UserInfo::getByID($id);
    }

    public function resolveEmail(string $userId): ?string
    {
        $ui = ($this->userInfoLoader)((int) $userId);
        return $ui?->getUserEmail() ?: null;
    }

    public function resolveName(string $userId): string
    {
        $ui = ($this->userInfoLoader)((int) $userId);
        if ($ui === null) {
            return 'Benutzer ' . $userId;
        }
        return $ui->getAttribute('display_name') ?: $ui->getUserName();
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/unit/inno_shared/Infrastructure/Adapter/User/ConcreteCmsUserResolverTest.php`
Expected: PASS (5 tests)

- [ ] **Step 6: Bind port in inno_shared controller**

In `public/packages/inno_shared/controller.php`, add inside `on_start()`:

```php
// --- User Resolution ---
$this->app->bind(
    \Concrete\Package\InnoShared\Port\Out\UserResolverPort::class,
    \Concrete\Package\InnoShared\Infrastructure\Adapter\User\ConcreteCmsUserResolver::class,
);
```

- [ ] **Step 7: Run all unit tests**

Run: `./vendor/bin/pest --testsuite=Unit`
Expected: All pass

- [ ] **Step 8: Commit**

```bash
git add public/packages/inno_shared/src/Port/Out/UserResolverPort.php \
        public/packages/inno_shared/src/Infrastructure/Adapter/User/ConcreteCmsUserResolver.php \
        tests/unit/inno_shared/Infrastructure/Adapter/User/ConcreteCmsUserResolverTest.php \
        public/packages/inno_shared/controller.php
git commit -m "feat(shared): add UserResolverPort for email/name resolution"
```

---

### Task 2: ProjectSpaceReaderPort + ProjectIdeaReaderPort

**Files:**
- Create: `public/packages/inno_shared/src/Port/Out/ProjectSpaceReaderPort.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Space/ConcreteProjectSpaceReader.php`
- Create: `public/packages/inno_shared/src/Port/Out/ProjectIdeaReaderPort.php`
- Create: `public/packages/inno_shared/src/Infrastructure/Adapter/Idea/ConcreteProjectIdeaReader.php`
- Modify: `public/packages/inno_shared/controller.php`

- [ ] **Step 1: Create ProjectSpaceReaderPort interface**

```php
<?php
// public/packages/inno_shared/src/Port/Out/ProjectSpaceReaderPort.php
declare(strict_types=1);
namespace Concrete\Package\InnoShared\Port\Out;

interface ProjectSpaceReaderPort
{
    public function getSpaceName(string $spaceId): string;
    public function getInitiatorId(string $spaceId): string;
    /** @return list<string> */
    public function getMemberUserIds(string $spaceId): array;
}
```

- [ ] **Step 2: Create ConcreteProjectSpaceReader**

```php
<?php
// public/packages/inno_shared/src/Infrastructure/Adapter/Space/ConcreteProjectSpaceReader.php
declare(strict_types=1);
namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Space;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;

class ConcreteProjectSpaceReader implements ProjectSpaceReaderPort
{
    public function __construct(private readonly Connection $db) {}

    public function getSpaceName(string $spaceId): string
    {
        $row = $this->db->fetchAssociative('SELECT title FROM ib_project_spaces WHERE id = ?', [$spaceId]);
        return $row ? (string) $row['title'] : 'Projektraum';
    }

    public function getInitiatorId(string $spaceId): string
    {
        $row = $this->db->fetchAssociative('SELECT initiator_id FROM ib_project_spaces WHERE id = ?', [$spaceId]);
        return $row ? (string) $row['initiator_id'] : '';
    }

    /** @return list<string> */
    public function getMemberUserIds(string $spaceId): array
    {
        $rows = $this->db->fetchAllAssociative(
            "SELECT user_id FROM ib_project_space_members WHERE space_id = ? AND request_status = 'accepted'",
            [$spaceId],
        );
        return array_map(fn (array $r) => (string) $r['user_id'], $rows);
    }
}
```

- [ ] **Step 3: Create ProjectIdeaReaderPort interface**

```php
<?php
// public/packages/inno_shared/src/Port/Out/ProjectIdeaReaderPort.php
declare(strict_types=1);
namespace Concrete\Package\InnoShared\Port\Out;

interface ProjectIdeaReaderPort
{
    public function getSubmitterId(string $ideaId): string;
    public function getIdeaTitle(string $ideaId): string;
}
```

- [ ] **Step 4: Create ConcreteProjectIdeaReader**

```php
<?php
// public/packages/inno_shared/src/Infrastructure/Adapter/Idea/ConcreteProjectIdeaReader.php
declare(strict_types=1);
namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Idea;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoShared\Port\Out\ProjectIdeaReaderPort;

class ConcreteProjectIdeaReader implements ProjectIdeaReaderPort
{
    public function __construct(private readonly Connection $db) {}

    public function getSubmitterId(string $ideaId): string
    {
        $row = $this->db->fetchAssociative('SELECT submitter_id FROM ib_project_ideas WHERE id = ?', [$ideaId]);
        return $row ? (string) $row['submitter_id'] : '';
    }

    public function getIdeaTitle(string $ideaId): string
    {
        $row = $this->db->fetchAssociative('SELECT title FROM ib_project_ideas WHERE id = ?', [$ideaId]);
        return $row ? (string) $row['title'] : '';
    }
}
```

- [ ] **Step 5: Bind both ports in inno_shared controller**

```php
// --- Space & Idea Readers ---
$this->app->bind(
    \Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort::class,
    \Concrete\Package\InnoShared\Infrastructure\Adapter\Space\ConcreteProjectSpaceReader::class,
);
$this->app->bind(
    \Concrete\Package\InnoShared\Port\Out\ProjectIdeaReaderPort::class,
    \Concrete\Package\InnoShared\Infrastructure\Adapter\Idea\ConcreteProjectIdeaReader::class,
);
```

- [ ] **Step 6: Run all unit tests**

Run: `./vendor/bin/pest --testsuite=Unit`
Expected: All pass

- [ ] **Step 7: Commit**

```bash
git add public/packages/inno_shared/src/Port/Out/ProjectSpaceReaderPort.php \
        public/packages/inno_shared/src/Port/Out/ProjectIdeaReaderPort.php \
        public/packages/inno_shared/src/Infrastructure/Adapter/Space/ConcreteProjectSpaceReader.php \
        public/packages/inno_shared/src/Infrastructure/Adapter/Idea/ConcreteProjectIdeaReader.php \
        public/packages/inno_shared/controller.php
git commit -m "feat(shared): add ProjectSpaceReaderPort and ProjectIdeaReaderPort"
```

---

### Task 3: Branded HTML Email Template

**Files:**
- Create: `public/packages/inno_notification/src/Infrastructure/Email/EmailTemplate.php`
- Create: `tests/unit/inno_notification/Infrastructure/Email/EmailTemplateTest.php`

- [ ] **Step 1: Write test**

```php
<?php
// tests/unit/inno_notification/Infrastructure/Email/EmailTemplateTest.php
declare(strict_types=1);

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;

test('wrap produces valid HTML with brand header', function () {
    $html = EmailTemplate::wrap('Test heading', '<p>Hello world</p>');
    expect($html)->toContain('<!DOCTYPE html');
    expect($html)->toContain('#B7CD57');
    expect($html)->toContain('Innovationsbox');
    expect($html)->toContain('Test heading');
    expect($html)->toContain('<p>Hello world</p>');
    expect($html)->toContain('automatisch');
});

test('wrap escapes heading', function () {
    $html = EmailTemplate::wrap('Test <script>alert(1)</script>', '<p>safe</p>');
    expect($html)->not->toContain('<script>');
    expect($html)->toContain('&lt;script&gt;');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/unit/inno_notification/Infrastructure/Email/EmailTemplateTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create EmailTemplate**

```php
<?php
// public/packages/inno_notification/src/Infrastructure/Email/EmailTemplate.php
declare(strict_types=1);
namespace Concrete\Package\InnoNotification\Infrastructure\Email;

class EmailTemplate
{
    /**
     * Wrap email body content in branded HTML shell.
     *
     * @param string $heading  Plain-text heading (will be escaped)
     * @param string $bodyHtml HTML content for the email body (already safe)
     */
    public static function wrap(string $heading, string $bodyHtml): string
    {
        $safeHeading = htmlspecialchars($heading, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$safeHeading}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;">
<tr><td align="center" style="padding:24px 16px;">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;">
    <!-- Header -->
    <tr><td style="background:#B7CD57;padding:20px 32px;">
      <span style="font-size:20px;font-weight:700;color:#2d2d2d;">Innovationsbox</span>
    </td></tr>
    <!-- Body -->
    <tr><td style="padding:32px;">
      <h1 style="margin:0 0 16px;font-size:22px;color:#2d2d2d;">{$safeHeading}</h1>
      <div style="font-size:15px;line-height:1.6;color:#4a4a4a;">
        {$bodyHtml}
      </div>
    </td></tr>
    <!-- Footer -->
    <tr><td style="padding:20px 32px;background:#fafafa;border-top:1px solid #e8e8e8;">
      <p style="margin:0;font-size:12px;color:#999;">
        Diese E-Mail wurde automatisch von der Innovationsbox gesendet.<br>
        Du erhältst diese Nachricht, weil du auf der Plattform registriert bist.
      </p>
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>
HTML;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/unit/inno_notification/Infrastructure/Email/EmailTemplateTest.php`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_notification/src/Infrastructure/Email/EmailTemplate.php \
        tests/unit/inno_notification/Infrastructure/Email/EmailTemplateTest.php
git commit -m "feat(notification): add branded HTML email template"
```

---

### Task 4: Fix Existing 3 Handlers (email resolution + HTML templates)

**Files:**
- Modify: `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaSubmitted.php`
- Modify: `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaApproved.php`
- Modify: `public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaRejected.php`
- Modify: `tests/unit/inno_notification/Application/OnProjectIdeaSubmittedTest.php`
- Modify: `tests/unit/inno_notification/Application/OnProjectIdeaApprovedTest.php`
- Modify: `tests/unit/inno_notification/Application/OnProjectIdeaRejectedTest.php`

- [ ] **Step 1: Update OnProjectIdeaSubmitted — add UserResolverPort + HTML**

The handler already works but sends plain text. Add `UserResolverPort` to resolve submitter name, use `EmailTemplate::wrap()`. Keep `ConfigInterface` for admin email.

```php
<?php
// public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaSubmitted.php
declare(strict_types=1);
namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaSubmitted;
use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnProjectIdeaSubmitted
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly ConfigInterface $config,
        private readonly UserResolverPort $userResolver,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectIdeaSubmitted $event): void
    {
        $adminEmail = $this->config->getRequired('notification.admin_email');
        $submitterName = $this->userResolver->resolveName($event->getSubmitterId());
        $title = htmlspecialchars($event->getTitle(), ENT_QUOTES, 'UTF-8');

        $this->emailService->send(
            to: $adminEmail,
            subject: 'Neue Projektidee eingereicht: ' . $event->getTitle(),
            body: EmailTemplate::wrap('Neue Projektidee eingereicht', <<<HTML
                <p>Eine neue Projektidee wurde eingereicht und wartet auf Moderation.</p>
                <p><strong>Titel:</strong> {$title}</p>
                <p><strong>Eingereicht von:</strong> {$submitterName}</p>
            HTML),
        );

        $this->logger->info('Admin notified about new idea', [
            'ideaId' => $event->getIdeaId(),
            'to' => $adminEmail,
        ]);
    }
}
```

- [ ] **Step 2: Update OnProjectIdeaApproved — fix email resolution + HTML**

```php
<?php
// public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaApproved.php
declare(strict_types=1);
namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaApproved;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnProjectIdeaApproved
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectIdeaApproved $event): void
    {
        $email = $this->userResolver->resolveEmail($event->getSubmitterId());
        if ($email === null) {
            $this->logger->warning('Cannot resolve submitter email for approval notification', [
                'ideaId' => $event->getIdeaId(),
                'submitterId' => $event->getSubmitterId(),
            ]);
            return;
        }

        $title = htmlspecialchars($event->getTitle(), ENT_QUOTES, 'UTF-8');

        $this->emailService->send(
            to: $email,
            subject: 'Deine Projektidee wurde freigegeben!',
            body: EmailTemplate::wrap('Deine Projektidee wurde freigegeben!', <<<HTML
                <p>Gratulation! Deine Projektidee <strong>«{$title}»</strong> wurde freigegeben.</p>
                <p>Ein Projektraum wurde für dich erstellt. Du kannst ihn jetzt einrichten und Mitglieder einladen.</p>
            HTML),
        );

        $this->logger->info('Submitter notified about approval', [
            'ideaId' => $event->getIdeaId(),
            'submitterId' => $event->getSubmitterId(),
        ]);
    }
}
```

- [ ] **Step 3: Update OnProjectIdeaRejected — fix email resolution + HTML**

```php
<?php
// public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaRejected.php
declare(strict_types=1);
namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ProjectIdeaRejected;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnProjectIdeaRejected
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ProjectIdeaRejected $event): void
    {
        $email = $this->userResolver->resolveEmail($event->getSubmitterId());
        if ($email === null) {
            $this->logger->warning('Cannot resolve submitter email for rejection notification', [
                'ideaId' => $event->getIdeaId(),
                'submitterId' => $event->getSubmitterId(),
            ]);
            return;
        }

        $reason = htmlspecialchars($event->getReason(), ENT_QUOTES, 'UTF-8');

        $this->emailService->send(
            to: $email,
            subject: 'Rückmeldung zu deiner Projektidee',
            body: EmailTemplate::wrap('Rückmeldung zu deiner Projektidee', <<<HTML
                <p>Deine Projektidee konnte leider nicht freigegeben werden.</p>
                <p><strong>Begründung:</strong> {$reason}</p>
            HTML),
        );

        $this->logger->info('Submitter notified about rejection', [
            'ideaId' => $event->getIdeaId(),
            'submitterId' => $event->getSubmitterId(),
        ]);
    }
}
```

- [ ] **Step 4: Update existing tests for new constructor signatures**

Read the 3 existing test files and update them:
- `OnProjectIdeaSubmittedTest.php`: add `UserResolverPort` mock to constructor, mock `resolveName`
- `OnProjectIdeaApprovedTest.php`: replace `ConfigInterface` with `UserResolverPort`, mock `resolveEmail` returning a real email, verify `send()` receives HTML
- `OnProjectIdeaRejectedTest.php`: same as Approved

The key changes per test:
- Add `$userResolver = Mockery::mock(UserResolverPort::class)`
- Mock `resolveEmail('user_1')->andReturn('user@example.com')` or `resolveName(...)->andReturn('Test User')`
- Verify `emailService->send()` receives an email body containing `<!DOCTYPE html`
- Add a test for the "email not found" case (resolveEmail returns null → no email sent, warning logged)

- [ ] **Step 5: Run all unit tests**

Run: `./vendor/bin/pest --testsuite=Unit`
Expected: All pass

- [ ] **Step 6: Commit**

```bash
git add public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaSubmitted.php \
        public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaApproved.php \
        public/packages/inno_notification/src/Application/EventHandler/OnProjectIdeaRejected.php \
        tests/unit/inno_notification/Application/
git commit -m "fix(notification): fix email resolution and upgrade to HTML templates"
```

---

### Task 5: New Handlers — ModerationFeedbackRequested + JoinRequest trio

**Files:**
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnModerationFeedbackRequested.php`
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnJoinRequestReceived.php`
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnJoinRequestAccepted.php`
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnJoinRequestRejected.php`
- Create: `tests/unit/inno_notification/Application/OnModerationFeedbackRequestedTest.php`
- Create: `tests/unit/inno_notification/Application/OnJoinRequestReceivedTest.php`
- Create: `tests/unit/inno_notification/Application/OnJoinRequestAcceptedTest.php`
- Create: `tests/unit/inno_notification/Application/OnJoinRequestRejectedTest.php`

All handlers follow this pattern:
1. Resolve recipient email via `UserResolverPort`
2. Look up space/idea name via reader ports
3. Build HTML body with `EmailTemplate::wrap()`
4. Send via `EmailServiceInterface`
5. Log

- [ ] **Step 1: Write tests for all 4 handlers**

Each test file follows the established Pest + Mockery pattern. For each handler, test:
- Happy path: email resolved, send called, info logged
- Email not found: resolveEmail returns null, send NOT called, warning logged

Example for `OnModerationFeedbackRequested`:
```php
<?php
// tests/unit/inno_notification/Application/OnModerationFeedbackRequestedTest.php
declare(strict_types=1);

use Concrete\Package\InnoNotification\Application\EventHandler\OnModerationFeedbackRequested;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationFeedbackRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectIdeaReaderPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

afterEach(fn() => Mockery::close());

test('handle sends feedback email to submitter', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $ideaReader = Mockery::mock(ProjectIdeaReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $ideaReader->shouldReceive('getSubmitterId')->with('idea_1')->andReturn('user_1');
    $ideaReader->shouldReceive('getIdeaTitle')->with('idea_1')->andReturn('Solar Panels');
    $userResolver->shouldReceive('resolveEmail')->with('user_1')->andReturn('user@example.com');
    $emailService->shouldReceive('send')->once()->with(
        'user@example.com',
        Mockery::on(fn($s) => str_contains($s, 'Rückmeldung')),
        Mockery::on(fn($b) => str_contains($b, 'Bitte überarbeite') && str_contains($b, '<!DOCTYPE html')),
    );
    $logger->shouldReceive('info')->once();

    $handler = new OnModerationFeedbackRequested($emailService, $userResolver, $ideaReader, $logger);
    $handler->handle(new ModerationFeedbackRequested('idea_1', 'mod_1', 'Bitte mehr Details', '2026-06-15'));
});

test('handle skips when submitter email not found', function () {
    $emailService = Mockery::mock(EmailServiceInterface::class);
    $userResolver = Mockery::mock(UserResolverPort::class);
    $ideaReader = Mockery::mock(ProjectIdeaReaderPort::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $ideaReader->shouldReceive('getSubmitterId')->with('idea_1')->andReturn('user_1');
    $ideaReader->shouldReceive('getIdeaTitle')->with('idea_1')->andReturn('Solar Panels');
    $userResolver->shouldReceive('resolveEmail')->with('user_1')->andReturn(null);
    $emailService->shouldNotReceive('send');
    $logger->shouldReceive('warning')->once();

    $handler = new OnModerationFeedbackRequested($emailService, $userResolver, $ideaReader, $logger);
    $handler->handle(new ModerationFeedbackRequested('idea_1', 'mod_1', 'Details needed', null));
});
```

For `OnJoinRequestReceived`: constructor takes `EmailServiceInterface, UserResolverPort, ProjectSpaceReaderPort, LoggerInterface`. Mock `spaceReader->getInitiatorId()->andReturn('user_3')`, `userResolver->resolveEmail('user_3')->andReturn(...)`, `spaceReader->getSpaceName()->andReturn('Solar Project')`, `userResolver->resolveName(event->getUserId())->andReturn('New User')`.

For `OnJoinRequestAccepted` and `OnJoinRequestRejected`: same deps but recipient is `event->getUserId()` (the requesting user). Mock `spaceReader->getSpaceName()`.

- [ ] **Step 2: Create all 4 handler implementations**

Each handler follows the same structure. Example for `OnModerationFeedbackRequested`:

```php
<?php
// public/packages/inno_notification/src/Application/EventHandler/OnModerationFeedbackRequested.php
declare(strict_types=1);
namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\ModerationFeedbackRequested;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectIdeaReaderPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnModerationFeedbackRequested
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly ProjectIdeaReaderPort $ideaReader,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ModerationFeedbackRequested $event): void
    {
        $submitterId = $this->ideaReader->getSubmitterId($event->getIdeaId());
        $email = $this->userResolver->resolveEmail($submitterId);
        if ($email === null) {
            $this->logger->warning('Cannot resolve submitter email for feedback notification', [
                'ideaId' => $event->getIdeaId(),
            ]);
            return;
        }

        $title = htmlspecialchars($this->ideaReader->getIdeaTitle($event->getIdeaId()), ENT_QUOTES, 'UTF-8');
        $notes = htmlspecialchars($event->getNotes(), ENT_QUOTES, 'UTF-8');
        $deadline = $event->getDeadline() ? htmlspecialchars($event->getDeadline(), ENT_QUOTES, 'UTF-8') : 'keine Frist';

        $this->emailService->send(
            to: $email,
            subject: 'Rückmeldung zu deiner Projektidee',
            body: EmailTemplate::wrap('Rückmeldung zu deiner Projektidee', <<<HTML
                <p>Das Moderationsteam hat Rückmeldungen zu deiner Projektidee <strong>«{$title}»</strong>.</p>
                <p>Bitte überarbeite sie und reiche sie erneut ein.</p>
                <p><strong>Hinweise:</strong> {$notes}</p>
                <p><strong>Frist:</strong> {$deadline}</p>
            HTML),
        );

        $this->logger->info('Submitter notified about feedback request', ['ideaId' => $event->getIdeaId()]);
    }
}
```

For `OnJoinRequestReceived`:
- Constructor: `EmailServiceInterface, UserResolverPort, ProjectSpaceReaderPort, LoggerInterface`
- Recipient: `spaceReader->getInitiatorId(event->getSpaceId())` → resolveEmail
- Body: "{userName} möchte deinem Projektraum «{spaceName}» beitreten."

For `OnJoinRequestAccepted`:
- Constructor: `EmailServiceInterface, UserResolverPort, ProjectSpaceReaderPort, LoggerInterface`
- Recipient: `event->getUserId()` → resolveEmail
- Body: "Du wurdest als Mitglied im Projektraum «{spaceName}» aufgenommen."

For `OnJoinRequestRejected`:
- Constructor: `EmailServiceInterface, UserResolverPort, ProjectSpaceReaderPort, LoggerInterface`
- Recipient: `event->getUserId()` → resolveEmail
- Body: "Deine Beitrittsanfrage für den Projektraum «{spaceName}» wurde leider abgelehnt."

- [ ] **Step 3: Run all unit tests**

Run: `./vendor/bin/pest --testsuite=Unit`
Expected: All pass

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_notification/src/Application/EventHandler/OnModerationFeedbackRequested.php \
        public/packages/inno_notification/src/Application/EventHandler/OnJoinRequestReceived.php \
        public/packages/inno_notification/src/Application/EventHandler/OnJoinRequestAccepted.php \
        public/packages/inno_notification/src/Application/EventHandler/OnJoinRequestRejected.php \
        tests/unit/inno_notification/Application/
git commit -m "feat(notification): add feedback and join request email handlers"
```

---

### Task 6: New Handlers — Member + Expert + StatusUpdate

**Files:**
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnMemberInvited.php`
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnExpertRequested.php`
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnExpertRequestAccepted.php`
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnExpertRequestDeclined.php`
- Create: `public/packages/inno_notification/src/Application/EventHandler/OnStatusUpdatePublished.php`
- Create: tests for each (5 files)

- [ ] **Step 1: Write tests for all 5 handlers**

Same pattern. Key differences:

**OnMemberInvited**: deps = `EmailServiceInterface, UserResolverPort, ProjectSpaceReaderPort, LoggerInterface`. Recipient = `event->getUserId()`. Body includes `initiatorName` (via `userResolver->resolveName(event->getInitiatorId())`).

**OnExpertRequested**: deps = `EmailServiceInterface, UserResolverPort, ProjectSpaceReaderPort, LoggerInterface`. Recipient = `event->getExpertUserId()`. Body includes requester name and optional message.

**OnExpertRequestAccepted**: deps = `EmailServiceInterface, UserResolverPort, ProjectSpaceReaderPort, LoggerInterface`. Recipient = space initiator (via `spaceReader->getInitiatorId(event->getSpaceId())`). Body includes expert name.

**OnExpertRequestDeclined**: same as Accepted but with decline message.

**OnStatusUpdatePublished**: deps = `EmailServiceInterface, UserResolverPort, ProjectSpaceReaderPort, LoggerInterface`. Recipients = ALL space members except author. Uses `spaceReader->getMemberUserIds()`, filters out `event->getAuthorId()`, resolves each email, sends one email per member.

Test for OnStatusUpdatePublished should verify:
- Sends to all members except author
- Skips members whose email can't be resolved
- Sends correct subject with space name

- [ ] **Step 2: Create all 5 handler implementations**

Each follows the established pattern. The `OnStatusUpdatePublished` handler is the most complex — it loops over member IDs:

```php
<?php
// public/packages/inno_notification/src/Application/EventHandler/OnStatusUpdatePublished.php
declare(strict_types=1);
namespace Concrete\Package\InnoNotification\Application\EventHandler;

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Domain\Event\StatusUpdatePublished;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class OnStatusUpdatePublished
{
    public function __construct(
        private readonly EmailServiceInterface $emailService,
        private readonly UserResolverPort $userResolver,
        private readonly ProjectSpaceReaderPort $spaceReader,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(StatusUpdatePublished $event): void
    {
        $spaceName = htmlspecialchars($this->spaceReader->getSpaceName($event->getSpaceId()), ENT_QUOTES, 'UTF-8');
        $authorName = htmlspecialchars($this->userResolver->resolveName($event->getAuthorId()), ENT_QUOTES, 'UTF-8');
        $memberIds = $this->spaceReader->getMemberUserIds($event->getSpaceId());
        $sent = 0;

        foreach ($memberIds as $memberId) {
            if ($memberId === $event->getAuthorId()) {
                continue;
            }

            $email = $this->userResolver->resolveEmail($memberId);
            if ($email === null) {
                continue;
            }

            $this->emailService->send(
                to: $email,
                subject: "Neues Update in «{$this->spaceReader->getSpaceName($event->getSpaceId())}»",
                body: EmailTemplate::wrap("Neues Update in «{$spaceName}»", <<<HTML
                    <p>{$authorName} hat ein neues Update im Projektraum <strong>«{$spaceName}»</strong> veröffentlicht.</p>
                    <p>Schau dir das Update an.</p>
                HTML),
            );
            $sent++;
        }

        $this->logger->info('Members notified about status update', [
            'spaceId' => $event->getSpaceId(),
            'updateId' => $event->getUpdateId(),
            'notified' => $sent,
        ]);
    }
}
```

- [ ] **Step 3: Run all unit tests**

Run: `./vendor/bin/pest --testsuite=Unit`
Expected: All pass

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_notification/src/Application/EventHandler/OnMemberInvited.php \
        public/packages/inno_notification/src/Application/EventHandler/OnExpertRequested.php \
        public/packages/inno_notification/src/Application/EventHandler/OnExpertRequestAccepted.php \
        public/packages/inno_notification/src/Application/EventHandler/OnExpertRequestDeclined.php \
        public/packages/inno_notification/src/Application/EventHandler/OnStatusUpdatePublished.php \
        tests/unit/inno_notification/Application/
git commit -m "feat(notification): add member, expert, and status update email handlers"
```

---

### Task 7: Wire All Listeners in Controller

**Files:**
- Modify: `public/packages/inno_notification/controller.php`

- [ ] **Step 1: Register all 9 new event listeners + imports**

Update `public/packages/inno_notification/controller.php`:

Add imports for all new handler classes and register listeners in `on_start()`:

```php
// Add these use statements at the top:
use Concrete\Package\InnoNotification\Application\EventHandler\OnExpertRequestAccepted;
use Concrete\Package\InnoNotification\Application\EventHandler\OnExpertRequestDeclined;
use Concrete\Package\InnoNotification\Application\EventHandler\OnExpertRequested;
use Concrete\Package\InnoNotification\Application\EventHandler\OnJoinRequestAccepted;
use Concrete\Package\InnoNotification\Application\EventHandler\OnJoinRequestReceived;
use Concrete\Package\InnoNotification\Application\EventHandler\OnJoinRequestRejected;
use Concrete\Package\InnoNotification\Application\EventHandler\OnMemberInvited;
use Concrete\Package\InnoNotification\Application\EventHandler\OnModerationFeedbackRequested;
use Concrete\Package\InnoNotification\Application\EventHandler\OnStatusUpdatePublished;

// Add in on_start() after the existing 3 listeners:

Events::addListener(
    'innovationsbox.moderation.feedback_requested',
    [$this->app->make(OnModerationFeedbackRequested::class), 'handle']
);
Events::addListener(
    'innovationsbox.project_space.join_request_received',
    [$this->app->make(OnJoinRequestReceived::class), 'handle']
);
Events::addListener(
    'innovationsbox.project_space.join_request_accepted',
    [$this->app->make(OnJoinRequestAccepted::class), 'handle']
);
Events::addListener(
    'innovationsbox.project_space.join_request_rejected',
    [$this->app->make(OnJoinRequestRejected::class), 'handle']
);
Events::addListener(
    'innovationsbox.project_space.member_invited',
    [$this->app->make(OnMemberInvited::class), 'handle']
);
Events::addListener(
    'innovationsbox.expert.requested',
    [$this->app->make(OnExpertRequested::class), 'handle']
);
Events::addListener(
    'innovationsbox.expert.request_accepted',
    [$this->app->make(OnExpertRequestAccepted::class), 'handle']
);
Events::addListener(
    'innovationsbox.expert.request_declined',
    [$this->app->make(OnExpertRequestDeclined::class), 'handle']
);
Events::addListener(
    'innovationsbox.project_space.status_update_published',
    [$this->app->make(OnStatusUpdatePublished::class), 'handle']
);
```

- [ ] **Step 2: Run all unit tests + lint**

Run: `./vendor/bin/pest --testsuite=Unit && composer lint:php && npm run typecheck`
Expected: All pass

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_notification/controller.php
git commit -m "feat(notification): wire all 12 event listeners in controller"
```
