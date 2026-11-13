<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Port\Out;

use Concrete\Package\InnoModeration\Domain\Model\Category;

interface CategoryRepositoryPort
{
    public function findById(string $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    /** @return Category[] */
    public function findAll(): array;

    public function save(Category $category): void;

    public function delete(string $id): void;

    public function countSpacesByCategoryId(string $categoryId): int;
}
