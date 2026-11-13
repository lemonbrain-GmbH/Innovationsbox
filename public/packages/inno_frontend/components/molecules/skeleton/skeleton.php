<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'text');
/** @var string $width */
$width = (string) ($props['width'] ?? '');
/** @var string $height */
$height = (string) ($props['height'] ?? '');
/** @var int $count */
$count = max(1, (int) ($props['count'] ?? 1));

// Default dimensions per variant
$defaults = [
    'text' => ['width' => '100%', 'height' => '1em'],
    'heading' => ['width' => '60%', 'height' => '1.5em'],
    'rectangle' => ['width' => '100%', 'height' => '120px'],
    'circle' => ['width' => '40px', 'height' => '40px'],
    'image' => ['width' => '100%', 'height' => '200px'],
];
$d = $defaults[$variant] ?? $defaults['text'];
$w = $width !== '' ? $width : $d['width'];
$h = $height !== '' ? $height : $d['height'];

// For text count > 1, vary widths for natural look
$widths = ['100%', '95%', '88%', '92%', '70%'];
?>
<?php if ($variant === 'text' && $count > 1): ?>
    <div class="skeleton-group" aria-hidden="true">
        <?php for ($i = 0; $i < $count; $i++):
            $lineWidth = $widths[$i % count($widths)];
            ?>
            <span class="skeleton skeleton--<?= htmlspecialchars($variant) ?>" style="width: <?= htmlspecialchars($lineWidth) ?>; height: <?= htmlspecialchars($h) ?>;"></span>
        <?php endfor; ?>
    </div>
<?php else: ?>
    <span class="skeleton skeleton--<?= htmlspecialchars($variant) ?>" style="width: <?= htmlspecialchars($w) ?>; height: <?= htmlspecialchars($h) ?>;" aria-hidden="true"></span>
<?php endif; ?>
