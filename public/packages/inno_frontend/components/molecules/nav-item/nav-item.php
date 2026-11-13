<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $text */
$text = (string) ($props['text'] ?? 'Navigation');
/** @var string $href */
$href = (string) ($props['href'] ?? '#');
/** @var bool $active */
$active = ($props['active'] ?? 'false') === 'true';
/** @var bool $standalone */
$standalone = ($props['standalone'] ?? 'false') === 'true';
$class = $active ? ' class="nav-selected"' : '';

if ($standalone): ?>
<ul role="list">
<?php endif; ?>
<li>
    <a href="<?= htmlspecialchars($href) ?>"<?= $class ?>><?= htmlspecialchars($text) ?></a>
</li>
<?php if ($standalone): ?>
</ul>
<?php endif; ?>
