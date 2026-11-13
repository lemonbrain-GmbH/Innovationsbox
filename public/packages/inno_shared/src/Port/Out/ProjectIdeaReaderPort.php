<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Port\Out;

interface ProjectIdeaReaderPort
{
    public function getSubmitterId(string $ideaId): string;
    public function getIdeaTitle(string $ideaId): string;
}
