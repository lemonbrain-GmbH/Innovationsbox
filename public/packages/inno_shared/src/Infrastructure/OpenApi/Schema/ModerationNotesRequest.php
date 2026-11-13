<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ModerationNotesRequest')]
class ModerationNotesRequest
{
    #[OA\Property(type: 'string', example: 'Looks good, approved for publication.')]
    public string $notes;
}
