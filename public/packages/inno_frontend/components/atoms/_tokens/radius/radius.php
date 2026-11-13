<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $samplesJson */
$samplesJson = (string) ($props['samples'] ?? '[]');
/** @var array<int, array{name: string, px: string}> $samples */
$samples = (array) (json_decode($samplesJson, true) ?: []);
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px; display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px;">
    <?php foreach ($samples as $sample): ?>
        <div style="text-align: center;">
            <div style="background: #B7CD57; width: 120px; height: 120px; margin: 0 auto; border-radius: <?= htmlspecialchars($sample['px']) ?>;"></div>
            <div style="font-family: ui-monospace, monospace; font-size: 12px; margin-top: 8px;">
                <strong>radius-<?= htmlspecialchars($sample['name']) ?></strong><br>
                <span style="color: #71716C;"><?= htmlspecialchars($sample['px']) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>
