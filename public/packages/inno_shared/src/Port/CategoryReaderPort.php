<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Port;

interface CategoryReaderPort
{
    /**
     * @return array<array{id: string, name: string, slug: string}>
     */
    public function findAllCategories(): array;
}
