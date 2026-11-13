<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling;

use Symfony\Component\HttpFoundation\JsonResponse;

interface ExceptionHandlerInterface
{
    public function toJsonResponse(\Throwable $e): JsonResponse;
}
