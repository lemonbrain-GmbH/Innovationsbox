<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectSpace\Domain\Model\CollaboardLink;
use Concrete\Package\InnoProjectSpace\Domain\Model\ExpertRequest;
use Concrete\Package\InnoProjectSpace\Domain\Model\ExpertRequestStatus;
use Concrete\Package\InnoProjectSpace\Domain\Model\MembershipRequest;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\RequestStatus;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
use Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdate;
use Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdateAttachment;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;

class ConcreteProjectSpaceRepository implements ProjectSpaceRepositoryInterface
{
    public function __construct(private readonly Connection $db) {}

    public function findById(string $id): ?ProjectSpace
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ib_project_spaces WHERE id = ?', [$id]);
        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByProjectIdeaId(string $ideaId): ?ProjectSpace
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ib_project_spaces WHERE project_idea_id = ?', [$ideaId]);
        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByMemberId(string $userId): array
    {
        $spaceIds = $this->db->fetchFirstColumn(
            'SELECT space_id FROM ib_project_space_members WHERE user_id = ? AND request_status = ?',
            [(int) $userId, RequestStatus::Accepted->value],
        );

        return array_map(fn(string $id) => $this->findById($id), $spaceIds);
    }

    public function findPublic(?SpaceStatus $status, int $page, int $limit, ?string $categorySlug = null, ?string $search = null, ?string $memberId = null): array
    {
        $qb = $this->db->createQueryBuilder();
        $qb->select('s.*')
            ->from('ib_project_spaces', 's');

        if ($status !== null) {
            $qb->andWhere('s.status = :status')->setParameter('status', $status->value);
        } else {
            $qb->andWhere('s.status IN (:statuses)')->setParameter('statuses', ['active', 'completed'], Connection::PARAM_STR_ARRAY);
        }

        if ($categorySlug !== null) {
            $qb->innerJoin('s', 'ib_categories', 'c', 's.category_id = c.id')
                ->andWhere('c.slug = :categorySlug')
                ->setParameter('categorySlug', $categorySlug);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('(s.title LIKE :search OR s.description LIKE :search)')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($memberId !== null) {
            $qb->andWhere('(s.initiator_id = :memberId OR EXISTS (SELECT 1 FROM ib_project_space_members m WHERE m.space_id = s.id AND m.user_id = :memberId AND m.request_status = :accepted))')
                ->setParameter('memberId', $memberId)
                ->setParameter('accepted', 'accepted');
        }

        // Count total
        $countQb = clone $qb;
        $countQb->select('COUNT(*)');
        $total = (int) $countQb->execute()->fetchOne();

        // Paginate
        $offset = ($page - 1) * $limit;
        $qb->orderBy('s.priority', 'DESC')
            ->addOrderBy('s.created_at', 'DESC')
            ->addOrderBy('s.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $rows = $qb->execute()->fetchAllAssociative();
        $spaces = array_map(fn(array $row) => $this->hydrate($row), $rows);

        return ['data' => $spaces, 'total' => $total];
    }

    public function save(ProjectSpace $space): void
    {
        $exists = $this->db->fetchOne('SELECT 1 FROM ib_project_spaces WHERE id = ?', [$space->getId()]);

        if ($exists) {
            $this->db->executeStatement(
                'UPDATE ib_project_spaces SET title = ?, description = ?, status = ?, updated_at = ?, header_image_file_id = ?, priority = ? WHERE id = ?',
                [$space->getTitle(), $space->getDescription(), $space->getStatus()->value, $space->getUpdatedAt()->format('Y-m-d H:i:s'), $space->getHeaderImageFileId(), $space->getPriority(), $space->getId()],
            );
        } else {
            $this->db->executeStatement(
                'INSERT INTO ib_project_spaces (id, project_idea_id, title, description, status, initiator_id, category_id, header_image_file_id, priority, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$space->getId(), $space->getProjectIdeaId(), $space->getTitle(), $space->getDescription(), $space->getStatus()->value, (int) $space->getInitiatorId(), $space->getCategoryId() !== '' ? $space->getCategoryId() : null, $space->getHeaderImageFileId(), $space->getPriority(), $space->getCreatedAt()->format('Y-m-d H:i:s'), $space->getUpdatedAt()->format('Y-m-d H:i:s')],
            );
        }

        $this->syncMembers($space);
        $this->syncCollaboardLinks($space);
        $this->syncStatusUpdates($space);
        $this->syncMembershipRequests($space);
        $this->syncExpertRequests($space);
    }

    public function delete(string $id): void
    {
        // Child tables have ON DELETE CASCADE, so just delete the space row
        $this->db->executeStatement('DELETE FROM ib_project_spaces WHERE id = ?', [$id]);
    }

    public function countByPriority(): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ib_project_spaces WHERE priority > 0');
    }

    private function syncMembers(ProjectSpace $space): void
    {
        // Delete members no longer in the domain model
        $existingUserIds = $this->db->fetchFirstColumn(
            "SELECT user_id FROM ib_project_space_members WHERE space_id = ? AND request_status = 'accepted'",
            [$space->getId()],
        );
        $currentMemberIds = array_map(fn(string $id) => (int) $id, $space->getMemberIds());
        foreach ($existingUserIds as $existingUserId) {
            if (!in_array((int) $existingUserId, $currentMemberIds, true)) {
                $this->db->executeStatement(
                    'DELETE FROM ib_project_space_members WHERE space_id = ? AND user_id = ?',
                    [$space->getId(), $existingUserId],
                );
            }
        }

        $this->db->executeStatement(
            'INSERT IGNORE INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at) VALUES (?, ?, ?, ?, ?, ?)',
            [uniqid('mem_', true), $space->getId(), (int) $space->getInitiatorId(), 'initiator', RequestStatus::Accepted->value, $space->getCreatedAt()->format('Y-m-d H:i:s')],
        );

        foreach ($space->getMemberIds() as $userId) {
            if ($userId === $space->getInitiatorId()) {
                continue;
            }
            $this->db->executeStatement(
                'INSERT IGNORE INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at) VALUES (?, ?, ?, ?, ?, ?)',
                [uniqid('mem_', true), $space->getId(), (int) $userId, 'developer', RequestStatus::Accepted->value, (new \DateTimeImmutable())->format('Y-m-d H:i:s')],
            );
        }
    }

    private function syncCollaboardLinks(ProjectSpace $space): void
    {
        $existingIds = $this->db->fetchFirstColumn(
            'SELECT id FROM ib_project_space_collaboards WHERE space_id = ?',
            [$space->getId()],
        );
        $currentIds = array_map(fn(CollaboardLink $l) => $l->getId(), $space->getCollaboardLinks());

        $toDelete = array_diff($existingIds, $currentIds);
        foreach ($toDelete as $id) {
            $this->db->executeStatement('DELETE FROM ib_project_space_collaboards WHERE id = ?', [$id]);
        }

        $toInsert = array_diff($currentIds, $existingIds);
        foreach ($space->getCollaboardLinks() as $link) {
            if (in_array($link->getId(), $toInsert, true)) {
                $this->db->executeStatement(
                    'INSERT INTO ib_project_space_collaboards (id, space_id, title, url, added_at) VALUES (?, ?, ?, ?, ?)',
                    [$link->getId(), $space->getId(), $link->getTitle(), $link->getUrl(), $link->getAddedAt()->format('Y-m-d H:i:s')],
                );
            }
        }
    }

    private function syncStatusUpdates(ProjectSpace $space): void
    {
        $existingIds = $this->db->fetchFirstColumn(
            'SELECT id FROM ib_project_space_updates WHERE space_id = ?',
            [$space->getId()],
        );
        $currentIds = array_map(fn(StatusUpdate $u) => $u->getId(), $space->getStatusUpdates());

        // Delete removed updates
        $toDelete = array_diff($existingIds, $currentIds);
        foreach ($toDelete as $id) {
            $this->db->executeStatement('DELETE FROM ib_project_space_updates WHERE id = ?', [$id]);
        }

        // Insert or update
        foreach ($space->getStatusUpdates() as $update) {
            if (in_array($update->getId(), $existingIds, true)) {
                $this->db->executeStatement(
                    'UPDATE ib_project_space_updates SET title = ?, body = ? WHERE id = ?',
                    [$update->getTitle(), $update->getBody(), $update->getId()],
                );
            } else {
                $this->db->executeStatement(
                    'INSERT INTO ib_project_space_updates (id, space_id, title, body, author_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                    [$update->getId(), $space->getId(), $update->getTitle(), $update->getBody(), (int) $update->getAuthorId(), $update->getCreatedAt()->format('Y-m-d H:i:s')],
                );
            }

            $this->syncUpdateAttachments($update);
        }
    }

    private function syncUpdateAttachments(StatusUpdate $update): void
    {
        $existingIds = $this->db->fetchFirstColumn(
            'SELECT id FROM ib_update_attachments WHERE update_id = ?',
            [$update->getId()],
        );
        $currentIds = array_map(fn(StatusUpdateAttachment $a) => $a->getId(), $update->getAttachments());

        $toDelete = array_diff($existingIds, $currentIds);
        foreach ($toDelete as $id) {
            $this->db->executeStatement('DELETE FROM ib_update_attachments WHERE id = ?', [$id]);
        }

        foreach ($update->getAttachments() as $att) {
            if (!in_array($att->getId(), $existingIds, true)) {
                $this->db->insert('ib_update_attachments', [
                    'id' => $att->getId(),
                    'update_id' => $update->getId(),
                    'file_manager_id' => $att->getFileManagerId(),
                    'filename' => $att->getFilename(),
                    'mime_type' => $att->getMimeType(),
                    'size' => $att->getSize(),
                    'added_at' => $att->getAddedAt()->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function syncMembershipRequests(ProjectSpace $space): void
    {
        foreach ($space->getMembershipRequests() as $request) {
            $exists = $this->db->fetchOne('SELECT 1 FROM ib_project_space_members WHERE id = ?', [$request->getId()]);
            if ($exists) {
                $this->db->executeStatement(
                    'UPDATE ib_project_space_members SET request_status = ?, decided_at = ? WHERE id = ?',
                    [$request->getStatus()->value, $request->getDecidedAt()?->format('Y-m-d H:i:s'), $request->getId()],
                );
            } else {
                $this->db->executeStatement(
                    'INSERT INTO ib_project_space_members (id, space_id, user_id, role, request_status, requested_at, decided_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$request->getId(), $space->getId(), (int) $request->getUserId(), 'developer', $request->getStatus()->value, $request->getRequestedAt()->format('Y-m-d H:i:s'), $request->getDecidedAt()?->format('Y-m-d H:i:s')],
                );
            }
        }
    }

    private function syncExpertRequests(ProjectSpace $space): void
    {
        foreach ($space->getExpertRequests() as $req) {
            $exists = $this->db->fetchOne(
                'SELECT 1 FROM ib_expert_requests WHERE id = ?',
                [$req->getId()]
            );
            if ($exists !== false) {
                $this->db->executeStatement(
                    'UPDATE ib_expert_requests SET status = ?, decided_at = ? WHERE id = ?',
                    [$req->getStatus()->value, $req->getDecidedAt()?->format('Y-m-d H:i:s'), $req->getId()]
                );
            } else {
                $this->db->insert('ib_expert_requests', [
                    'id' => $req->getId(),
                    'space_id' => $space->getId(),
                    'expert_user_id' => $req->getExpertUserId(),
                    'requested_by_user_id' => $req->getRequestedByUserId(),
                    'message' => $req->getMessage(),
                    'status' => $req->getStatus()->value,
                    'requested_at' => $req->getRequestedAt()->format('Y-m-d H:i:s'),
                    'decided_at' => $req->getDecidedAt()?->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /** @return StatusUpdateAttachment[] */
    private function loadUpdateAttachments(string $updateId): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ib_update_attachments WHERE update_id = ? ORDER BY added_at ASC',
            [$updateId],
        );

        return array_map(fn(array $r) => StatusUpdateAttachment::fromPersistence(
            id: $r['id'],
            filename: $r['filename'],
            fileManagerId: (int) $r['file_manager_id'],
            mimeType: $r['mime_type'],
            size: (int) $r['size'],
            addedAt: new \DateTimeImmutable($r['added_at']),
        ), $rows);
    }

    /** @return ExpertRequest[] */
    private function loadExpertRequests(string $spaceId): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ib_expert_requests WHERE space_id = ? ORDER BY requested_at ASC',
            [$spaceId]
        );

        return array_map(fn(array $row) => ExpertRequest::fromPersistence(
            id: $row['id'],
            expertUserId: $row['expert_user_id'],
            requestedByUserId: $row['requested_by_user_id'],
            message: $row['message'],
            status: ExpertRequestStatus::from($row['status']),
            requestedAt: new \DateTimeImmutable($row['requested_at']),
            decidedAt: $row['decided_at'] !== null ? new \DateTimeImmutable($row['decided_at']) : null,
        ), $rows);
    }

    private function hydrate(array $row): ProjectSpace
    {
        $spaceId = $row['id'];

        $collaboards = array_map(
            fn(array $r) => CollaboardLink::fromPersistence($r['id'], $r['title'], $r['url'], new \DateTimeImmutable($r['added_at'])),
            $this->db->fetchAllAssociative('SELECT * FROM ib_project_space_collaboards WHERE space_id = ?', [$spaceId]),
        );

        $updateRows = $this->db->fetchAllAssociative('SELECT * FROM ib_project_space_updates WHERE space_id = ? ORDER BY created_at DESC', [$spaceId]);
        $updates = array_map(function (array $r) {
            $attachments = $this->loadUpdateAttachments($r['id']);

            return StatusUpdate::fromPersistence($r['id'], $r['title'], $r['body'], (string) $r['author_id'], new \DateTimeImmutable($r['created_at']), $attachments);
        }, $updateRows);

        $memberRows = $this->db->fetchAllAssociative('SELECT * FROM ib_project_space_members WHERE space_id = ?', [$spaceId]);
        $memberIds = [];
        $requests = [];
        foreach ($memberRows as $mr) {
            $status = RequestStatus::from($mr['request_status']);
            if ($status === RequestStatus::Accepted) {
                $memberIds[] = (string) $mr['user_id'];
            }
            if ($mr['role'] === 'developer' && $status !== RequestStatus::Accepted) {
                $requests[] = MembershipRequest::fromPersistence(
                    $mr['id'],
                    (string) $mr['user_id'],
                    $status,
                    new \DateTimeImmutable($mr['requested_at']),
                    $mr['decided_at'] ? new \DateTimeImmutable($mr['decided_at']) : null,
                );
            }
        }

        return ProjectSpace::fromPersistence(
            id: $row['id'],
            projectIdeaId: $row['project_idea_id'],
            title: $row['title'],
            description: $row['description'],
            status: SpaceStatus::from($row['status']),
            initiatorId: (string) $row['initiator_id'],
            categoryId: (string) ($row['category_id'] ?? ''),
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
            memberIds: $memberIds,
            collaboardLinks: $collaboards,
            statusUpdates: $updates,
            membershipRequests: $requests,
            expertRequests: $this->loadExpertRequests($spaceId),
            headerImageFileId: $row['header_image_file_id'] !== null ? (int) $row['header_image_file_id'] : null,
            priority: (int) ($row['priority'] ?? 0),
        );
    }

    public function findLatestUpdates(int $limit): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT u.id AS update_id, u.title, u.body, u.created_at, u.author_id,
                    s.id AS space_id, s.title AS space_title
             FROM ib_project_space_updates u
             JOIN ib_project_spaces s ON u.space_id = s.id
             WHERE s.status = ?
             ORDER BY u.created_at DESC
             LIMIT ' . (int) $limit,
            ['active'],
        );

        return array_map(static fn(array $r) => [
            'updateId' => $r['update_id'],
            'title' => $r['title'],
            'body' => $r['body'],
            'createdAt' => $r['created_at'],
            'authorId' => (string) $r['author_id'],
            'spaceId' => $r['space_id'],
            'spaceTitle' => $r['space_title'],
        ], $rows);
    }
}
