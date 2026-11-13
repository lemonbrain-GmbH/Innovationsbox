<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $id */
$id = (string) ($props['id'] ?? 'search');
/** @var string $name */
$name = (string) ($props['name'] ?? 'q');
/** @var string $value */
$value = (string) ($props['value'] ?? '');
/** @var string $placeholder */
$placeholder = (string) ($props['placeholder'] ?? 'Suchen…');
/** @var bool $clearable */
$clearable = ($props['clearable'] ?? 'true') === 'true';
/** @var bool $disabled */
$disabled = ($props['disabled'] ?? 'false') === 'true';
/** @var string $ariaLabel */
$ariaLabel = (string) ($props['ariaLabel'] ?? 'Suche');

$showClear = $clearable && $value !== '' && !$disabled;
?>
<div class="search-bar<?= $disabled ? ' search-bar--disabled' : '' ?>">
    <span class="search-bar__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 0 0 1.48-5.34c-.47-2.78-2.79-5-5.59-5.34a6.505 6.505 0 0 0-7.27 7.27c.34 2.8 2.56 5.12 5.34 5.59a6.5 6.5 0 0 0 5.34-1.48l.27.28v.79l4.25 4.25c.41.41 1.08.41 1.49 0 .41-.41.41-1.08 0-1.49L15.5 14zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
        </svg>
    </span>
    <input
        type="search"
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?>"
        value="<?= htmlspecialchars($value) ?>"
        class="search-bar__input"
        placeholder="<?= htmlspecialchars($placeholder) ?>"
        aria-label="<?= htmlspecialchars($ariaLabel) ?>"
        <?= $disabled ? 'disabled' : '' ?>
    >
    <?php if ($showClear): ?>
        <button type="reset" class="search-bar__clear" aria-label="Suche löschen">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    <?php endif; ?>
</div>
