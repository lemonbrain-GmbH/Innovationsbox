<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;

class UserCountsQuery
{
    public function __construct(
        private readonly Connection $db,
    ) {}

    /**
     * @return array{moderationReviews: int, expertRequests: int, joinRequests: int, total: int}
     */
    public function getCounts(int $userId, bool $isModerator, bool $isExpert): array
    {
        $moderationReviews = 0;
        $expertRequests = 0;
        $joinRequests = 0;

        if ($isModerator) {
            $moderationReviews = (int) $this->db->fetchOne(
                "SELECT COUNT(*) FROM ib_moderation_reviews r
                 INNER JOIN ib_project_ideas i ON r.idea_id = i.id
                 WHERE r.decision = 'pending'"
            );
        }

        if ($isExpert) {
            $expertRequests = (int) $this->db->fetchOne(
                "SELECT COUNT(*) FROM ib_expert_requests WHERE expert_user_id = ? AND status = 'pending'",
                [(string) $userId]
            );
        }

        // Join requests: count pending requests in spaces where this user is the initiator
        $joinRequests = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM ib_project_space_members m
             INNER JOIN ib_project_spaces s ON m.space_id = s.id
             WHERE s.initiator_id = ? AND m.request_status = 'pending' AND m.role != 'initiator'",
            [$userId]
        );

        $total = $moderationReviews + $expertRequests + $joinRequests;

        return compact('moderationReviews', 'expertRequests', 'joinRequests', 'total');
    }
}
