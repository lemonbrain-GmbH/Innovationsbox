<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $text */
$text = (string) ($props['text'] ?? '');
?>
<span class="question"><?= htmlspecialchars($text) ?></span>
