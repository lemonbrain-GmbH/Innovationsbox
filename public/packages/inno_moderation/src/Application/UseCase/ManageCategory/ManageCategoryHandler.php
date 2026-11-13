<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Application\UseCase\ManageCategory;

use Concrete\Package\InnoModeration\Domain\Model\Category;
use Concrete\Package\InnoModeration\Port\In\ManageCategoryPort;
use Concrete\Package\InnoModeration\Port\Out\CategoryRepositoryPort;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class ManageCategoryHandler implements ManageCategoryPort
{
    public function __construct(
        private readonly CategoryRepositoryPort $repository,
        private readonly LoggerInterface $logger,
    ) {}

    public function create(CreateCategoryCommand $command): Category
    {
        $category = Category::create(
            id: uniqid('cat_', true),
            name: $command->name,
            slug: $command->slug,
            sortOrder: $command->sortOrder,
        );
        $this->repository->save($category);
        $this->logger->info('Category created', ['id' => $category->getId(), 'name' => $category->getName()]);

        return $category;
    }

    public function update(UpdateCategoryCommand $command): Category
    {
        $category = $this->repository->findById($command->id);
        if ($category === null) {
            throw new RuntimeException('Category not found');
        }

        if ($command->name !== null) {
            $category->rename($command->name, $command->slug);
        }
        if ($command->sortOrder !== null) {
            $category->reorder($command->sortOrder);
        }

        $this->repository->save($category);
        $this->logger->info('Category updated', ['id' => $category->getId()]);

        return $category;
    }

    public function delete(DeleteCategoryCommand $command): void
    {
        $category = $this->repository->findById($command->id);
        if ($category === null) {
            throw new RuntimeException('Category not found');
        }

        $count = $this->repository->countSpacesByCategoryId($command->id);
        if ($count > 0) {
            throw new RuntimeException('Cannot delete category with assigned project spaces');
        }

        $this->repository->delete($command->id);
        $this->logger->info('Category deleted', ['id' => $command->id]);
    }

    /** @return Category[] */
    public function list(): array
    {
        return $this->repository->findAll();
    }
}
