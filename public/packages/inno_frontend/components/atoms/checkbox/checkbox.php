<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $id */
$id = (string) ($props['id'] ?? '');
/** @var string $name */
$name = (string) ($props['name'] ?? '');
/** @var string $value */
$value = (string) ($props['value'] ?? 'yes');
/** @var string $label */
$label = (string) ($props['label'] ?? 'Option');
/** @var bool $checked */
$checked = ($props['checked'] ?? 'false') === 'true';
/** @var bool $disabled */
$disabled = ($props['disabled'] ?? 'false') === 'true';
/** @var bool $required */
$required = ($props['required'] ?? 'false') === 'true';
/** @var bool $invalid */
$invalid = ($props['invalid'] ?? 'false') === 'true';
/** @var string $describedBy */
$describedBy = (string) ($props['describedBy'] ?? '');
?>
<label class="checkbox<?= $disabled ? ' checkbox--disabled' : '' ?>">
    <input
        type="checkbox"
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?>"
        value="<?= htmlspecialchars($value) ?>"
        class="checkbox__input"
        <?= $checked ? 'checked' : '' ?>
        <?= $disabled ? 'disabled' : '' ?>
        <?= $required ? 'required' : '' ?>
        aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
        <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
    >
    <span class="checkbox__box" aria-hidden="true">
        <svg viewBox="0 0 16 16" class="checkbox__check" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 8l3 3 7-7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <span class="checkbox__label"><?= htmlspecialchars($label) ?></span>
</label>
