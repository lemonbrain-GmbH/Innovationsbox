<?php

declare(strict_types=1);

defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Localization\Service\Date $dh */
/** @var bool $isLoggedIn */
/** @var string $keywords */
/** @var \Concrete\Core\User\UserInfo[] $users */
/** @var int $total */
/** @var bool $pagination */
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
                        $avatarPath = (string) $userAvatar->getPath();
                    }

                    $memberSince = '';
                    $dateAdded = $user->getUserDateAdded();
                    if ($dateAdded && method_exists($dh, 'formatDate')) {
                        $memberSince = (string) $dh->formatDate($dateAdded, false);
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
