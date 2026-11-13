<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $id */
$id = (string) ($props['id'] ?? 'dropdown');

/** @var string $triggerLabel */
$triggerLabel = (string) ($props['triggerLabel'] ?? 'Aktionen');

/** @var string $itemsJson */
$itemsJson = (string) ($props['items'] ?? '[]');

/** @var string $position */
$position = (string) ($props['position'] ?? 'bottom-start');

/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'ghost');

/** @var array<int, array<string, mixed>> $items */
$items = (array) (json_decode($itemsJson, true) ?: []);
$menuId = $id . '-menu';
?>
<div class="dropdown dropdown--<?= htmlspecialchars($position) ?>" data-dropdown>
    <button
        type="button"
        class="button button--<?= htmlspecialchars($variant) ?> button--md dropdown__trigger"
        aria-haspopup="menu"
        aria-expanded="false"
        aria-controls="<?= htmlspecialchars($menuId) ?>"
        data-dropdown-trigger
    >
        <span><?= htmlspecialchars($triggerLabel) ?></span>
        <span class="dropdown__chevron" aria-hidden="true">▾</span>
    </button>
    <ul id="<?= htmlspecialchars($menuId) ?>" class="dropdown__menu" role="menu" hidden>
        <?php foreach ($items as $item): ?>
            <?php if (($item['divider'] ?? 'false') === 'true'): ?>
                <li role="none"><hr class="dropdown__divider"></li>
            <?php else:
                $classes = ['dropdown__item'];
                if (($item['danger'] ?? 'false') === 'true') {
                    $classes[] = 'dropdown__item--danger';
                }
                $disabled = ($item['disabled'] ?? 'false') === 'true';
                ?>
                <li role="none">
                    <a
                        href="<?= htmlspecialchars($item['href'] ?? '#') ?>"
                        role="menuitem"
                        class="<?= implode(' ', $classes) ?>"
                        <?= $disabled ? 'aria-disabled="true" tabindex="-1"' : '' ?>
                    ><?= htmlspecialchars($item['label'] ?? '') ?></a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>
