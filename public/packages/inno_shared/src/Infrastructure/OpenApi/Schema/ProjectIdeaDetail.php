<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ProjectIdeaDetail', required: ['id', 'title', 'description', 'category', 'status', 'submitterId'])]
class ProjectIdeaDetail
{
    #[OA\Property(type: 'string', example: 'idea_6627a1b2e3f41')]
    public string $id;

    #[OA\Property(type: 'string', example: 'Solar panels for community building')]
    public string $title;

    #[OA\Property(type: 'string', example: 'Install solar panels on the community center roof')]
    public string $description;

    #[OA\Property(type: 'string', example: 'energy')]
    public string $category;

    #[OA\Property(type: 'string', enum: ['submitted', 'under_review', 'approved', 'rejected'])]
    public string $status;

    #[OA\Property(type: 'string', example: 'user_42')]
    public string $submitterId;
}
