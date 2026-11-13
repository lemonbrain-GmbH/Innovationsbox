<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'HealthResponse', required: ['status', 'services', 'timestamp'])]
class HealthResponse
{
    #[OA\Property(type: 'string', enum: ['healthy', 'unhealthy'], example: 'healthy')]
    public string $status;

    #[OA\Property(
        type: 'object',
        additionalProperties: new OA\AdditionalProperties(type: 'string', enum: ['healthy', 'degraded', 'unhealthy']),
        example: ['database' => 'healthy', 'cms' => 'healthy'],
    )]
    public object $services;

    #[OA\Property(type: 'string', format: 'date-time', example: '2026-04-17T10:30:00+00:00')]
    public string $timestamp;
}
