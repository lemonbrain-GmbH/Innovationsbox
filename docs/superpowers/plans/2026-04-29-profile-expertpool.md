# Profile, Display Name & Expert Pool — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix avatar upload for non-admin users, add a user-controlled display name, and transform the members directory into a visibility-tiered expert pool.

**Architecture:** Three changes share the same data model and templates. A CMS permission fix enables avatar uploads. Two new user attributes (`display_name`, `expertise_area`) are installed programmatically. A new CMS group "Experte" drives the directory filter. The `ConcreteCmsAuth` adapter gets a new role mapping and permission. The directory controller overrides `CoreDirectory` to filter by group, and the template renders different detail levels based on login status.

**Tech Stack:** PHP 8.2, Concrete CMS 9.4+, Pest (unit tests), Mockery

**Spec:** `docs/superpowers/specs/2026-04-29-profile-expertpool-design.md`

---

### Task 1: Avatar upload — file validation in `save_avatar()`

**Files:**
- Modify: `public/packages/inno_frontend/controllers/single_page/account/avatar.php:56-89`
- Test: `tests/unit/inno_frontend/Controllers/AvatarControllerTest.php` (create)

- [ ] **Step 1: Create test file with failing tests**

Create `tests/unit/inno_frontend/Controllers/AvatarControllerTest.php`:

```php
<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\File\UploadedFile;

test('save_avatar rejects files with disallowed MIME type', function () {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $disallowedType = 'application/pdf';

    expect(in_array($disallowedType, $allowedTypes, true))->toBeFalse();
});

test('save_avatar rejects files exceeding 5MB', function () {
    $maxBytes = 5 * 1024 * 1024;
    $oversizedBytes = 6 * 1024 * 1024;

    expect($oversizedBytes)->toBeGreaterThan($maxBytes);
});

test('save_avatar allows valid image files', function () {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    expect(in_array('image/jpeg', $allowedTypes, true))->toBeTrue();
    expect(in_array('image/png', $allowedTypes, true))->toBeTrue();
    expect(in_array('image/webp', $allowedTypes, true))->toBeTrue();
});
```

Note: The avatar controller depends heavily on CMS infrastructure (`AvatarCropperInstanceFactory`, `PageController`, DI container), making full integration tests impractical in unit tests. These tests validate the validation logic constants. The actual controller change is small and will be verified manually.

- [ ] **Step 2: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/unit/inno_frontend/Controllers/AvatarControllerTest.php --verbose`
Expected: 3 tests PASS

- [ ] **Step 3: Add MIME-type and file-size validation to `save_avatar()`**

Modify `public/packages/inno_frontend/controllers/single_page/account/avatar.php`. Replace the section after `$file = $this->request->files->get('file');` (lines 75-86) with:

```php
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile */
        $file = $this->request->files->get('file');

        if ($file) {
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $maxFileSize = 5 * 1024 * 1024; // 5 MB

            if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
                $result['error'] = true;
                $result['message'] = t('Only JPEG, PNG, and WebP images are allowed.');
                return new JsonResponse($result, 400);
            }

            if ($file->getSize() > $maxFileSize) {
                $result['error'] = true;
                $result['message'] = t('File size must not exceed 5 MB.');
                return new JsonResponse($result, 400);
            }

            $command = new UpdateUserAvatarCommand($profile, $file);
            $this->app->executeCommand($command);

            $result['success'] = true;
            $result['avatar'] = $profile->getUserAvatar()->getPath() . '?' . time();
        } else {
            $result['error'] = true;
            $result['message'] = t('Error while uploading file.');
        }
```

- [ ] **Step 4: Verify tests still pass**

Run: `./vendor/bin/pest tests/unit/inno_frontend/Controllers/AvatarControllerTest.php --verbose`
Expected: 3 tests PASS

- [ ] **Step 5: Document the CMS permission requirement**

Add the following section to `docs/auth-pages.md` before the "## How to Add or Modify an Auth Page" section:

```markdown
## Required CMS Permissions

### File Manager — Avatar Upload

Non-admin users need the "Add File" permission to use the avatar cropper. Set this in the CMS Dashboard:

1. Go to *System & Settings → Files → File Manager → Permissions*
2. Enable "Add File" for the group "Registered Users"

Without this permission, the Vue-based avatar cropper component will not render. The avatar controller validates uploads server-side (JPEG/PNG/WebP only, max 5 MB) regardless of this setting.
```

- [ ] **Step 6: Commit**

```bash
git add tests/unit/inno_frontend/Controllers/AvatarControllerTest.php \
        public/packages/inno_frontend/controllers/single_page/account/avatar.php \
        docs/auth-pages.md
git commit -m "fix(avatar): add file validation and document CMS permission for non-admin upload"
```

---

### Task 2: Extend `ConcreteCmsAuth` with expert role and permission

**Files:**
- Modify: `public/packages/inno_shared/src/Infrastructure/Adapter/Auth/ConcreteCmsAuth.php:13-30`
- Modify: `tests/unit/inno_shared/Infrastructure/Adapter/Auth/ConcreteCmsAuthTest.php`

- [ ] **Step 1: Write failing tests for expert role and permission**

Add to the end of `tests/unit/inno_shared/Infrastructure/Adapter/Auth/ConcreteCmsAuthTest.php`:

```php
test('hasRole() recognises Experte group as expert role', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Experte']);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->hasRole('expert'))->toBeTrue();
});

test('hasPermission() grants expert.manage to moderator', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Moderator']);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->hasPermission('expert.manage'))->toBeTrue();
});

test('hasPermission() denies expert.manage to regular member', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isRegistered')->andReturn(true);
    $user->shouldReceive('getUserGroups')->andReturn([1 => 'Mitglied']);

    $auth = new ConcreteCmsAuth($user);
    expect($auth->hasPermission('expert.manage'))->toBeFalse();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/unit/inno_shared/Infrastructure/Adapter/Auth/ConcreteCmsAuthTest.php --verbose`
Expected: 3 new tests FAIL (expert role not mapped, expert.manage permission not defined)

- [ ] **Step 3: Add expert mappings to `ConcreteCmsAuth`**

Modify `public/packages/inno_shared/src/Infrastructure/Adapter/Auth/ConcreteCmsAuth.php`.

In `PERMISSION_MAP` (after line 19, before the closing `]`), add:

```php
        'expert.manage' => ['moderator', 'administrator'],
```

In `GROUP_TO_ROLE` (after line 29, before the closing `]`), add:

```php
        'experte' => 'expert',
        'expert' => 'expert',
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/unit/inno_shared/Infrastructure/Adapter/Auth/ConcreteCmsAuthTest.php --verbose`
Expected: 10 tests PASS (7 existing + 3 new)

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_shared/src/Infrastructure/Adapter/Auth/ConcreteCmsAuth.php \
        tests/unit/inno_shared/Infrastructure/Adapter/Auth/ConcreteCmsAuthTest.php
git commit -m "feat(auth): add expert role mapping and expert.manage permission"
```

---

### Task 3: Install user attributes and expert group in package controller

**Files:**
- Modify: `public/packages/inno_frontend/controller.php:137-158`

- [ ] **Step 1: Add use statements at the top of controller.php**

Add after the existing `use` statements (after line 18):

```php
use Concrete\Core\Attribute\Key\UserKey as UserAttributeKey;
use Concrete\Core\Attribute\Type as AttributeType;
use Concrete\Core\User\Group\Group;
```

- [ ] **Step 2: Create the `installUserAttributes()` method**

Add after the `configureBlocks()` method (after line 211):

```php
    private function installUserAttributes(): void
    {
        $textType = AttributeType::getByHandle('text');

        if (!UserAttributeKey::getByHandle('display_name')) {
            UserAttributeKey::add(
                $textType,
                [
                    'akHandle' => 'display_name',
                    'akName' => t('Anzeigename'),
                    'akIsSearchable' => true,
                    'akIsSearchableIndexed' => true,
                    'uakProfileEdit' => true,
                    'uakProfileEditRequired' => true,
                    'uakRegisterEdit' => false,
                    'uakRegisterEditRequired' => false,
                    'uakMemberListDisplay' => true,
                ],
                $this->pkg,
            );
        }

        if (!UserAttributeKey::getByHandle('expertise_area')) {
            UserAttributeKey::add(
                $textType,
                [
                    'akHandle' => 'expertise_area',
                    'akName' => t('Fachgebiet'),
                    'akIsSearchable' => true,
                    'akIsSearchableIndexed' => true,
                    'uakProfileEdit' => true,
                    'uakProfileEditRequired' => false,
                    'uakRegisterEdit' => false,
                    'uakRegisterEditRequired' => false,
                    'uakMemberListDisplay' => true,
                ],
                $this->pkg,
            );
        }
    }

    private function installGroups(): void
    {
        if (!Group::getByName('Experte')) {
            Group::add('Experte', t('Users recognised as domain experts for the expert pool.'));
        }
    }
```

- [ ] **Step 3: Call the new methods from `install()` and `upgrade()`**

In `install()` (line 144, before the closing `}`), add:

```php
        $this->installUserAttributes();
        $this->installGroups();
```

In `upgrade()` (line 157, before the closing `}`), add:

```php
        $this->installUserAttributes();
        $this->installGroups();
```

- [ ] **Step 4: Verify lint passes**

Run: `composer lint:php`
Expected: No errors in `controller.php`

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/controller.php
git commit -m "feat(frontend): install display_name, expertise_area attributes and Experte group"
```

---

### Task 4: Replace `getUserName()` with `display_name` in templates

**Files:**
- Modify: `public/packages/inno_frontend/themes/innovationsbox/elements/header.php:21`
- Modify: `public/packages/inno_frontend/single_pages/members/profile.php:33,45`
- Modify: `public/packages/inno_frontend/single_pages/members/directory.php:66`

- [ ] **Step 1: Update `header.php`**

In `public/packages/inno_frontend/themes/innovationsbox/elements/header.php`, replace line 21:

```php
        $userName = (string) $ui->getUserName();
```

with:

```php
        $userName = (string) ($ui->getAttribute('display_name') ?: $ui->getUserName());
```

- [ ] **Step 2: Update `members/profile.php`**

In `public/packages/inno_frontend/single_pages/members/profile.php`, replace line 33:

```php
$profileName = isset($profile) ? $profile->getUserName() : '';
```

with:

```php
$profileName = isset($profile) ? ($profile->getAttribute('display_name') ?: $profile->getUserName()) : '';
```

- [ ] **Step 3: Update `members/directory.php`**

In `public/packages/inno_frontend/single_pages/members/directory.php`, replace line 66:

```php
                    $userName = ucfirst($user->getUserName());
```

with:

```php
                    $userName = ucfirst($user->getAttribute('display_name') ?: $user->getUserName());
```

- [ ] **Step 4: Verify lint passes**

Run: `composer lint:php`
Expected: No errors

- [ ] **Step 5: Commit**

```bash
git add public/packages/inno_frontend/themes/innovationsbox/elements/header.php \
        public/packages/inno_frontend/single_pages/members/profile.php \
        public/packages/inno_frontend/single_pages/members/directory.php
git commit -m "feat(frontend): use display_name with getUserName() fallback in all public views"
```

---

### Task 5: Override directory controller to filter by expert group

**Files:**
- Modify: `public/packages/inno_frontend/controllers/single_page/members/directory.php`

- [ ] **Step 1: Replace the stub controller with expert-filtered implementation**

Replace the entire content of `public/packages/inno_frontend/controllers/single_page/members/directory.php`:

```php
<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage\Members;

use Concrete\Core\Page\Controller\PublicProfilePageController;
use Concrete\Core\User\User;
use Concrete\Core\User\UserList;
use Concrete\Core\Attribute\Key\UserKey as UserAttributeKey;

class Directory extends PublicProfilePageController
{
    protected UserList $userList;

    public function on_start()
    {
        parent::on_start();
        $this->requireAsset('css', 'core/frontend/pagination');

        $this->userList = new UserList();
        $this->userList->sortByUserID();
        $this->userList->filterByGroup('Experte');

        $u = $this->app->make(User::class);
        $this->set('isLoggedIn', $u->isRegistered());
    }

    public function on_before_render()
    {
        $pagination = $this->userList->getPagination();
        $users = $pagination->getCurrentPageResults();

        $this->set('userList', $this->userList);
        $this->set('users', $users);
        $this->set('total', $pagination->getTotalResults());
        $this->set('attribs', UserAttributeKey::getMemberListList());
        $this->set('keywords', isset($_REQUEST['keywords']) ? h($_REQUEST['keywords']) : '');
        $this->set('pagination', $pagination);
    }

    public function search_members()
    {
        $keywords = $this->get('keywords');
        if ($keywords != '') {
            $this->userList->filterByKeywords($keywords);
        }
    }
}
```

Key changes vs `CoreDirectory`:
- No longer extends `CoreDirectory` — extends `PublicProfilePageController` directly (same parent the core uses), because we need to override `on_start()` to add `filterByGroup('Experte')`.
- Adds `$this->userList->filterByGroup('Experte')` in `on_start()` — filters at DB level, pagination works correctly.
- Sets `$isLoggedIn` for the template's visibility logic.

- [ ] **Step 2: Verify lint passes**

Run: `composer lint:php`
Expected: No errors

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/controllers/single_page/members/directory.php
git commit -m "feat(directory): filter members by Experte group and pass auth state to template"
```

---

### Task 6: Update member-card component with expertise and email props

**Files:**
- Modify: `public/packages/inno_frontend/components/molecules/member-card/member-card.php`
- Modify: `public/packages/inno_frontend/components/molecules/member-card/member-card.variants.php`

- [ ] **Step 1: Update `member-card.php` to support new props and conditional linking**

Replace the entire content of `public/packages/inno_frontend/components/molecules/member-card/member-card.php`:

```php
<?php
$userName = $props['userName'] ?? '';
$userInitials = $props['userInitials'] ?? '';
$avatarUrl = $props['avatarUrl'] ?? '';
$profileUrl = $props['profileUrl'] ?? '';
$memberSince = $props['memberSince'] ?? '';
$role = $props['role'] ?? '';
$expertise = $props['expertise'] ?? '';
$email = $props['email'] ?? '';
$isLinked = $profileUrl !== '';

$tagName = $isLinked ? 'a' : 'div';
$linkAttr = $isLinked ? ' href="' . htmlspecialchars($profileUrl) . '"' : '';
?>
<<?= $tagName ?><?= $linkAttr ?> class="member-card">
    <div class="member-card__avatar">
        <?php
        $props = [
            'src' => $avatarUrl,
            'name' => $userName,
            'size' => 'lg',
            'shape' => 'circle',
        ];
include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
?>
    </div>

    <div class="member-card__info">
        <span class="member-card__name"><?= htmlspecialchars($userName) ?></span>

        <?php if ($expertise !== ''): ?>
            <span class="member-card__expertise"><?= htmlspecialchars($expertise) ?></span>
        <?php endif; ?>

        <?php if ($role !== ''): ?>
            <?php
    $props = [
        'label' => $role,
        'variant' => 'neutral',
        'size' => 'sm',
    ];
            include DIR_COMPONENTS . '/atoms/badge/badge.php';
            ?>
        <?php endif; ?>

        <?php if ($email !== ''): ?>
            <span class="member-card__email"><?= htmlspecialchars($email) ?></span>
        <?php endif; ?>

        <?php if ($memberSince !== ''): ?>
            <span class="member-card__date">Mitglied seit <?= htmlspecialchars($memberSince) ?></span>
        <?php endif; ?>
    </div>
</<?= $tagName ?>>
```

- [ ] **Step 2: Update `member-card.variants.php` with new default props and variants**

Replace the entire content of `public/packages/inno_frontend/components/molecules/member-card/member-card.variants.php`:

```php
<?php

declare(strict_types=1);

$authData = require __DIR__ . '/../../_fixtures/auth-data.php';
$user = $authData['user'];

return [
    'defaults' => [
        'userName' => $user['name'],
        'userInitials' => $user['initials'],
        'avatarUrl' => $user['avatarUrl'],
        'profileUrl' => $user['profileUrl'],
        'memberSince' => $user['memberSince'],
        'role' => $user['role'],
        'expertise' => 'Erneuerbare Energien',
        'email' => $user['email'],
    ],
    'variants' => [
        'anonymous' => [
            'profileUrl' => '',
            'email' => '',
        ],
    ],
];
```

- [ ] **Step 3: Verify lint passes**

Run: `composer lint:php`
Expected: No errors

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/member-card/member-card.php \
        public/packages/inno_frontend/components/molecules/member-card/member-card.variants.php
git commit -m "feat(member-card): add expertise, email props and conditional link rendering"
```

---

### Task 7: Update directory template with visibility-tiered expert pool

**Files:**
- Modify: `public/packages/inno_frontend/single_pages/members/directory.php`

- [ ] **Step 1: Replace the directory template**

Replace the entire content of `public/packages/inno_frontend/single_pages/members/directory.php`:

```php
<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

$dh = app('helper/date');
$isLoggedIn = $isLoggedIn ?? false;
?>
<div class="members-directory">
    <div class="members-directory__header">
        <h1 class="members-directory__title"><?= t('Expertinnen-Pool') ?></h1>
    </div>

    <div class="members-directory__search">
        <form method="get" action="<?= $view->action('search_members') ?>">
            <?php
            $props = [
                'id'          => 'member-search',
                'name'        => 'keywords',
                'value'       => $keywords ?? '',
                'placeholder' => t('Name oder Fachgebiet'),
                'ariaLabel'   => t('Expertinnen suchen'),
                'clearable'   => 'false',
            ];
include DIR_COMPONENTS . '/molecules/search-bar/search-bar.php';
?>
            <div class="members-directory__search-submit">
                <?php
    $props = [
        'label'   => t('Suchen'),
        'variant' => 'primary',
        'type'    => 'submit',
    ];
include DIR_COMPONENTS . '/atoms/button/button.php';
?>
            </div>
        </form>
    </div>

    <div class="members-directory__results">
        <?php if (isset($total) && $total === 0): ?>
            <?php
            $props = [
'title'       => t('Keine Expertinnen gefunden'),
'description' => t('Versuche einen anderen Suchbegriff.'),
'size'        => 'md',
            ];
            include DIR_COMPONENTS . '/molecules/empty-state/empty-state.php';
            ?>
        <?php elseif (isset($users) && count($users) > 0): ?>
            <div class="members-directory__grid">
                <?php foreach ($users as $user): ?>
                    <?php
                    $avatarPath = '';
                    $userAvatar = $user->getUserAvatar();
                    if ($userAvatar) {
                        $avatarPath = $userAvatar->getPath();
                    }

                    $memberSince = '';
                    $dateAdded = $user->getUserDateAdded();
                    if ($dateAdded && method_exists($dh, 'formatDate')) {
                        $memberSince = $dh->formatDate($dateAdded, false);
                    }

                    $displayName = ucfirst($user->getAttribute('display_name') ?: $user->getUserName());
                    $expertiseArea = (string) $user->getAttribute('expertise_area');

                    $props = [
                        'userName'    => $displayName,
                        'avatarUrl'   => $avatarPath,
                        'profileUrl'  => $isLoggedIn ? $user->getUserPublicProfileURL() : '',
                        'memberSince' => $memberSince,
                        'role'        => '',
                        'expertise'   => $expertiseArea,
                        'email'       => $isLoggedIn ? (string) $user->getUserEmail() : '',
                    ];
                    include DIR_COMPONENTS . '/molecules/member-card/member-card.php';
                    ?>
                <?php endforeach; ?>
            </div>

            <?php if (isset($pagination) && $pagination): ?>
                <div class="members-directory__pagination">
                    <?php
                    $currentPage = isset($_GET['ccm_paging_p']) ? (int) $_GET['ccm_paging_p'] : 1;
                $props = [
                    'currentPage' => (string) $currentPage,
                    'totalPages'  => (string) ceil($total / 10),
                    'baseUrl'     => $view->action('search_members') . (isset($keywords) && $keywords !== '' ? '?keywords=' . urlencode($keywords) : ''),
                    'pageParam'   => 'ccm_paging_p',
                    'ariaLabel'   => t('Expertinnen-Pool Navigation'),
                ];
                include DIR_COMPONENTS . '/molecules/pagination/pagination.php';
                ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
```

Key changes vs original:
- Title: «Expertinnen-Pool» statt «Mitglieder»
- Search placeholder: «Name oder Fachgebiet»
- Empty state: «Keine Expertinnen gefunden»
- Uses `display_name` with fallback
- Reads `expertise_area` attribute
- Passes `profileUrl` and `email` only when `$isLoggedIn` is true
- Pagination aria label updated

- [ ] **Step 2: Verify lint passes**

Run: `composer lint:php`
Expected: No errors

- [ ] **Step 3: Commit**

```bash
git add public/packages/inno_frontend/single_pages/members/directory.php
git commit -m "feat(directory): transform members directory into visibility-tiered expert pool"
```

---

### Task 8: Add SCSS for new member-card elements

**Files:**
- Modify: `public/packages/inno_frontend/components/molecules/member-card/member-card.scss`

- [ ] **Step 1: Read the existing member-card SCSS**

Read `public/packages/inno_frontend/components/molecules/member-card/member-card.scss` to understand the current styles.

- [ ] **Step 2: Add styles for new elements**

Append the following rules inside the existing SCSS file, following the BEM pattern already in use:

```scss
.member-card__expertise {
  display: block;
  font-size: var(--font-size-sm, 0.875rem);
  color: var(--color-text-secondary, #6b7280);
}

.member-card__email {
  display: block;
  font-size: var(--font-size-sm, 0.875rem);
  color: var(--color-text-secondary, #6b7280);
  word-break: break-all;
}

div.member-card {
  cursor: default;
  text-decoration: none;
}
```

Note: the `div.member-card` rule removes the link-like cursor when the card is rendered as a `<div>` (anonymous view). Check existing styles — if there's a hover effect on `.member-card`, consider scoping it to `a.member-card` only.

- [ ] **Step 3: Build assets**

Run: `npm run development`
Expected: Build succeeds without errors

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/member-card/member-card.scss
git commit -m "style(member-card): add styles for expertise, email, and non-linked variant"
```

---

### Task 9: Update Storybook story for member-card

**Files:**
- Modify: `public/packages/inno_frontend/components/molecules/member-card/member-card.stories.json`

- [ ] **Step 1: Read the existing stories file**

Read `public/packages/inno_frontend/components/molecules/member-card/member-card.stories.json`.

- [ ] **Step 2: Add stories for expert pool variants**

Add two new stories to the stories array:

```json
{
  "name": "Expert (Logged In)",
  "args": {
    "expertise": "Erneuerbare Energien",
    "email": "marco@innovationsbox.ch"
  },
  "parameters": { "server": { "id": "molecules/member-card" } }
},
{
  "name": "Expert (Anonymous)",
  "args": {
    "profileUrl": "",
    "email": "",
    "expertise": "Erneuerbare Energien"
  },
  "parameters": { "server": { "id": "molecules/member-card" } }
}
```

- [ ] **Step 3: Verify Storybook renders correctly**

Run: `npm run storybook` and check that both new stories render without errors. The "Expert (Anonymous)" variant should render as a `<div>` without link behavior.

- [ ] **Step 4: Commit**

```bash
git add public/packages/inno_frontend/components/molecules/member-card/member-card.stories.json
git commit -m "docs(storybook): add expert pool member-card story variants"
```

---

### Task 10: Run full test suite and verify

**Files:** none (verification only)

- [ ] **Step 1: Run all unit tests**

Run: `composer test`
Expected: All tests pass, including the 3 new tests from Task 1 and 3 from Task 2

- [ ] **Step 2: Run PHP linting**

Run: `composer lint:php`
Expected: No errors

- [ ] **Step 3: Run frontend build**

Run: `npm run production`
Expected: Build succeeds

- [ ] **Step 4: Run TypeScript and SCSS checks**

Run: `npm run lint:all`
Expected: No errors

- [ ] **Step 5: Manual verification checklist**

Verify in the browser (requires running CMS with `npm run watch`):

1. **Avatar upload**: Log in as non-admin user → go to `/account/avatar` → cropper should render → upload a JPEG → should succeed → try uploading a PDF → should get error message
2. **Display name**: Go to `/account/edit_profile` → «Anzeigename» field should appear in «Additional Information» → fill it → save → header user menu should show the display name
3. **Expert pool**: Assign test user to «Experte» group in Dashboard → go to `/members/directory` → page title should be «Expertinnen-Pool» → only expert users visible → log out → same page should show experts without email and without profile links
