<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $scalesJson */
$scalesJson = (string) ($props['scales'] ?? '[]');
/** @var array<int, array{name: string, description: string, steps: array<string, string>}> $scales */
$scales = (array) (json_decode($scalesJson, true) ?: []);
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
<?php foreach ($scales as $scale): ?>
    <h3 style="margin: 24px 0 8px;"><?= htmlspecialchars($scale['name']) ?></h3>
    <p style="color: #52524E; margin: 0 0 12px;"><?= htmlspecialchars($scale['description'] ?? '') ?></p>
    <div style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 4px;">
        <?php foreach ($scale['steps'] as $step => $hex):
            $r = hexdec(substr($hex, 1, 2));
            $g = hexdec(substr($hex, 3, 2));
            $b = hexdec(substr($hex, 5, 2));
            $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
            $textColor = $lum < 0.55 ? '#fff' : '#181816';
            ?>
            <div style="background: <?= $hex ?>; color: <?= $textColor ?>; padding: 20px 12px 12px; border-radius: 8px; min-height: 90px; display: flex; flex-direction: column; justify-content: space-between; font-family: ui-monospace, monospace; font-size: 12px;">
                <span style="font-weight: 700; font-size: 14px;"><?= $step ?></span>
                <span style="opacity: 0.85;"><?= $hex ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</div>
