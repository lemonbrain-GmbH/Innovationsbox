<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\Out\Persistence;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Domain\Model\ModerationReview;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;

class ConcreteModerationReviewRepository implements ModerationReviewRepositoryInterface
{
    private const TABLE = 'ib_moderation_reviews';

    public function __construct(private readonly Connection $db) {}

    public function findById(string $id): ?ModerationReview
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE . ' WHERE id = ?', [$id]);

        return $row !== false ? $this->hydrate($row) : null;
    }

    public function findByIdeaId(string $ideaId): ?ModerationReview
    {
        $row = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE . ' WHERE idea_id = ?', [$ideaId]);

        return $row !== false ? $this->hydrate($row) : null;
    }

    /** @return ModerationReview[] */
    public function findByDecision(ModerationDecision $decision): array
    {
        $rows = $this->db->fetchAllAssociative(
            'SELECT * FROM ' . self::TABLE . ' WHERE decision = ? ORDER BY created_at ASC',
            [$decision->value],
        );

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function save(ModerationReview $review): void
    {
        $data = [
            'id' => $review->getId(),
            'idea_id' => $review->getIdeaId(),
            'decision' => $review->getDecision()->value,
            'reviewer_id' => $review->getReviewerId(),
            'notes' => $review->getNotes(),
            'title_override' => $review->getTitleOverride(),
            'description_override' => $review->getDescriptionOverride(),
            'category_override' => $review->getCategoryOverride(),
            'feedback_deadline' => $review->getFeedbackDeadline(),
            'created_at' => $review->getCreatedAt()->format('Y-m-d H:i:s'),
            'reviewed_at' => $review->getReviewedAt()?->format('Y-m-d H:i:s'),
        ];

        $exists = $this->db->fetchOne('SELECT 1 FROM ' . self::TABLE . ' WHERE id = ?', [$review->getId()]);
        if ($exists !== false) {
            $this->db->update(self::TABLE, $data, ['id' => $review->getId()]);
        } else {
            $this->db->insert(self::TABLE, $data);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ModerationReview
    {
        return ModerationReview::reconstitute(
            id: $row['id'],
            ideaId: $row['idea_id'],
            decision: ModerationDecision::from($row['decision']),
            reviewerId: $row['reviewer_id'],
            notes: $row['notes'] ?? '',
            createdAt: new \DateTimeImmutable($row['created_at']),
            reviewedAt: $row['reviewed_at'] !== null ? new \DateTimeImmutable($row['reviewed_at']) : null,
            titleOverride: $row['title_override'],
            descriptionOverride: $row['description_override'],
            categoryOverride: $row['category_override'],
            feedbackDeadline: $row['feedback_deadline'] ?? null,
        );
    }
}
