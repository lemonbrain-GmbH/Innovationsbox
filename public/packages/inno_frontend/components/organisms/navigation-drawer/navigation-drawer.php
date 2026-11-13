<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $logoSrc */
$logoSrc = (string) ($props['logoSrc'] ?? '');
/** @var string $logoAlt */
$logoAlt = (string) ($props['logoAlt'] ?? '');
/** @var string $logoHref */
$logoHref = (string) ($props['logoHref'] ?? '/');
/** @var string $itemsJson */
$itemsJson = (string) ($props['items'] ?? '[]');

/** @var array<int, array<string, mixed>> $items */
$items = (array) (json_decode($itemsJson, true) ?: []);
?>
<div class="nav-container">
    <?php if ($logoSrc): ?>
        <div class="main-logo">
            <a href="<?= htmlspecialchars($logoHref) ?>" aria-label="<?= htmlspecialchars($logoAlt) ?>">
                <img src="<?= htmlspecialchars($logoSrc) ?>" alt="<?= htmlspecialchars($logoAlt) ?>">
            </a>
        </div>
    <?php endif; ?>

    <button class="nav-toggle" aria-controls="main-nav" aria-expanded="false">
        <span class="sr-only"><?= t('Menü öffnen') ?></span>
        <svg width="34" height="25" viewBox="0 0 34 25" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path class="nav-toggle__top" d="M 2 3 L 32 3"/>
            <path class="nav-toggle__mid" d="M 2 12.5 L 32 12.5"/>
            <path class="nav-toggle__bot" d="M 2 22 L 32 22"/>
        </svg>
    </button>

    <nav class="nav-drawer" id="main-nav" aria-hidden="true">
        <div class="nav-drawer__top">
            <button class="nav-close" tabindex="-1">
                <span class="sr-only"><?= t('Menü schliessen') ?></span>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path class="nav-close__top" d="M 6 6 L 18 18"/>
                    <path class="nav-close__bot" d="M 18 6 L 6 18"/>
                </svg>
            </button>
            <div class="nav-drawer__auth" data-nav-auth></div>
        </div>

        <ul>
            <?php
            $prevLevel = 1;
foreach ($items as $i => $item):
    $level = (int) ($item['level'] ?? 1);
    $text = $item['text'] ?? '';
    $href = $item['href'] ?? '#';
    $target = $item['target'] ?? '_self';
    $active = ($item['active'] ?? 'false') === 'true';
    $hasSubmenu = ($item['hasSubmenu'] ?? false);
    $subDepth = (int) ($item['subDepth'] ?? 0);
    $class = $active ? ' class="nav-selected"' : '';

    // Open nested <ul> if going deeper
    if ($level > $prevLevel) {
        echo '<ul>';
    }
    // Close nested levels if going shallower
    if ($level < $prevLevel) {
        echo str_repeat('</ul></li>', $prevLevel - $level);
    }
    ?>
                <li<?= $class ?>><a href="<?= htmlspecialchars($href) ?>" target="<?= htmlspecialchars($target) ?>" tabindex="-1"<?= $class ?>><?= $text ?></a><?php
        if ($hasSubmenu) {
            // Don't close <li> — nested <ul> will follow
        } else {
            echo '</li>';
            if ($subDepth > 0) {
                echo str_repeat('</ul></li>', $subDepth);
                $level -= $subDepth;
            }
        }
    $prevLevel = $level;
endforeach;
?>
        </ul>
    </nav>
    <div class="nav-overlay"></div>
</div>
