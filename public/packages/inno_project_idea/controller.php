<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Core\Support\Facade\Events;
use Concrete\Package\InnoProjectIdea\Adapter\In\Http\ProjectIdeaApiController;
use Concrete\Package\InnoProjectIdea\Adapter\Out\Persistence\ConcreteProjectIdeaRepository;
use Concrete\Package\InnoProjectIdea\Application\EventHandler\OnModerationDecisionMade;
use Concrete\Package\InnoProjectIdea\Application\EventHandler\OnModerationFeedbackRequested;
use Concrete\Package\InnoProjectIdea\Application\EventHandler\OnProjectSpaceCreated;
use Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea\DeleteProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea\EditProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaHandler;
use Concrete\Package\InnoProjectIdea\Port\In\DeleteProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\In\EditProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\In\SubmitProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;

class Controller extends Package
{
    protected $pkgHandle = 'inno_project_idea';
    protected $appVersionRequired = '9.5.0';
    protected $pkgVersion = '1.2.0';

    public function getPackageDescription(): string
    {
        return t('Manages the submission, storage and lifecycle of project ideas.');
    }

    public function getPackageName(): string
    {
        return t('Innovationsbox – Project Idea');
    }

    public function on_start(): void
    {
        // Bind repository interface to its Concrete CMS implementation
        $this->app->bind(ProjectIdeaRepositoryInterface::class, ConcreteProjectIdeaRepository::class);

        // Bind inbound ports to their handlers
        $this->app->bind(SubmitProjectIdeaPort::class, SubmitProjectIdeaHandler::class);
        $this->app->bind(EditProjectIdeaPort::class, EditProjectIdeaHandler::class);
        $this->app->bind(DeleteProjectIdeaPort::class, DeleteProjectIdeaHandler::class);

        // REST API routes — use string format for Concrete CMS router compatibility
        $router = $this->app->make(Router::class);
        $ctrl = ProjectIdeaApiController::class;
        $router->register('/api/project-idea', "{$ctrl}::index", 'api.project_idea.index', [], [], '', [], ['GET']);
        $router->register('/api/project-idea', "{$ctrl}::store", 'api.project_idea.store', [], [], '', [], ['POST']);
        $router->register('/api/project-idea/mine', "{$ctrl}::mine", 'api.project_idea.mine', [], [], '', [], ['GET']);
        $router->register('/api/project-idea/{id}', "{$ctrl}::show", 'api.project_idea.show', [], [], '', [], ['GET']);
        $router->register('/api/project-idea/{id}', "{$ctrl}::update", 'api.project_idea.update', [], [], '', [], ['POST', 'PUT']);
        $router->register('/api/project-idea/{id}', "{$ctrl}::destroy", 'api.project_idea.destroy', [], [], '', [], ['DELETE']);

        // React to moderation decisions from the moderation package
        Events::addListener(
            'innovationsbox.moderation.decision_made',
            [$this->app->make(OnModerationDecisionMade::class), 'handle']
        );

        // React to moderation feedback requests from the moderation package
        Events::addListener(
            'innovationsbox.moderation.feedback_requested',
            [$this->app->make(OnModerationFeedbackRequested::class), 'handle']
        );

        // React to project space creation from the project space package
        Events::addListener(
            'innovationsbox.project_space.created',
            [$this->app->make(OnProjectSpaceCreated::class), 'handle']
        );
    }

    public function install(): void
    {
        parent::install();
        $db = $this->app->make('database')->connection();
        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_project_ideas (
                id VARCHAR(36) NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                category_id VARCHAR(64) NULL,
                status VARCHAR(64) NOT NULL DEFAULT \'draft\',
                submitter_id INT NULL,
                rejection_reason TEXT NULL,
                project_space_id VARCHAR(64) NULL,
                feedback_notes TEXT NULL,
                feedback_deadline VARCHAR(20) NULL,
                header_image_file_id INT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_category (category_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_idea_attachments (
                id VARCHAR(64) NOT NULL,
                idea_id VARCHAR(64) NOT NULL,
                file_manager_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                size INT NOT NULL,
                added_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_idea_id (idea_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function upgrade(): void
    {
        parent::upgrade();
        $db = $this->app->make('database')->connection();
        $sm = $db->getSchemaManager();
        $columns = array_map(fn($c) => $c->getName(), $sm->listTableColumns('ib_project_ideas'));
        if (in_array('category', $columns, true) && !in_array('category_id', $columns, true)) {
            $db->executeStatement('ALTER TABLE ib_project_ideas CHANGE category category_id VARCHAR(64) NULL');
            $db->executeStatement('CREATE INDEX idx_category ON ib_project_ideas (category_id)');
        }
        if (!in_array('rejection_reason', $columns, true)) {
            $db->executeStatement('ALTER TABLE ib_project_ideas ADD COLUMN rejection_reason TEXT NULL');
        }
        if (!in_array('project_space_id', $columns, true)) {
            $db->executeStatement('ALTER TABLE ib_project_ideas ADD COLUMN project_space_id VARCHAR(64) NULL');
        }
        if (!in_array('feedback_notes', $columns, true)) {
            $db->executeStatement('ALTER TABLE ib_project_ideas ADD COLUMN feedback_notes TEXT NULL');
        }
        if (!in_array('feedback_deadline', $columns, true)) {
            $db->executeStatement('ALTER TABLE ib_project_ideas ADD COLUMN feedback_deadline VARCHAR(20) NULL');
        }
        if (!in_array('header_image_file_id', $columns, true)) {
            $db->executeStatement('ALTER TABLE ib_project_ideas ADD COLUMN header_image_file_id INT NULL AFTER feedback_deadline');
        }
        if (!$sm->tablesExist(['ib_idea_attachments'])) {
            $db->executeStatement('
                CREATE TABLE IF NOT EXISTS ib_idea_attachments (
                    id VARCHAR(64) NOT NULL,
                    idea_id VARCHAR(64) NOT NULL,
                    file_manager_id INT NOT NULL,
                    filename VARCHAR(255) NOT NULL,
                    mime_type VARCHAR(100) NOT NULL,
                    size INT NOT NULL,
                    added_at DATETIME NOT NULL,
                    PRIMARY KEY (id),
                    INDEX idx_idea_id (idea_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');
        }
    }
}
