<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ProjectIdeaSummary', required: ['id', 'title', 'status'])]
class ProjectIdeaSummary
{
    #[OA\Property(type: 'string', example: 'idea_6627a1b2e3f41')]
    public string $id;

    #[OA\Property(type: 'string', example: 'Solar panels for community building')]
    public string $title;

    #[OA\Property(type: 'string', enum: ['submitted', 'under_review', 'approved', 'rejected'], example: 'submitted')]
    public string $status;
}
