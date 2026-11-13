<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Exception;

class ValidationException extends BaseException
{
    public function getHttpStatusCode(): int
    {
        return 400;
    }

    public function getUserMessage(): string
    {
        return 'The provided data is invalid.';
    }
}
