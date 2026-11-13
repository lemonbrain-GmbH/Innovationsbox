<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ManageCategory;

final class CreateCategoryCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $slug = null,
        public readonly int $sortOrder = 0,
    ) {}
}
