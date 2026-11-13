<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'primary');
/** @var string $sizeInput */
$sizeInput = (string) ($props['size'] ?? 'md');
$sizeMap = ['default' => 'md', 'small' => 'sm', 'large' => 'lg'];
$size = $sizeMap[$sizeInput] ?? $sizeInput;
/** @var string $label */
$label = (string) ($props['label'] ?? 'Button');
/** @var string $href */
$href = (string) ($props['href'] ?? '');
/** @var string $type */
$type = (string) ($props['type'] ?? 'button');
/** @var bool $disabled */
$disabled = ($props['disabled'] ?? 'false') === 'true';
/** @var bool $loading */
$loading = ($props['loading'] ?? 'false') === 'true';
/** @var string $iconLeft */
$iconLeft = (string) ($props['iconLeft'] ?? '');
/** @var string $iconRight */
$iconRight = (string) ($props['iconRight'] ?? '');
/** @var bool $fullWidth */
$fullWidth = ($props['fullWidth'] ?? 'false') === 'true';

$classes = ['button', 'button--' . $variant, 'button--' . $size];
if ($loading) {
    $classes[] = 'button--loading';
}
if ($fullWidth) {
    $classes[] = 'button--full-width';
}

$classAttr = implode(' ', $classes);

$inner = '';
if ($iconLeft) {
    $inner .= '<span class="button__icon button__icon--left" aria-hidden="true">' . $iconLeft . '</span>';
}
$inner .= '<span class="button__label">' . htmlspecialchars($label) . '</span>';
if ($iconRight) {
    $inner .= '<span class="button__icon button__icon--right" aria-hidden="true">' . $iconRight . '</span>';
}
if ($loading) {
    $inner .= '<span class="button__spinner" aria-hidden="true"><svg viewBox="0 0 24 24" class="spinner__svg"><circle cx="12" cy="12" r="10" class="spinner__track"/><circle cx="12" cy="12" r="10" class="spinner__arc"/></svg></span>';
}

if ($href) {
    $ariaDisabled = $disabled ? ' aria-disabled="true"' : '';
    $ariaBusy = $loading ? ' aria-busy="true"' : '';
    echo '<a href="' . htmlspecialchars($href) . '" class="' . $classAttr . '"' . $ariaDisabled . $ariaBusy . '>' . $inner . '</a>';
} else {
    $disabledAttr = $disabled || $loading ? ' disabled' : '';
    $ariaBusy = $loading ? ' aria-busy="true"' : '';
    echo '<button type="' . htmlspecialchars($type) . '" class="' . $classAttr . '"' . $disabledAttr . $ariaBusy . '>' . $inner . '</button>';
}
