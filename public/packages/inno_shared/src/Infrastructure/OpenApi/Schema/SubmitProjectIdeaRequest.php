<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'SubmitProjectIdeaRequest', required: ['title', 'description', 'category'])]
class SubmitProjectIdeaRequest
{
    #[OA\Property(type: 'string', example: 'Solar panels for community building')]
    public string $title;

    #[OA\Property(type: 'string', example: 'Install solar panels on the community center roof')]
    public string $description;

    #[OA\Property(type: 'string', example: 'energy')]
    public string $category;
}
