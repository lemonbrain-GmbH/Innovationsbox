<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $text */
$text = (string) ($props['text'] ?? '');
/** @var string $partnersJson */
$partnersJson = (string) ($props['partners'] ?? '[]');

/** @var array<int, array<string, mixed>> $partners */
$partners = (array) (json_decode($partnersJson, true) ?: []);
?>
<footer>
    <div class="lb-container static-content">
        <?php if ($text): ?>
            <?php
            $props = ['variant' => 'body', 'text' => $text];
            include DIR_COMPONENTS . '/atoms/paragraph/paragraph.php';
            ?>
        <?php endif; ?>

        <?php if (!empty($partners)): ?>
            <div class="logos">
                <?php foreach ($partners as $partner): ?>
                    <?php
                    $props = [
                        'name' => $partner['name'] ?? '',
                        'href' => $partner['href'] ?? '#',
                        'imageSrc' => $partner['imageSrc'] ?? '',
                    ];
                    include DIR_COMPONENTS . '/molecules/partner-logo/partner-logo.php';
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</footer>
