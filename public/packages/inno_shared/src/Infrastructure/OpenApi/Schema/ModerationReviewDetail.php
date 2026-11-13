<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ModerationReviewDetail', required: ['id', 'ideaId', 'decision', 'createdAt'])]
class ModerationReviewDetail
{
    #[OA\Property(type: 'string', example: 'review_6627a1c4d5e62')]
    public string $id;

    #[OA\Property(type: 'string', example: 'idea_6627a1b2e3f41')]
    public string $ideaId;

    #[OA\Property(type: 'string', enum: ['pending', 'approved', 'rejected', 'feedback_requested'], example: 'pending')]
    public string $decision;

    #[OA\Property(type: 'string', nullable: true, example: 'user_42')]
    public ?string $reviewerId;

    #[OA\Property(type: 'string', nullable: true, example: 'Looks good, needs minor revision')]
    public ?string $notes;

    #[OA\Property(type: 'string', format: 'date-time', example: '2024-06-10T12:00:00+00:00')]
    public string $createdAt;

    #[OA\Property(type: 'string', format: 'date-time', nullable: true, example: '2024-06-11T09:30:00+00:00')]
    public ?string $reviewedAt;

    #[OA\Property(
        type: 'object',
        nullable: true,
        properties: [
            new OA\Property(property: 'title', type: 'string', example: 'Solar panels for community building'),
            new OA\Property(property: 'description', type: 'string', example: 'Install solar panels on the community center roof'),
            new OA\Property(property: 'category', type: 'string', example: 'energy'),
            new OA\Property(property: 'categoryName', type: 'string', example: 'Energy & Environment'),
            new OA\Property(property: 'submitterId', type: 'string', example: 'user_42'),
            new OA\Property(property: 'submitterName', type: 'string', example: 'max.muster'),
            new OA\Property(property: 'status', type: 'string', enum: ['submitted', 'under_review', 'approved', 'rejected'], example: 'under_review'),
            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2024-06-09T08:00:00+00:00'),
            new OA\Property(
                property: 'attachments',
                type: 'array',
                items: new OA\Items(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: 'att_123'),
                        new OA\Property(property: 'filename', type: 'string', example: 'proposal.pdf'),
                        new OA\Property(property: 'mimeType', type: 'string', example: 'application/pdf'),
                        new OA\Property(property: 'size', type: 'integer', example: 204800),
                        new OA\Property(property: 'isImage', type: 'boolean', example: false),
                        new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://example.com/files/proposal.pdf'),
                    ],
                ),
            ),
        ],
    )]
    public ?array $idea;
}
