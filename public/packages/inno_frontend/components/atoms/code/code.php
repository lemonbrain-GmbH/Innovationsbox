<?php

declare(strict_types=1);

/** @var array<string, mixed> $props */

/** @var string $content */
$content = (string) ($props['content'] ?? 'code');
/** @var bool $block */
$block = ($props['block'] ?? 'false') === 'true';
?>
<?php if ($block): ?>
<pre class="code-block"><code class="code"><?= htmlspecialchars($content) ?></code></pre>
<?php else: ?>
<code class="code"><?= htmlspecialchars($content) ?></code>
<?php endif; ?>
