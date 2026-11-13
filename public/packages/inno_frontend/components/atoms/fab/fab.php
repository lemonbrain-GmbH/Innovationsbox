<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

$href = (string) ($props['href'] ?? '/projektidee/einreichen');
$label = (string) ($props['label'] ?? 'Neue Projektidee einreichen');
?>
<a href="<?= htmlspecialchars($href) ?>" class="fab" aria-label="<?= htmlspecialchars($label) ?>">
    <span class="fab__circle"></span>
    <span class="fab__content">
        <svg class="fab__plus" viewBox="0 0 13.5 13.5" fill="none" aria-hidden="true">
            <path d="M12.25 6.748L1.25 6.748" stroke="#D6E290" stroke-width="2.5" stroke-linecap="round"/>
            <path d="M6.752 12.25L6.752 1.25" stroke="#D6E290" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
        <span class="fab__label" aria-hidden="true">Idee<br>einreichen</span>
    </span>
</a>
