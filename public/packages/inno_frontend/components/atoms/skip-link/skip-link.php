<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $text */
$text = (string) ($props['text'] ?? 'Zum Hauptinhalt springen');
/** @var string $target */
$target = (string) ($props['target'] ?? '#main-content-area');
?>
<a href="<?= htmlspecialchars($target) ?>" class="skip-link visually-hidden"><?= htmlspecialchars($text) ?></a>
