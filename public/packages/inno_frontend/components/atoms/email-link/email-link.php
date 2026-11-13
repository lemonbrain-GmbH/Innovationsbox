<?php

declare(strict_types=1);

use Concrete\Core\Support\Facade\Application as App;

/** @var array<string, mixed> $props */

/** @var string $email */
$email = (string) ($props['email'] ?? '');
/** @var string $label */
$label = (string) ($props['label'] ?? '');
/** @var string $classes */
$classes = (string) ($props['classes'] ?? '');

if ($email === '') {
    return;
}

$displayLabel = $label !== '' ? $label : $email;
$cssClass = 'email-link' . ($classes !== '' ? ' ' . $classes : '');
?>
<span class="<?= htmlspecialchars($cssClass) ?>">
    <?php
    $app = class_exists(App::class) ? App::getFacadeApplication() : null;
if ($app !== null && $app->bound(\Helpers\Links\EmailAddressObfuscated::class)): ?>
        <?= $app->make(\Helpers\Links\EmailAddressObfuscated::class)->transformToLink(
            $email,
            $displayLabel,
            $displayLabel,
            'email-link__anchor',
        ) ?>
    <?php else: ?>
        <a href="mailto:<?= htmlspecialchars($email) ?>" class="email-link__anchor"><?= htmlspecialchars($displayLabel) ?></a>
    <?php endif; ?>
</span>
