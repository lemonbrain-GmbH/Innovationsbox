<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Exception;

class InfrastructureException extends BaseException
{
    public function getHttpStatusCode(): int
    {
        return 500;
    }

    public function getUserMessage(): string
    {
        return 'An internal error occurred. Please try again later.';
    }
}
