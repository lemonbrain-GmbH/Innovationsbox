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
/** @var int $rows */
$rows = (int) ($props['rows'] ?? 4);
/** @var string $resize */
$resize = (string) ($props['resize'] ?? 'vertical');
/** @var bool $disabled */
$disabled = ($props['disabled'] ?? 'false') === 'true';
/** @var bool $required */
$required = ($props['required'] ?? 'false') === 'true';
/** @var bool $invalid */
$invalid = ($props['invalid'] ?? 'false') === 'true';
/** @var string $describedBy */
$describedBy = (string) ($props['describedBy'] ?? '');

$style = 'resize: ' . htmlspecialchars($resize) . ';';
?>
<textarea
    id="<?= htmlspecialchars($id) ?>"
    name="<?= htmlspecialchars($name) ?>"
    rows="<?= $rows ?>"
    class="textarea"
    style="<?= $style ?>"
    <?= $placeholder ? 'placeholder="' . htmlspecialchars($placeholder) . '"' : '' ?>
    <?= $disabled ? 'disabled' : '' ?>
    <?= $required ? 'required' : '' ?>
    aria-invalid="<?= $invalid ? 'true' : 'false' ?>"
    <?= $describedBy ? 'aria-describedby="' . htmlspecialchars($describedBy) . '"' : '' ?>
><?= htmlspecialchars($value) ?></textarea>
