<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $scaleJson */
$scaleJson = (string) ($props['scale'] ?? '[]');
/** @var array<int, array{step: string, px: string}> $scale */
$scale = (array) (json_decode($scaleJson, true) ?: []);
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
    <h3 style="margin: 0 0 16px;">Spacing Scale (4px base)</h3>
    <?php foreach ($scale as $item): ?>
        <div style="display: flex; align-items: center; gap: 16px; margin: 4px 0;">
            <div style="width: 120px; font-family: ui-monospace, monospace; font-size: 12px;">
                <strong>space-<?= htmlspecialchars($item['step']) ?></strong>
                <span style="color: #71716C;"> = <?= htmlspecialchars($item['px']) ?>px</span>
            </div>
            <div style="background: #B7CD57; height: 24px; width: <?= htmlspecialchars($item['px']) ?>px; border-radius: 4px;"></div>
        </div>
    <?php endforeach; ?>
</div>
