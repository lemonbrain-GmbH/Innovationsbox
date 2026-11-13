<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Exception;

abstract class BaseException extends \RuntimeException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        private readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    abstract public function getHttpStatusCode(): int;

    abstract public function getUserMessage(): string;
}
