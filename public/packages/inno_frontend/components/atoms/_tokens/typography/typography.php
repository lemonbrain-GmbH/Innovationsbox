<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $samplesJson */
$samplesJson = (string) ($props['samples'] ?? '[]');
/** @var array<int, array{token: string, sizes: string, fontSize: string, lineHeight: string, weight?: string}> $samples */
$samples = (array) (json_decode($samplesJson, true) ?: []);
/** @var string $sampleText */
$sampleText = (string) ($props['sampleText'] ?? 'Die Innovationsbox stellt digitale Räume zur Verfügung');
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
<?php foreach ($samples as $sample): ?>
    <div style="margin: 16px 0; padding: 16px; border-bottom: 1px solid #E7E7E4;">
        <div style="font-family: ui-monospace, monospace; font-size: 11px; color: #71716C; margin-bottom: 8px;">
            <?= htmlspecialchars($sample['token']) ?> — <?= htmlspecialchars($sample['sizes']) ?>
        </div>
        <div style="font-size: <?= htmlspecialchars($sample['fontSize']) ?>; line-height: <?= htmlspecialchars($sample['lineHeight']) ?>; font-weight: <?= htmlspecialchars($sample['weight'] ?? '400') ?>;">
            <?= htmlspecialchars($sampleText) ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
