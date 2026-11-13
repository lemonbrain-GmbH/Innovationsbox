<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $type */
$type = (string) ($props['type'] ?? 'text');
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
/** @var string $autocomplete */
$autocomplete = (string) ($props['autocomplete'] ?? '');
/** @var bool $disabled */
$disabled = ($props['disabled'] ?? 'false') === 'true';
/** @var bool $required */
$required = ($props['required'] ?? 'false') === 'true';
/** @var bool $invalid */
$invalid = ($props['invalid'] ?? 'false') === 'true';
/** @var string $describedBy */
$describedBy = (string) ($props['describedBy'] ?? '');

$classes = ['input', 'input--' . $size];
?>
<input
    type="<?= htmlspecialchars($type) ?>"
    id="<?= htmlspecialchars($id) ?>"
    name="<?= htmlspecialchars($name) ?>"
    value="<?= htmlspecialchars($value) ?>"
    class="<?= implode(' ', $classes) ?>"
    <?= $placeholder ? 'placeholder="' . htmlspecialchars($placeholder) . '"' : '' ?>
    <?= $autocomplete ? 'autocomplete="' . htmlspecialchars($autocomplete) . '"' : '' ?>
    <?= $disabled ? 'disabled' : '' ?>
    <?= $required ? 'required' : '' ?>
    aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
    <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
>
