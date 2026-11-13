<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorResponse',
    description: 'Standard error response envelope',
    required: ['error'],
)]
class ErrorResponse
{
    #[OA\Property(
        property: 'error',
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'The requested resource was not found.'),
            new OA\Property(property: 'code', type: 'integer', example: 404),
        ],
        type: 'object',
        required: ['message', 'code'],
    )]
    public object $error;
}
