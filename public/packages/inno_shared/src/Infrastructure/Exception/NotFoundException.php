<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Exception;

class NotFoundException extends BaseException
{
    public function getHttpStatusCode(): int
    {
        return 404;
    }

    public function getUserMessage(): string
    {
        return 'The requested resource was not found.';
    }
}
