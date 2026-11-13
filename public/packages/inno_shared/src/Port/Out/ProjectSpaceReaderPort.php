<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Port\Out;

interface ProjectSpaceReaderPort
{
    public function getSpaceName(string $spaceId): string;
    public function getInitiatorId(string $spaceId): string;
    /** @return list<string> */
    public function getMemberUserIds(string $spaceId): array;
}
