<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $logoSrc */
$logoSrc = (string) ($props['logoSrc'] ?? '');
/** @var string $logoAlt */
$logoAlt = (string) ($props['logoAlt'] ?? '');
/** @var string $logoHref */
$logoHref = (string) ($props['logoHref'] ?? '/');
/** @var string $navItems */
$navItems = (string) ($props['navItems'] ?? '[]');
/** @var string $authState */
$authState = (string) ($props['authState'] ?? 'logged-out'); // 'logged-in' or 'logged-out'

// Auth props (used when authState === 'logged-in')
/** @var string $userName */
$userName = (string) ($props['userName'] ?? '');
/** @var string $userEmail */
$userEmail = (string) ($props['userEmail'] ?? '');
/** @var string $userInitials */
$userInitials = (string) ($props['userInitials'] ?? '');
/** @var string $avatarUrl */
$avatarUrl = (string) ($props['avatarUrl'] ?? '');
/** @var string $editProfileUrl */
$editProfileUrl = (string) ($props['editProfileUrl'] ?? '/account/edit_profile');
/** @var string $logoutUrl */
$logoutUrl = (string) ($props['logoutUrl'] ?? '/login/do_logout');
?>
<header class="page-header">
    <?php
    $props = ['text' => 'Zum Hauptinhalt springen', 'target' => '#main-content-area'];
include DIR_COMPONENTS . '/atoms/skip-link/skip-link.php';
?>

    <?php
$props = [
    'logoSrc' => $logoSrc,
    'logoAlt' => $logoAlt,
    'logoHref' => $logoHref,
    'items' => $navItems,
];
include DIR_COMPONENTS . '/organisms/navigation-drawer/navigation-drawer.php';
?>

    <div class="page-header__auth">
        <?php if ($authState === 'logged-in') : ?>
            <?php
            $props = [
                'userName' => $userName,
                'userEmail' => $userEmail,
                'userInitials' => $userInitials,
                'avatarUrl' => $avatarUrl,
                'editProfileUrl' => $editProfileUrl,
                'logoutUrl' => $logoutUrl,
            ];
            include DIR_COMPONENTS . '/molecules/user-menu/user-menu.php';
            ?>
        <?php else : ?>
            <?php
                        $props = ['label' => 'Anmelden', 'variant' => 'ghost', 'href' => '/login', 'size' => 'sm'];
            include DIR_COMPONENTS . '/atoms/button/button.php';
            ?>
            <?php
                        $props = ['label' => 'Registrieren', 'variant' => 'primary', 'href' => '/register', 'size' => 'sm'];
            include DIR_COMPONENTS . '/atoms/button/button.php';
            ?>
        <?php endif; ?>
    </div>
</header>
