<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaAttachment;
use Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus;
use Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;

class ConcreteProjectIdeaRepository implements ProjectIdeaRepositoryInterface
{
    private const TABLE = 'ib_project_ideas';
    private const ATTACHMENTS_TABLE = 'ib_idea_attachments';

    public function __construct(private readonly Connection $db) {}

    public function findById(string $id): ?ProjectIdea
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE . ' WHERE id = ?', [$id]);

        return $row !== false ? $this->hydrate($row) : null;
    }

    public function findAll(): array
    {
        $rows = $this->db->fetchAllAssociative('SELECT * FROM ' . self::TABLE . ' ORDER BY created_at DESC');

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function findByStatus(IdeaStatus $status): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . self::TABLE . ' WHERE status = ? ORDER BY created_at DESC',
            [$status->value],
        );

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function findBySubmitterId(string $submitterId): array
    {
        $cutoff = (new \DateTimeImmutable())->modify('-90 days')->format('Y-m-d H:i:s');
        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . self::TABLE
            . ' WHERE submitter_id = ? AND (status IN (?, ?, ?) OR updated_at >= ?)'
            . ' ORDER BY created_at DESC',
            [$submitterId, 'submitted', 'under_review', 'feedback_requested', $cutoff],
        );

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function save(ProjectIdea $idea): void
    {
        $exists = $this->db->fetchOne('SELECT id FROM ' . self::TABLE . ' WHERE id = ?', [$idea->getId()]);

        if ($exists === false) {
            $this->db->executeStatement(
                'INSERT INTO ' . self::TABLE . '
                    (id, title, description, category_id, status, submitter_id, rejection_reason, project_space_id, feedback_notes, feedback_deadline, header_image_file_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $idea->getId(),
                    $idea->getTitle(),
                    $idea->getDescription(),
                    $idea->getCategory(),
                    $idea->getStatus()->value,
                    $idea->getSubmitterId(),
                    $idea->getRejectionReason(),
                    $idea->getProjectSpaceId(),
                    $idea->getFeedbackNotes(),
                    $idea->getFeedbackDeadline(),
                    $idea->getHeaderImageFileId(),
                    $idea->getCreatedAt()->format('Y-m-d H:i:s'),
                    $idea->getUpdatedAt()->format('Y-m-d H:i:s'),
                ],
            );
        } else {
            $this->db->executeStatement(
                'UPDATE ' . self::TABLE . ' SET
                    title = ?, description = ?, category_id = ?, status = ?,
                    submitter_id = ?, rejection_reason = ?, project_space_id = ?,
                    feedback_notes = ?, feedback_deadline = ?, header_image_file_id = ?, updated_at = ?
                 WHERE id = ?',
                [
                    $idea->getTitle(),
                    $idea->getDescription(),
                    $idea->getCategory(),
                    $idea->getStatus()->value,
                    $idea->getSubmitterId(),
                    $idea->getRejectionReason(),
                    $idea->getProjectSpaceId(),
                    $idea->getFeedbackNotes(),
                    $idea->getFeedbackDeadline(),
                    $idea->getHeaderImageFileId(),
                    $idea->getUpdatedAt()->format('Y-m-d H:i:s'),
                    $idea->getId(),
                ],
            );
        }

        $this->syncAttachments($idea);
    }

    public function delete(string $id): void
    {
        $this->db->executeStatement('DELETE FROM ' . self::ATTACHMENTS_TABLE . ' WHERE idea_id = ?', [$id]);
        $this->db->executeStatement('DELETE FROM ' . self::TABLE . ' WHERE id = ?', [$id]);
    }

    private function syncAttachments(ProjectIdea $idea): void
    {
        $existing = $this->db->fetchAllAssociative(
            'SELECT id FROM ' . self::ATTACHMENTS_TABLE . ' WHERE idea_id = ?',
            [$idea->getId()],
        );
        $existingIds = array_column($existing, 'id');
        $currentIds = array_map(fn(IdeaAttachment $a) => $a->getId(), $idea->getAttachments());

        $toDelete = array_diff($existingIds, $currentIds);
        foreach ($toDelete as $attId) {
            $this->db->executeStatement('DELETE FROM ' . self::ATTACHMENTS_TABLE . ' WHERE id = ?', [$attId]);
        }

        $toInsert = array_diff($currentIds, $existingIds);
        foreach ($idea->getAttachments() as $att) {
            if (in_array($att->getId(), $toInsert, true)) {
                $this->db->insert(self::ATTACHMENTS_TABLE, [
                    'id' => $att->getId(),
                    'idea_id' => $idea->getId(),
                    'file_manager_id' => $att->getFileManagerId(),
                    'filename' => $att->getFilename(),
                    'mime_type' => $att->getMimeType(),
                    'size' => $att->getSize(),
                    'added_at' => $att->getAddedAt()->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /** @return IdeaAttachment[] */
    private function loadAttachments(string $ideaId): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . self::ATTACHMENTS_TABLE . ' WHERE idea_id = ? ORDER BY added_at ASC',
            [$ideaId],
        );

        return array_map(fn(array $row) => IdeaAttachment::fromPersistence(
            id: $row['id'],
            filename: $row['filename'],
            fileManagerId: (int) $row['file_manager_id'],
            mimeType: $row['mime_type'],
            size: (int) $row['size'],
            addedAt: new \DateTimeImmutable($row['added_at']),
        ), $rows);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ProjectIdea
    {
        return ProjectIdea::reconstitute(
            id: $row['id'],
            title: $row['title'],
            description: $row['description'] ?? null,
            category: $row['category_id'],
            status: IdeaStatus::from($row['status']),
            submitterId: isset($row['submitter_id']) ? (int) $row['submitter_id'] : null,
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
            rejectionReason: $row['rejection_reason'] ?? null,
            projectSpaceId: $row['project_space_id'] ?? null,
            attachments: $this->loadAttachments($row['id']),
            feedbackNotes: $row['feedback_notes'] ?? null,
            feedbackDeadline: $row['feedback_deadline'] ?? null,
            headerImageFileId: $row['header_image_file_id'] !== null ? (int) $row['header_image_file_id'] : null,
        );
    }
}
