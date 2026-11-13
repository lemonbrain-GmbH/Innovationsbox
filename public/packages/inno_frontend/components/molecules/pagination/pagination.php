<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var int $current */
$current = (int) ($props['currentPage'] ?? '1');
/** @var int $total */
$total = (int) ($props['totalPages'] ?? '1');
/** @var string $baseUrl */
$baseUrl = (string) ($props['baseUrl'] ?? '');
/** @var string $pageParam */
$pageParam = (string) ($props['pageParam'] ?? 'page');
/** @var int $siblings */
$siblings = (int) ($props['siblingCount'] ?? '1');
/** @var string $ariaLabel */
$ariaLabel = (string) ($props['ariaLabel'] ?? 'Seitennavigation');

if ($total <= 1) {
    return;
}

$buildHref = function (int $page) use ($baseUrl, $pageParam): string {
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    return $baseUrl . $sep . $pageParam . '=' . $page;
};

$pages = [];
$left = max(2, $current - $siblings);
$right = min($total - 1, $current + $siblings);
$pages[] = 1;
if ($left > 2) {
    $pages[] = '...';
}
for ($i = $left; $i <= $right; $i++) {
    $pages[] = $i;
}
if ($right < $total - 1) {
    $pages[] = '...';
}
if ($total > 1) {
    $pages[] = $total;
}

$hasPrev = $current > 1;
$hasNext = $current < $total;
?>
<nav class="pagination" aria-label="<?= htmlspecialchars($ariaLabel) ?>">
    <ul class="pagination__list">
        <li>
            <?php if ($hasPrev): ?>
                <a href="<?= htmlspecialchars($buildHref($current - 1)) ?>" class="pagination__link pagination__link--prev" aria-label="Vorherige Seite" rel="prev">‹</a>
            <?php else: ?>
                <span class="pagination__link pagination__link--prev pagination__link--disabled" aria-hidden="true">‹</span>
            <?php endif; ?>
        </li>
        <?php foreach ($pages as $p): ?>
            <li>
                <?php if ($p === '...'): ?>
                    <span class="pagination__ellipsis" aria-hidden="true">…</span>
                <?php elseif ($p === $current): ?>
                    <a href="<?= htmlspecialchars($buildHref($p)) ?>" class="pagination__link pagination__link--current" aria-current="page"><?= $p ?></a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($buildHref($p)) ?>" class="pagination__link"><?= $p ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <li>
            <?php if ($hasNext): ?>
                <a href="<?= htmlspecialchars($buildHref($current + 1)) ?>" class="pagination__link pagination__link--next" aria-label="Nächste Seite" rel="next">›</a>
            <?php else: ?>
                <span class="pagination__link pagination__link--next pagination__link--disabled" aria-hidden="true">›</span>
            <?php endif; ?>
        </li>
    </ul>
</nav>
