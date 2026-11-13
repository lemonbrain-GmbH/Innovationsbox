<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'elevated');

/** @var string $padding */
$padding = (string) ($props['padding'] ?? 'md');

/** @var bool $clickable */
$clickable = ($props['clickable'] ?? 'false') === 'true';

/** @var string $href */
$href = (string) ($props['href'] ?? '#');

/** @var string $title */
$title = (string) ($props['title'] ?? '');

/** @var int $titleLevel */
$titleLevel = (int) ($props['titleLevel'] ?? '3');
$titleLevel = max(2, min(4, $titleLevel));
$titleTag = 'h' . $titleLevel;

/** @var string $subtitle */
$subtitle = (string) ($props['subtitle'] ?? '');

/** @var string $body */
$body = (string) ($props['body'] ?? '');

/** @var string $footer */
$footer = (string) ($props['footer'] ?? '');

$classes = ['card', 'card--' . $variant, 'card--padding-' . $padding];
if ($clickable) {
    $classes[] = 'card--clickable';
}
$classAttr = implode(' ', $classes);

$tag = $clickable ? 'a' : 'article';
$extraAttrs = $clickable ? ' href="' . htmlspecialchars($href) . '"' : '';
?>
<<?= $tag ?> class="<?= $classAttr ?>"<?= $extraAttrs ?>>
    <?php if ($title !== '' || $subtitle !== ''): ?>
        <header class="card__header">
            <?php if ($title !== ''): ?>
                <<?= $titleTag ?> class="card__title"><?= htmlspecialchars($title) ?></<?= $titleTag ?>>
            <?php endif; ?>
            <?php if ($subtitle !== ''): ?>
                <p class="card__subtitle"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </header>
    <?php endif; ?>
    <?php if ($body !== ''): ?>
        <div class="card__body"><?= $body ?></div>
    <?php endif; ?>
    <?php if ($footer !== ''): ?>
        <footer class="card__footer"><?= $footer ?></footer>
    <?php endif; ?>
</<?= $tag ?>>
