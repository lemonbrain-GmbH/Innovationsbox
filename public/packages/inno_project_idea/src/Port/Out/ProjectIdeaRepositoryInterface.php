<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Port\Out;

use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;

interface ProjectIdeaRepositoryInterface
{
    public function findById(string $id): ?ProjectIdea;
    /** @return ProjectIdea[] */
    public function findAll(): array;
    /** @return ProjectIdea[] */
    public function findByStatus(IdeaStatus $status): array;
    public function save(ProjectIdea $idea): void;
    /** @return ProjectIdea[] */
    public function findBySubmitterId(string $submitterId): array;
    public function delete(string $id): void;
}
