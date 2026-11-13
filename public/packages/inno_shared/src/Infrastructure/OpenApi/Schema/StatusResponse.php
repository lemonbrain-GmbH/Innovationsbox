<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'StatusResponse', required: ['status'])]
class StatusResponse
{
    #[OA\Property(type: 'string', example: 'approved')]
    public string $status;
}
