<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $label */
$label = (string) ($props['label'] ?? '');

/** @var string $id */
$id = (string) ($props['id'] ?? '');

/** @var string $name */
$name = (string) ($props['name'] ?? $id);

/** @var string $type */
$type = (string) ($props['type'] ?? 'text');

/** @var string $value */
$value = (string) ($props['value'] ?? '');

/** @var string $placeholder */
$placeholder = (string) ($props['placeholder'] ?? '');

/** @var string $hint */
$hint = (string) ($props['hint'] ?? '');

/** @var string $error */
$error = (string) ($props['error'] ?? '');

/** @var bool $required */
$required = ($props['required'] ?? 'false') === 'true';

/** @var bool $disabled */
$disabled = ($props['disabled'] ?? 'false') === 'true';

/** @var string $options */
$options = (string) ($props['options'] ?? '[]');

/** @var string $rows */
$rows = (string) ($props['rows'] ?? '4');

$hasError = $error !== '';
$hintId = $id . '-hint';
$describedBy = $hasError ? $hintId : ($hint !== '' ? $hintId : '');

$fieldClasses = ['form-field'];
if ($hasError) {
    $fieldClasses[] = 'form-field--error';
}
if ($disabled) {
    $fieldClasses[] = 'form-field--disabled';
}
?>
<div class="<?= implode(' ', $fieldClasses) ?>">
    <?php
    // Label
    $props = [
        'text' => $label,
        'for' => $id,
        'required' => $required ? 'true' : 'false',
        'disabled' => $disabled ? 'true' : 'false',
    ];
include DIR_COMPONENTS . '/atoms/label/label.php';

// Input (based on type)
$commonProps = [
    'id' => $id,
    'name' => $name,
    'value' => $value,
    'placeholder' => $placeholder,
    'disabled' => $disabled ? 'true' : 'false',
    'required' => $required ? 'true' : 'false',
    'invalid' => $hasError ? 'true' : 'false',
    'describedBy' => $describedBy,
];

if ($type === 'textarea') {
    $props = array_merge($commonProps, ['rows' => $rows, 'resize' => 'vertical']);
    include DIR_COMPONENTS . '/atoms/textarea/textarea.php';
} elseif ($type === 'select') {
    $props = array_merge($commonProps, ['options' => $options, 'size' => 'md']);
    include DIR_COMPONENTS . '/atoms/select/select.php';
} else {
    $props = array_merge($commonProps, ['type' => $type, 'size' => 'md']);
    include DIR_COMPONENTS . '/atoms/input/input.php';
}

// Hint or error message
if ($hasError) {
    $props = ['text' => $error, 'variant' => 'error'];
    echo '<span id="' . htmlspecialchars($hintId) . '" class="caption caption--error">' . htmlspecialchars($error) . '</span>';
} elseif ($hint !== '') {
    echo '<span id="' . htmlspecialchars($hintId) . '" class="caption caption--muted">' . htmlspecialchars($hint) . '</span>';
}
?>
</div>
