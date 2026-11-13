<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var bool $hasAvatar */
$hasAvatar = ($props['hasAvatar'] ?? 'false') === 'true';

/** @var string $avatarUrl */
$avatarUrl = (string) ($props['avatarUrl'] ?? '');

/** @var string $userName */
$userName = (string) ($props['userName'] ?? '');

/** @var string $cropperHtml */
$cropperHtml = (string) ($props['cropperHtml'] ?? '');
?>
<div class="avatar-upload">
    <div class="avatar-upload__preview">
        <?php
        $props = [
            'src' => $hasAvatar ? $avatarUrl : '',
            'name' => $userName,
            'size' => 'xl',
            'shape' => 'circle',
        ];
include DIR_COMPONENTS . '/atoms/avatar/avatar.php';
?>
    </div>

    <div class="avatar-upload__controls">
        <?php if ($cropperHtml !== ''): ?>
            <div class="avatar-upload__cropper">
                <?= $cropperHtml ?>
            </div>
        <?php else: ?>
            <?php
    $props = [
        'label' => 'Bild hochladen',
        'variant' => 'secondary',
        'size' => 'sm',
        'type' => 'button',
    ];
            include DIR_COMPONENTS . '/atoms/button/button.php';
            ?>
        <?php endif; ?>

        <?php if ($hasAvatar): ?>
            <?php
            $props = [
                'label' => 'Bild entfernen',
                'variant' => 'danger',
                'size' => 'sm',
                'type' => 'button',
            ];
            include DIR_COMPONENTS . '/atoms/button/button.php';
            ?>
        <?php endif; ?>
    </div>
</div>
