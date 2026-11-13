<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ManageCategory;

final class UpdateCategoryCommand
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $name = null,
        public readonly ?string $slug = null,
        public readonly ?int $sortOrder = null,
    ) {}
}
