<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $groupsJson */
$groupsJson = (string) ($props['groups'] ?? '[]');
/** @var array<int, array{name: string, description: string, tokens: array<int, array{var: string, desc: string}>}> $groups */
$groups = (array) (json_decode($groupsJson, true) ?: []);
?>
<div style="font-family: 'Inter', sans-serif; padding: 24px;">
<?php foreach ($groups as $group): ?>
    <h3 style="margin: 24px 0 8px;"><?= htmlspecialchars($group['name']) ?></h3>
    <p style="color: #52524E; margin: 0 0 12px;"><?= htmlspecialchars($group['description']) ?></p>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px;">
        <?php foreach ($group['tokens'] as $token): ?>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid #E7E7E4; border-radius: 8px;">
                <div style="width: 40px; height: 40px; border-radius: 4px; background: var(--<?= htmlspecialchars($token['var']) ?>); border: 1px solid #D1D1CC; flex-shrink: 0;"></div>
                <div style="font-family: ui-monospace, monospace; font-size: 11px; line-height: 1.4;">
                    <div style="font-weight: 700; color: #181816;"><?= htmlspecialchars($token['var']) ?></div>
                    <div style="color: #71716C; margin-top: 2px;"><?= htmlspecialchars($token['desc']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</div>
