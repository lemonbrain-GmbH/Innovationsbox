<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert;

final class RequestExpertCommand
{
    public function __construct(
        public readonly string $spaceId,
        public readonly string $expertUserId,
        public readonly ?string $message = null,
    ) {}
}
