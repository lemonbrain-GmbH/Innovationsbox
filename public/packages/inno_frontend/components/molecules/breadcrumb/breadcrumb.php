<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $itemsJson */
$itemsJson = (string) ($props['items'] ?? '[]');

/** @var string $separator */
$separator = (string) ($props['separator'] ?? '/');

/** @var string $ariaLabel */
$ariaLabel = (string) ($props['ariaLabel'] ?? 'Brotkrumen-Navigation');

/** @var array<int, array<string, mixed>> $items */
$items = (array) (json_decode($itemsJson, true) ?: []);
$last = count($items) - 1;
?>
<nav class="breadcrumb" aria-label="<?= htmlspecialchars($ariaLabel) ?>">
    <ol class="breadcrumb__list">
        <?php foreach ($items as $i => $item):
            $isLast = $i === $last;
            ?>
            <li class="breadcrumb__item<?= $isLast ? ' breadcrumb__item--current' : '' ?>"
                <?= $isLast ? 'aria-current="page"' : '' ?>>
                <?php if (!$isLast && !empty($item['href'])): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="breadcrumb__link"><?= htmlspecialchars($item['label'] ?? '') ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars($item['label'] ?? '') ?></span>
                <?php endif; ?>
                <?php if (!$isLast): ?>
                    <span class="breadcrumb__separator" aria-hidden="true"><?= htmlspecialchars($separator) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
