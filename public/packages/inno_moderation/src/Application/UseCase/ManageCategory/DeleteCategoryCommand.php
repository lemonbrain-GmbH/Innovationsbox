<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ManageCategory;

final class DeleteCategoryCommand
{
    public function __construct(
        public readonly string $id,
    ) {}
}
