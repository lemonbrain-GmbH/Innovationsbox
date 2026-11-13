<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Domain\Model;

enum IdeaStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case FeedbackRequested = 'feedback_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
