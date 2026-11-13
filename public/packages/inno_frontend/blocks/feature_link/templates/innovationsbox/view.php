<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Entity\File\File|null $buttonColor */
/** @var object|null $button */
/** @var bool $buttonIcon */
/** @var string|null $iconTag */

$buttonColor = $buttonColor ?? null;

if (isset($button)) {
    if ($buttonIcon && $button->getValue()) {
        $iconTag = '<span class="me-3">' . $iconTag . '</span>';
    }

    $props = [
        'label' => strip_tags((string) $button->getValue()),
        'href' => (string) ($button->getAttribute('href') ?? '#'),
        'icon' => isset($iconTag) ? $iconTag : '',
    ];
    ?>
    <div class="lb-container spaced-block">
        <?php include DIR_COMPONENTS . '/molecules/feature-button/feature-button.php'; ?>
    </div>
    <?php
}
