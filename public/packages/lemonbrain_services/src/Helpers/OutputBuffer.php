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
 * Generic output buffering utilities.
 */
final class OutputBuffer
{
    /**
     * Run a callable and capture ONLY what it echoes/prints.
     *
     * @template TArgs of array
     *
     * @param callable $cb   Callable to invoke.
     * @param mixed ...$args                 Arguments forwarded to the callable.
     *
     * @return string                        The captured output (empty string if nothing was printed).
     *
     * @throws \Throwable                    Re-throws any exception/error from the callable.
     */
    public static function capture(callable $cb, mixed ...$args): string
    {
        $level = ob_get_level();
        ob_start();
        try {
            $cb(...$args);
            /** @var string */
            $out = (string) ob_get_clean();
            return $out;
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
    }

    /**
     * Run a callable and capture BOTH its echoed output and its return value.
     *
     * @template TReturn
     * @param callable $cb  Callable to invoke.
     * @param mixed ...$args                  Arguments forwarded to the callable.
     *
     * @return array{output:string, return:TReturn}
     *
     * @throws \Throwable                     Re-throws any exception/error from the callable.
     */
    public static function captureWithReturn(callable $cb, mixed ...$args): array
    {
        $level = ob_get_level();
        ob_start();
        try {
            /** @var TReturn $return */
            $return = $cb(...$args);
            /** @var string */
            $output = (string) ob_get_clean();
            return ['output' => $output, 'return' => $return];
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
