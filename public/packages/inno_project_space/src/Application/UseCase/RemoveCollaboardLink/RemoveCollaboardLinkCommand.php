<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveCollaboardLink;

final class RemoveCollaboardLinkCommand
{
    public function __construct(
        public readonly string $spaceId,
        public readonly string $linkId,
    ) {}
}
