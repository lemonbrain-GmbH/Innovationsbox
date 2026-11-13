<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\In;

interface UpdateHeaderImagePort
{
    public function updateHeaderImage(string $spaceId, ?int $fileId): void;
}
