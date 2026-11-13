<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $samplesJson */
$samplesJson = (string) ($props['samples'] ?? '[]');
/** @var array<int, array{name: string, shadow: string}> $samples */
$samples = (array) (json_decode($samplesJson, true) ?: []);
?>
<div style="font-family: 'Inter', sans-serif; padding: 48px; background: #FAFAF9; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 32px;">
    <?php foreach ($samples as $sample): ?>
        <div style="text-align: center;">
            <div style="background: white; width: 140px; height: 140px; margin: 0 auto; border-radius: 10px; box-shadow: <?= htmlspecialchars($sample['shadow']) ?>;"></div>
            <div style="font-family: ui-monospace, monospace; font-size: 12px; margin-top: 12px;">
                <strong>shadow-<?= htmlspecialchars($sample['name']) ?></strong>
            </div>
        </div>
    <?php endforeach; ?>
</div>
