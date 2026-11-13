<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Port\Out;

use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;

interface ProjectSpaceRepositoryInterface
{
    public function findById(string $id): ?ProjectSpace;
    public function findByProjectIdeaId(string $ideaId): ?ProjectSpace;
    /** @return ProjectSpace[] */
    public function findByMemberId(string $userId): array;
    /**
     * @return array{data: ProjectSpace[], total: int}
     */
    public function findPublic(?SpaceStatus $status, int $page, int $limit, ?string $categorySlug = null, ?string $search = null, ?string $memberId = null): array;
    public function save(ProjectSpace $space): void;
    public function delete(string $id): void;
    public function countByPriority(): int;
    /**
     * @return array<int, array{updateId: string, title: string, body: string, createdAt: string, authorId: string, spaceId: string, spaceTitle: string}>
     */
    public function findLatestUpdates(int $limit): array;
}
