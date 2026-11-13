<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Exception;

class AuthException extends BaseException
{
    private int $httpStatus;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        array $context = [],
        bool $authenticated = true,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        $this->httpStatus = $authenticated ? 403 : 401;
        parent::__construct($message, $context, $code, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatus;
    }

    public function getUserMessage(): string
    {
        return 'You do not have permission to perform this action.';
    }
}
