<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Space;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoShared\Port\Out\ProjectSpaceReaderPort;

class ConcreteProjectSpaceReader implements ProjectSpaceReaderPort
{
    public function __construct(private readonly Connection $db) {}

    public function getSpaceName(string $spaceId): string
    {
        $row = $this->db->fetchAssociative('SELECT title FROM ib_project_spaces WHERE id = ?', [$spaceId]);
        return $row ? (string) $row['title'] : 'Innovation';
    }

    public function getInitiatorId(string $spaceId): string
    {
        $row = $this->db->fetchAssociative('SELECT initiator_id FROM ib_project_spaces WHERE id = ?', [$spaceId]);
        return $row ? (string) $row['initiator_id'] : '';
    }

    /** @return list<string> */
    public function getMemberUserIds(string $spaceId): array
    {
        $rows = $this->db->fetchAllAssociative(
            "SELECT user_id FROM ib_project_space_members WHERE space_id = ? AND request_status = 'accepted'",
            [$spaceId],
        );
        return array_map(fn(array $r) => (string) $r['user_id'], $rows);
    }
}
