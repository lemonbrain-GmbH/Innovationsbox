<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate;

final class WriteStatusUpdateCommand
{
    public function __construct(
        public readonly string $spaceId,
        public readonly string $title,
        public readonly string $body,
    ) {}
}
