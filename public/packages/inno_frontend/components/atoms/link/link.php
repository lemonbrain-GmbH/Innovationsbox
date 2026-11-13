<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $text */
$text = (string) ($props['text'] ?? 'Link');
/** @var string $href */
$href = (string) ($props['href'] ?? '#');
/** @var string $target */
$target = (string) ($props['target'] ?? '_self');

$attrs = '';
if ($target === '_blank') {
    $attrs = ' target="_blank" rel="noopener noreferrer"';
}
?>
<a href="<?= htmlspecialchars($href) ?>"<?= $attrs ?>><?= htmlspecialchars($text) ?></a>
