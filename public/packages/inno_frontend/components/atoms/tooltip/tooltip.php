<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $content */
$content = (string) ($props['content'] ?? '');
/** @var string $position */
$position = (string) ($props['position'] ?? 'top');
/** @var string $trigger */
$trigger = (string) ($props['trigger'] ?? 'Hover me');
/** @var string $id */
$id = (string) ($props['id'] ?? 'tooltip-' . uniqid());
?>
<span class="tooltip tooltip--<?= htmlspecialchars($position) ?>">
    <span class="tooltip__trigger" tabindex="0" aria-describedby="<?= htmlspecialchars($id) ?>"><?= $trigger ?></span>
    <span class="tooltip__bubble" role="tooltip" id="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($content) ?></span>
</span>
