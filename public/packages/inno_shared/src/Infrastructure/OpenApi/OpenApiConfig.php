<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '0.1.0',
    title: 'Innovationsbox API',
    description: 'REST API for the Innovationsbox platform — project ideas, moderation, project spaces, notifications.',
)]
#[OA\Server(url: '/api', description: 'API base path')]
#[OA\SecurityScheme(
    securityScheme: 'cookieAuth',
    type: 'apiKey',
    in: 'cookie',
    name: 'CONCRETE5',
    description: 'Concrete CMS session cookie. Login via the CMS web interface to obtain a session.',
)]
#[OA\Tag(name: 'Project Ideas', description: 'Submit and browse project ideas')]
#[OA\Tag(name: 'Moderation', description: 'Review queue for submitted ideas')]
#[OA\Tag(name: 'Project Spaces', description: 'Collaboration spaces for approved ideas')]
#[OA\Tag(name: 'Notifications', description: 'User notifications')]
#[OA\Tag(name: 'Health', description: 'System health checks')]
class OpenApiConfig {}
