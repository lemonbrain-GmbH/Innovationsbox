<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $id */
$id = (string) ($props['id'] ?? 'tabs');
/** @var string $tabsJson */
$tabsJson = (string) ($props['tabs'] ?? '[]');
/** @var int $activeIndex */
$activeIndex = (int) ($props['activeIndex'] ?? '0');
/** @var string $variant */
$variant = (string) ($props['variant'] ?? 'line');
/** @var string $ariaLabel */
$ariaLabel = (string) ($props['ariaLabel'] ?? 'Abschnitte');

/** @var array<int, array<string, mixed>> $tabs */
$tabs = (array) (json_decode($tabsJson, true) ?: []);
?>
<div class="tabs tabs--<?= htmlspecialchars($variant) ?>" data-tabs>
    <div class="tabs__list" role="tablist" aria-label="<?= htmlspecialchars($ariaLabel) ?>">
        <?php foreach ($tabs as $i => $tab):
            $tabId = $id . '-tab-' . $i;
            $panelId = $id . '-panel-' . $i;
            $active = $i === $activeIndex;
            $disabled = ($tab['disabled'] ?? 'false') === 'true';
            $classes = ['tabs__tab'];
            if ($active) {
                $classes[] = 'tabs__tab--active';
            }
            if ($disabled) {
                $classes[] = 'tabs__tab--disabled';
            }
            ?>
            <button
                type="button"
                role="tab"
                id="<?= htmlspecialchars($tabId) ?>"
                aria-controls="<?= htmlspecialchars($panelId) ?>"
                aria-selected="<?= $active ? 'true' : 'false' ?>"
                tabindex="<?= $active ? '0' : '-1' ?>"
                class="<?= implode(' ', $classes) ?>"
                <?= $disabled ? 'disabled' : '' ?>
            ><?= htmlspecialchars($tab['label'] ?? '') ?></button>
        <?php endforeach; ?>
    </div>
    <?php foreach ($tabs as $i => $tab):
        $tabId = $id . '-tab-' . $i;
        $panelId = $id . '-panel-' . $i;
        $active = $i === $activeIndex;
        ?>
        <div
            role="tabpanel"
            id="<?= htmlspecialchars($panelId) ?>"
            aria-labelledby="<?= htmlspecialchars($tabId) ?>"
            class="tabs__panel"
            <?= $active ? '' : 'hidden' ?>
        ><?= $tab['panelContent'] ?? '' ?></div>
    <?php endforeach; ?>
</div>
