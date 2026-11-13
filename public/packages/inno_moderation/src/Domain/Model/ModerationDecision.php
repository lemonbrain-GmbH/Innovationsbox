<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Domain\Model;

enum ModerationDecision: string
{
    case Pending = 'pending';
    case FeedbackRequested = 'feedback_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
