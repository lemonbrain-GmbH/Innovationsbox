<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ExpertRequested extends Event
{
    public const NAME = 'innovationsbox.expert.requested';

    public function __construct(
        private readonly string $spaceId,
        private readonly string $expertUserId,
        private readonly string $requestedByUserId,
        private readonly ?string $message,
    ) {}

    public function getSpaceId(): string
    {
        return $this->spaceId;
    }
    public function getExpertUserId(): string
    {
        return $this->expertUserId;
    }
    public function getRequestedByUserId(): string
    {
        return $this->requestedByUserId;
    }
    public function getMessage(): ?string
    {
        return $this->message;
    }
}
