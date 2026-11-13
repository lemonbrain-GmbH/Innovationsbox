<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoModeration\Domain\Model\Category;
use Concrete\Package\InnoModeration\Port\Out\CategoryRepositoryPort;
use Concrete\Package\InnoShared\Port\CategoryReaderPort;
use DateTimeImmutable;

final class ConcreteCategoryRepository implements CategoryRepositoryPort, CategoryReaderPort
{
    public function __construct(private readonly Connection $db) {}

    public function findById(string $id): ?Category
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ib_categories WHERE id = ?', [$id]);

        return $row !== false ? $this->hydrate($row) : null;
    }

    public function findBySlug(string $slug): ?Category
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ib_categories WHERE slug = ?', [$slug]);

        return $row !== false ? $this->hydrate($row) : null;
    }

    /** @return Category[] */
    public function findAll(): array
    {
        $rows = $this->db->fetchAllAssociative('SELECT * FROM ib_categories ORDER BY sort_order ASC, name ASC');

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    /** @return array<array{id: string, name: string, slug: string}> */
    public function findAllCategories(): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT id, name, slug FROM ib_categories ORDER BY sort_order ASC, name ASC',
        );
    }

    public function save(Category $category): void
    {
        $data = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'sort_order' => $category->getSortOrder(),
            'created_at' => $category->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $category->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        $exists = $this->db->fetchOne('SELECT 1 FROM ib_categories WHERE id = ?', [$category->getId()]);
        if ($exists !== false) {
            $this->db->update('ib_categories', $data, ['id' => $category->getId()]);
        } else {
            $this->db->insert('ib_categories', $data);
        }
    }

    public function delete(string $id): void
    {
        $this->db->delete('ib_categories', ['id' => $id]);
    }

    public function countSpacesByCategoryId(string $categoryId): int
    {
        return (int) $this->db->fetchOne(
            'SELECT COUNT(*) FROM ib_project_spaces WHERE category_id = ?',
            [$categoryId],
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Category
    {
        return Category::fromPersistence(
            id: (string) $row['id'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            sortOrder: (int) $row['sort_order'],
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            updatedAt: new DateTimeImmutable((string) $row['updated_at']),
        );
    }
}
