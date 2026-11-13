<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $samplesJson */
$samplesJson = (string) ($props['samples'] ?? '[]');
/** @var array<int, array{name: string, value: string, duration: string, easing: string}> $samples */
$samples = (array) (json_decode($samplesJson, true) ?: []);
?>
<style>
@keyframes motionDemo { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(200px); } }
</style>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
    <?php foreach ($samples as $sample): ?>
        <div style="margin: 16px 0; padding: 16px; border: 1px solid #E7E7E4; border-radius: 8px;">
            <div style="font-family: ui-monospace, monospace; font-size: 12px; margin-bottom: 8px;">
                <strong><?= htmlspecialchars($sample['name']) ?></strong>
                <span style="color: #71716C;"> — <?= htmlspecialchars($sample['value']) ?></span>
            </div>
            <div style="background: #F4F4F3; padding: 16px; border-radius: 4px; overflow: hidden;">
                <div style="background: #B7CD57; width: 40px; height: 40px; border-radius: 4px; animation: motionDemo <?= htmlspecialchars($sample['duration']) ?> <?= htmlspecialchars($sample['easing']) ?> infinite;"></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
