<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

$imageSrc = (string) ($props['imageSrc'] ?? '');
$imageAlt = (string) ($props['imageAlt'] ?? '');
$content = (string) ($props['content'] ?? '');
?>
<section class="info-panel">
    <?php if ($imageSrc !== ''): ?>
        <div class="info-panel__image">
            <img src="<?= htmlspecialchars($imageSrc) ?>" alt="<?= htmlspecialchars($imageAlt) ?>" loading="lazy">
        </div>
    <?php endif; ?>

    <div class="info-panel__content">
        <?= $content ?>
    </div>
</section>
