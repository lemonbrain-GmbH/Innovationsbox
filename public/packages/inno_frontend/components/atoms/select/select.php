<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $id */
$id = (string) ($props['id'] ?? '');
/** @var string $name */
$name = (string) ($props['name'] ?? '');
/** @var string $value */
$value = (string) ($props['value'] ?? '');
/** @var string $placeholder */
$placeholder = (string) ($props['placeholder'] ?? '');
/** @var string $size */
$size = (string) ($props['size'] ?? 'md');
/** @var string $optionsJson */
$optionsJson = (string) ($props['options'] ?? '[]');
/** @var array<int, array{value: string, label: string}> $options */
$options = (array) (json_decode($optionsJson, true) ?: []);
/** @var bool $disabled */
$disabled = ($props['disabled'] ?? 'false') === 'true';
/** @var bool $required */
$required = ($props['required'] ?? 'false') === 'true';
/** @var bool $invalid */
$invalid = ($props['invalid'] ?? 'false') === 'true';
/** @var string $describedBy */
$describedBy = (string) ($props['describedBy'] ?? '');
/** @var string $ariaLabel */
$ariaLabel = (string) ($props['ariaLabel'] ?? '');
?>
<div class="select-wrapper select-wrapper--<?= htmlspecialchars($size) ?>">
    <select
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?>"
        class="select"
        <?= $disabled ? 'disabled' : '' ?>
        <?= $required ? 'required' : '' ?>
        aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
        <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
        <?= $ariaLabel ? 'aria-label="' . htmlspecialchars($ariaLabel) . '"' : '' ?>
    >
        <?php if ($placeholder): ?>
            <option value="" disabled <?= $value === '' ? 'selected' : '' ?>><?= htmlspecialchars($placeholder) ?></option>
        <?php endif; ?>
        <?php foreach ($options as $option): ?>
            <option value="<?= htmlspecialchars($option['value']) ?>" <?= $value === $option['value'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($option['label']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <span class="select__chevron" aria-hidden="true">▾</span>
</div>
