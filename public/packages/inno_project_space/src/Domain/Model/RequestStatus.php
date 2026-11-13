<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Domain\Model;

enum RequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
