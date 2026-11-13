<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $content */
$content = (string) ($props['content'] ?? '');
?>
<div class="lb-container spaced-block content-block">
    <?= $content ?>
</div>
