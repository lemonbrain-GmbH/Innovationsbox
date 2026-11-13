<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'StatusUpdateSchema', required: ['id', 'title', 'body', 'authorId', 'createdAt'])]
class StatusUpdateSchema
{
    #[OA\Property(type: 'string', example: 'upd_6627a1d5e6f73')]
    public string $id;

    #[OA\Property(type: 'string', example: 'Kickoff abgeschlossen')]
    public string $title;

    #[OA\Property(type: 'string', example: '<p>Wir haben gestartet!</p>')]
    public string $body;

    #[OA\Property(type: 'string', example: '42')]
    public string $authorId;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $createdAt;
}
