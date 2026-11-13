<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Port\In;

use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\CreateCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\DeleteCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\UpdateCategoryCommand;
use Concrete\Package\InnoModeration\Domain\Model\Category;

interface ManageCategoryPort
{
    public function create(CreateCategoryCommand $command): Category;

    public function update(UpdateCategoryCommand $command): Category;

    public function delete(DeleteCategoryCommand $command): void;

    /** @return Category[] */
    public function list(): array;
}
