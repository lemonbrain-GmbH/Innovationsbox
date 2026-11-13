<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $label */
$label = (string) ($props['label'] ?? 'Passwort');
/** @var string $id */
$id = (string) ($props['id'] ?? 'password');
/** @var string $name */
$name = (string) ($props['name'] ?? $id);
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
/** @var bool $showStrength */
$showStrength = ($props['showStrength'] ?? 'false') === 'true';

$hasError = $error !== '';
$hintId = $id . '-hint';
$describedBy = $hasError ? $hintId : ($hint !== '' ? $hintId : '');

$fieldClasses = ['password-field'];
if ($hasError) {
    $fieldClasses[] = 'password-field--error';
}
if ($disabled) {
    $fieldClasses[] = 'password-field--disabled';
}
?>
<div class="<?= implode(' ', $fieldClasses) ?>" data-password-field>
    <?php
    $props = [
        'text' => $label,
        'for' => $id,
        'required' => $required ? 'true' : 'false',
        'disabled' => $disabled ? 'true' : 'false',
    ];
include DIR_COMPONENTS . '/atoms/label/label.php';
?>

    <div class="password-field__input-wrapper">
        <?php
    $props = [
        'id' => $id,
        'name' => $name,
        'type' => 'password',
        'value' => $value,
        'placeholder' => $placeholder,
        'disabled' => $disabled ? 'true' : 'false',
        'required' => $required ? 'true' : 'false',
        'invalid' => $hasError ? 'true' : 'false',
        'describedBy' => $describedBy,
        'size' => 'md',
    ];
include DIR_COMPONENTS . '/atoms/input/input.php';
?>
        <button
            type="button"
            class="password-field__toggle"
            aria-label="Passwort anzeigen"
            data-password-toggle
            <?= $disabled ? 'disabled' : '' ?>
        >
            <svg class="password-field__icon password-field__icon--show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="password-field__icon password-field__icon--hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" hidden>
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
        </button>
    </div>

    <?php if ($showStrength): ?>
        <div class="password-field__strength" data-password-strength aria-live="polite">
            <div class="password-field__strength-bar">
                <div class="password-field__strength-fill" data-password-strength-fill></div>
            </div>
            <span class="password-field__strength-label" data-password-strength-label></span>
        </div>
    <?php endif; ?>

    <?php if ($hasError): ?>
        <span id="<?= htmlspecialchars($hintId) ?>" class="caption caption--error"><?= htmlspecialchars($error) ?></span>
    <?php elseif ($hint !== ''): ?>
        <span id="<?= htmlspecialchars($hintId) ?>" class="caption caption--muted"><?= htmlspecialchars($hint) ?></span>
    <?php endif; ?>
</div>
