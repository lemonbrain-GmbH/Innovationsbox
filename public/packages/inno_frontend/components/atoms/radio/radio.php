<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $id */
$id = (string) ($props['id'] ?? '');
/** @var string $name */
$name = (string) ($props['name'] ?? '');
/** @var string $value */
$value = (string) ($props['value'] ?? '');
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
<label class="radio<?= $disabled ? ' radio--disabled' : '' ?>">
    <input
        type="radio"
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?>"
        value="<?= htmlspecialchars($value) ?>"
        class="radio__input"
        <?= $checked ? 'checked' : '' ?>
        <?= $disabled ? 'disabled' : '' ?>
        <?= $required ? 'required' : '' ?>
        aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
        <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
    >
    <span class="radio__circle" aria-hidden="true">
        <span class="radio__dot"></span>
    </span>
    <span class="radio__label"><?= htmlspecialchars($label) ?></span>
</label>
