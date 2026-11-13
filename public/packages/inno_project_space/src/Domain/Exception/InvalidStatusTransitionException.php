<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Exception;

use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InvalidStatusTransitionException extends ValidationException
{
    public static function fromTo(SpaceStatus $from, SpaceStatus $to): self
    {
        return new self(
            "Cannot transition from '{$from->value}' to '{$to->value}'.",
            ['from' => $from->value, 'to' => $to->value],
        );
    }
}
