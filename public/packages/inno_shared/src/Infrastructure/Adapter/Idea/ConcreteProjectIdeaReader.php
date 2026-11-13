<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Idea;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoShared\Port\Out\ProjectIdeaReaderPort;

class ConcreteProjectIdeaReader implements ProjectIdeaReaderPort
{
    public function __construct(private readonly Connection $db) {}

    public function getSubmitterId(string $ideaId): string
    {
        $row = $this->db->fetchAssociative('SELECT submitter_id FROM ib_project_ideas WHERE id = ?', [$ideaId]);
        return $row ? (string) $row['submitter_id'] : '';
    }

    public function getIdeaTitle(string $ideaId): string
    {
        $row = $this->db->fetchAssociative('SELECT title FROM ib_project_ideas WHERE id = ?', [$ideaId]);
        return $row ? (string) $row['title'] : '';
    }
}
