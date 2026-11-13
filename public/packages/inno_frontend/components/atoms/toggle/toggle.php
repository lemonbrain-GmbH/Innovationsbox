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
<label class="toggle<?= $disabled ? ' toggle--disabled' : '' ?>">
    <input
        type="checkbox"
        role="switch"
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?>"
        value="<?= htmlspecialchars($value) ?>"
        class="toggle__input"
        <?= $checked ? 'checked' : '' ?>
        <?= $disabled ? 'disabled' : '' ?>
        <?= $required ? 'required' : '' ?>
        aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
        <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
    >
    <span class="toggle__track" aria-hidden="true">
        <span class="toggle__thumb"></span>
    </span>
    <span class="toggle__label"><?= htmlspecialchars($label) ?></span>
</label>
