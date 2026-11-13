<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CollaboardLink', required: ['id', 'title', 'url', 'addedAt'])]
class CollaboardLinkSchema
{
    #[OA\Property(type: 'string', example: 'link_6627a1d5e6f73')]
    public string $id;

    #[OA\Property(type: 'string', example: 'Brainstorming Board')]
    public string $title;

    #[OA\Property(type: 'string', format: 'uri', example: 'https://web.collaboard.app/share/abc123')]
    public string $url;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $addedAt;
}
