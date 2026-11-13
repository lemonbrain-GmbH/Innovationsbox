<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProjectSpaceDetail',
    required: ['id', 'projectIdeaId', 'title', 'description', 'status', 'initiatorId', 'memberCount', 'createdAt'],
)]
class ProjectSpaceDetail
{
    #[OA\Property(type: 'string', example: 'space_6627a1d5e6f73')]
    public string $id;

    #[OA\Property(type: 'string', example: 'idea_6627a1b2e3f41')]
    public string $projectIdeaId;

    #[OA\Property(type: 'string', example: 'Solarenergie für Schulen')]
    public string $title;

    #[OA\Property(type: 'string', example: 'Installation von Solarpanels auf Schulgebäuden')]
    public string $description;

    #[OA\Property(type: 'string', enum: ['draft', 'active', 'completed', 'archived'], example: 'active')]
    public string $status;

    #[OA\Property(type: 'string', example: '42')]
    public string $initiatorId;

    #[OA\Property(type: 'integer', example: 5)]
    public int $memberCount;

    #[OA\Property(
        properties: [
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        ],
        type: 'object',
        nullable: true,
    )]
    public ?object $latestUpdate;

    #[OA\Property(type: 'string', format: 'date-time')]
    public string $createdAt;

    /** @var CollaboardLinkSchema[] */
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/CollaboardLink'))]
    public array $collaboardLinks;

    /** @var StatusUpdateSchema[] */
    #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/StatusUpdateSchema'))]
    public array $statusUpdates;

    /** @var list<string> */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    public array $members;
}
