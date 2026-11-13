<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ModerationReviewSummary', required: ['id', 'ideaId'])]
class ModerationReviewSummary
{
    #[OA\Property(type: 'string', example: 'review_6627a1c4d5e62')]
    public string $id;

    #[OA\Property(type: 'string', example: 'idea_6627a1b2e3f41')]
    public string $ideaId;
}
