<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ExpertRequestAccepted extends Event
{
    public const NAME = 'innovationsbox.expert.request_accepted';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $expertUserId,
        private readonly string $requestId,
    ) {}

    public function getSpaceId(): string
    {
        return $this->spaceId;
    }
    public function getExpertUserId(): string
    {
        return $this->expertUserId;
    }
    public function getRequestId(): string
    {
        return $this->requestId;
    }
}
