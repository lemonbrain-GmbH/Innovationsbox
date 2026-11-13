<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Logger;

interface LoggerInterface extends \Psr\Log\LoggerInterface
{
    /**
     * Returns a new logger instance with the given context
     * merged into every subsequent log call.
     *
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): static;
}
