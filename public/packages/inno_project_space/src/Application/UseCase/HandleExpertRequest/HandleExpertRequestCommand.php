<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest;

final class HandleExpertRequestCommand
{
    public function __construct(
        public readonly string $spaceId,
        public readonly string $requestId,
        public readonly bool $accept,
    ) {}
}
