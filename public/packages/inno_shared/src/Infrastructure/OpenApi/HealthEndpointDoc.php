<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi;

use OpenApi\Attributes as OA;

/**
 * OpenAPI documentation for the /api/health endpoint.
 * The actual implementation is an inline closure in controller.php.
 */
class HealthEndpointDoc
{
    #[OA\Get(
        path: '/health',
        summary: 'System health check',
        description: 'Returns the health status of all system services (database, CMS). Disabled in production via monitoring.health_enabled config.',
        tags: ['Health'],
    )]
    #[OA\Response(
        response: 200,
        description: 'All services healthy',
        content: new OA\JsonContent(ref: '#/components/schemas/HealthResponse'),
    )]
    #[OA\Response(
        response: 503,
        description: 'One or more services unhealthy',
        content: new OA\JsonContent(ref: '#/components/schemas/HealthResponse'),
    )]
    #[OA\Response(response: 404, description: 'Health check disabled')]
    public function health(): void {}
}
