<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Application\UseCase;

use Concrete\Package\InnoProjectSpace\Port\In\UpdateHeaderImagePort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;

class UpdateHeaderImageHandler implements UpdateHeaderImagePort
{
    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
    ) {}

    public function updateHeaderImage(string $spaceId, ?int $fileId): void
    {
        $space = $this->repository->findById($spaceId);
        if ($space === null) {
            throw new \RuntimeException("Project space not found: {$spaceId}");
        }
        $space->setHeaderImage($fileId);
        $this->repository->save($space);
    }
}
