<?php

declare(strict_types=1);

namespace Helpers;

defined('C5_EXECUTE') or die('Access Denied.');


/**
 *
// 1) One-off capture
$html = OutputBuffer::capture([$this, 'inc'], 'view.php', ['foo' => 'bar']);

// 2) Reusable decorated callable
$incAsString = new OutputDecoratedCallable([$this, 'inc']);
$html = $incAsString('view.php', ['foo' => 'bar']);

// 3) Capture + also keep original return value (if any)
[$out, $ret] = (function () {
    $res = OutputBuffer::captureWithReturn([$this, 'inc'], 'view.php', ['foo' => 'bar']);
    return [$res['output'], $res['return']];
})();
*
 */

/**
 * Decorator that turns any callable into one that returns its echoed output.
 */
final class OutputDecoratedCallable
{
    /**
     * @var callable(...mixed):mixed
     */
    private $cb;

    /**
     * @param callable $cb
     */
    public function __construct(callable $cb)
    {
        $this->cb = $cb;
    }

    /**
     * Invoke the underlying callable, returning the echoed output as a string.
     *
     * @param mixed ...$args Arguments forwarded to the callable.
     *
     * @return string The captured output.
     *
     * @throws \Throwable Re-throws any exception/error from the callable.
     */
    public function __invoke(mixed ...$args): string
    {
        return OutputBuffer::capture($this->cb, ...$args);
    }
}
