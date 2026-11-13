<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Core\Support\Facade\Events;
use Concrete\Package\InnoModeration\Adapter\Out\Persistence\ConcreteCategoryRepository;
use Concrete\Package\InnoProjectSpace\Adapter\In\Http\ProjectSpaceApiController;
use Concrete\Package\InnoProjectSpace\Adapter\Out\Persistence\ConcreteProjectSpaceRepository;
use Concrete\Package\InnoProjectSpace\Application\EventHandler\OnProjectIdeaApproved;
use Concrete\Package\InnoProjectSpace\Application\EventHandler\OnUserAccountDeactivated;
use Concrete\Package\InnoProjectSpace\Application\UseCase\AddCollaboardLink\AddCollaboardLinkHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\ChangeSpaceStatus\ChangeSpaceStatusHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\DeleteProjectSpace\DeleteProjectSpaceHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest\HandleExpertRequestHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest\HandleJoinRequestHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\InviteMember\InviteMemberHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\LeaveSpace\LeaveSpaceHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveCollaboardLink\RemoveCollaboardLinkHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveMember\RemoveMemberHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert\RequestExpertHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin\RequestToJoinHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\SetSpacePriority\SetSpacePriorityHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\UpdateHeaderImageHandler;
use Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate\WriteStatusUpdateHandler;
use Concrete\Package\InnoProjectSpace\Port\In\AddCollaboardLinkPort;
use Concrete\Package\InnoProjectSpace\Port\In\ChangeSpaceStatusPort;
use Concrete\Package\InnoProjectSpace\Port\In\DeleteProjectSpacePort;
use Concrete\Package\InnoProjectSpace\Port\In\HandleExpertRequestPort;
use Concrete\Package\InnoProjectSpace\Port\In\HandleJoinRequestPort;
use Concrete\Package\InnoProjectSpace\Port\In\InviteMemberPort;
use Concrete\Package\InnoProjectSpace\Port\In\LeaveSpacePort;
use Concrete\Package\InnoProjectSpace\Port\In\RemoveCollaboardLinkPort;
use Concrete\Package\InnoProjectSpace\Port\In\RemoveMemberPort;
use Concrete\Package\InnoProjectSpace\Port\In\RequestExpertPort;
use Concrete\Package\InnoProjectSpace\Port\In\RequestToJoinPort;
use Concrete\Package\InnoProjectSpace\Port\In\SetSpacePriorityPort;
use Concrete\Package\InnoProjectSpace\Port\In\UpdateHeaderImagePort;
use Concrete\Package\InnoProjectSpace\Port\In\WriteStatusUpdatePort;
use Concrete\Package\InnoProjectSpace\Port\Out\ProjectSpaceRepositoryInterface;
use Concrete\Package\InnoShared\Port\CategoryReaderPort;

class Controller extends Package
{
    protected $pkgHandle = 'inno_project_space';
    protected $appVersionRequired = '9.5.0';
    protected $pkgVersion = '1.2.0';

    public function getPackageDescription(): string
    {
        return t('Protected collaboration spaces for approved project ideas.');
    }

    public function getPackageName(): string
    {
        return t('Innovationsbox – Project Space');
    }

    public function on_start(): void
    {
        // Bind repository interface to its Concrete CMS implementation
        $this->app->bind(ProjectSpaceRepositoryInterface::class, ConcreteProjectSpaceRepository::class);

        // Bind Port/In interfaces to their handlers
        $this->app->bind(AddCollaboardLinkPort::class, AddCollaboardLinkHandler::class);
        $this->app->bind(RemoveCollaboardLinkPort::class, RemoveCollaboardLinkHandler::class);
        $this->app->bind(WriteStatusUpdatePort::class, WriteStatusUpdateHandler::class);
        $this->app->bind(RequestToJoinPort::class, RequestToJoinHandler::class);
        $this->app->bind(HandleJoinRequestPort::class, HandleJoinRequestHandler::class);
        $this->app->bind(ChangeSpaceStatusPort::class, ChangeSpaceStatusHandler::class);
        $this->app->bind(InviteMemberPort::class, InviteMemberHandler::class);
        $this->app->bind(RequestExpertPort::class, RequestExpertHandler::class);
        $this->app->bind(HandleExpertRequestPort::class, HandleExpertRequestHandler::class);
        $this->app->bind(LeaveSpacePort::class, LeaveSpaceHandler::class);
        $this->app->bind(DeleteProjectSpacePort::class, DeleteProjectSpaceHandler::class);
        $this->app->bind(RemoveMemberPort::class, RemoveMemberHandler::class);
        $this->app->bind(UpdateHeaderImagePort::class, UpdateHeaderImageHandler::class);
        $this->app->bind(SetSpacePriorityPort::class, SetSpacePriorityHandler::class);

        // Bind CategoryReaderPort (implemented by inno_moderation) so DI can resolve it
        // for ProjectSpaceApiController regardless of package load order
        if (!$this->app->bound(CategoryReaderPort::class)) {
            $this->app->bind(CategoryReaderPort::class, ConcreteCategoryRepository::class);
        }

        // REST API routes — use string callback format "Controller::method"
        $router = $this->app->make(Router::class);
        $ctrl = ProjectSpaceApiController::class;
        $router->register('/api/expert-request/mine', "{$ctrl}::myExpertRequests", 'api.expert_request.mine', [], [], '', [], ['GET']);
        $router->register('/api/project-space', "{$ctrl}::index", 'api.project_space.index', [], [], '', [], ['GET']);
        $router->register('/api/project-space/categories', "{$ctrl}::categories", 'api.project_space.categories', [], [], '', [], ['GET']);
        $router->register('/api/project-space/latest-updates', "{$ctrl}::latestUpdates", 'api.project_space.latest_updates', [], [], '', [], ['GET']);
        $router->register('/api/project-space/{id}', "{$ctrl}::show", 'api.project_space.show', [], [], '', [], ['GET']);
        $router->register('/api/project-space/{id}/collaboard', "{$ctrl}::addCollaboard", 'api.project_space.add_collaboard', [], [], '', [], ['POST']);
        $router->register('/api/project-space/{id}/collaboard/{linkId}', "{$ctrl}::removeCollaboard", 'api.project_space.remove_collaboard', [], [], '', [], ['DELETE']);
        $router->register('/api/project-space/{id}/status-update', "{$ctrl}::addStatusUpdate", 'api.project_space.add_status_update', [], [], '', [], ['POST']);
        $router->register('/api/project-space/{id}/status-update/{updateId}', "{$ctrl}::editStatusUpdate", 'api.project_space.edit_status_update', [], [], '', [], ['PUT']);
        $router->register('/api/project-space/{id}/status-update/{updateId}/edit', "{$ctrl}::editStatusUpdate", 'api.project_space.edit_status_update_post', [], [], '', [], ['POST']);
        $router->register('/api/project-space/{id}/status-update/{updateId}', "{$ctrl}::deleteStatusUpdate", 'api.project_space.delete_status_update', [], [], '', [], ['DELETE']);
        $router->register('/api/project-space/{id}', "{$ctrl}::deleteSpace", 'api.project_space.delete', [], [], '', [], ['DELETE']);
        $router->register('/api/project-space/{id}/experts', "{$ctrl}::listExperts", 'api.project_space.list_experts', [], [], '', [], ['GET']);
        $router->register('/api/project-space/{id}/expert-request', "{$ctrl}::requestExpert", 'api.project_space.request_expert', [], [], '', [], ['POST']);
        $router->register('/api/project-space/{id}/expert-request/{requestId}', "{$ctrl}::handleExpertRequest", 'api.project_space.handle_expert_request', [], [], '', [], ['PUT']);
        $router->register('/api/project-space/{id}/join-request', "{$ctrl}::joinRequest", 'api.project_space.join_request', [], [], '', [], ['POST']);
        $router->register('/api/project-space/{id}/join-request/{requestId}', "{$ctrl}::handleJoinRequest", 'api.project_space.handle_join_request', [], [], '', [], ['PUT']);
        $router->register('/api/project-space/{id}/status', "{$ctrl}::changeStatus", 'api.project_space.change_status', [], [], '', [], ['PUT']);
        $router->register('/api/project-space/{id}/invite', "{$ctrl}::invite", 'api.project_space.invite', [], [], '', [], ['POST']);
        $router->register('/api/project-space/{id}/membership', "{$ctrl}::leaveSpace", 'api.project_space.leave', [], [], '', [], ['DELETE']);
        $router->register('/api/project-space/{id}/member/{userId}', "{$ctrl}::removeMember", 'api.project_space.remove_member', [], [], '', [], ['DELETE']);
        $router->register('/api/project-space/{id}/header-image', "{$ctrl}::uploadHeaderImage", 'api.project_space.upload_header_image', [], [], '', [], ['POST']);
        $router->register('/api/project-space/{id}/header-image', "{$ctrl}::deleteHeaderImage", 'api.project_space.delete_header_image', [], [], '', [], ['DELETE']);
        $router->register('/api/project-space/{id}/priority', "{$ctrl}::setPriority", 'api.project_space.set_priority', [], [], '', [], ['PUT']);

        // Automatically create a project space when an idea is approved
        // Use a closure so dependencies are resolved lazily (at event dispatch time,
        // not during on_start() when other packages may not have registered bindings yet).
        Events::addListener(
            'innovationsbox.project_idea.approved',
            function ($event) {
                $this->app->make(OnProjectIdeaApproved::class)->handle($event);
            }
        );

        // Remove deactivated users from all project spaces
        Events::addListener(
            'innovationsbox.user.account_deactivated',
            function ($event) {
                $this->app->make(OnUserAccountDeactivated::class)->handle($event);
            }
        );
    }

    public function install(): void
    {
        parent::install();

        $db = $this->app->make('database')->connection();

        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_project_spaces (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                project_idea_id VARCHAR(64) NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                status ENUM("draft","active","completed","archived") NOT NULL DEFAULT "draft",
                initiator_id INT UNSIGNED NOT NULL,
                category_id VARCHAR(64) NULL,
                priority TINYINT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_status (status),
                INDEX idx_initiator (initiator_id),
                INDEX idx_category (category_id),
                UNIQUE INDEX idx_idea (project_idea_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_project_space_collaboards (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                space_id VARCHAR(64) NOT NULL,
                title VARCHAR(255) NOT NULL,
                url VARCHAR(2048) NOT NULL,
                added_at DATETIME NOT NULL,
                INDEX idx_space (space_id),
                FOREIGN KEY (space_id) REFERENCES ib_project_spaces(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_project_space_updates (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                space_id VARCHAR(64) NOT NULL,
                title VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                author_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_space_date (space_id, created_at DESC),
                FOREIGN KEY (space_id) REFERENCES ib_project_spaces(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_update_attachments (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                update_id VARCHAR(64) NOT NULL,
                file_manager_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                size INT NOT NULL,
                added_at DATETIME NOT NULL,
                INDEX idx_update_id (update_id),
                FOREIGN KEY (update_id) REFERENCES ib_project_space_updates(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_project_space_members (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                space_id VARCHAR(64) NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                role ENUM("initiator","developer") NOT NULL,
                request_status ENUM("pending","accepted","rejected") NOT NULL DEFAULT "accepted",
                requested_at DATETIME NOT NULL,
                decided_at DATETIME NULL,
                INDEX idx_space (space_id),
                INDEX idx_user (user_id),
                UNIQUE INDEX idx_space_user (space_id, user_id),
                FOREIGN KEY (space_id) REFERENCES ib_project_spaces(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_expert_requests (
                id VARCHAR(64) NOT NULL,
                space_id VARCHAR(64) NOT NULL,
                expert_user_id VARCHAR(64) NOT NULL,
                requested_by_user_id VARCHAR(64) NOT NULL,
                message TEXT NULL,
                status VARCHAR(20) NOT NULL DEFAULT \'pending\',
                requested_at DATETIME NOT NULL,
                decided_at DATETIME NULL,
                PRIMARY KEY (id),
                INDEX idx_space_id (space_id),
                INDEX idx_expert_user_id (expert_user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function upgrade(): void
    {
        parent::upgrade();

        $db = $this->app->make('database')->connection();
        $columns = array_map(fn($c) => $c->getName(), $db->getSchemaManager()->listTableColumns('ib_project_spaces'));
        if (!in_array('category_id', $columns, true)) {
            $db->executeStatement('ALTER TABLE ib_project_spaces ADD COLUMN category_id VARCHAR(64) NULL AFTER initiator_id');
            $db->executeStatement('CREATE INDEX idx_category ON ib_project_spaces (category_id)');
        }
        if (!in_array('header_image_file_id', $columns, true)) {
            $db->executeStatement('ALTER TABLE ib_project_spaces ADD COLUMN header_image_file_id INT NULL AFTER category_id');
        }
        if (!in_array('priority', $columns, true)) {
            $db->executeStatement('ALTER TABLE ib_project_spaces ADD COLUMN priority TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER header_image_file_id');
        }

        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_update_attachments (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                update_id VARCHAR(64) NOT NULL,
                file_manager_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                size INT NOT NULL,
                added_at DATETIME NOT NULL,
                INDEX idx_update_id (update_id),
                FOREIGN KEY (update_id) REFERENCES ib_project_space_updates(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_expert_requests (
                id VARCHAR(64) NOT NULL,
                space_id VARCHAR(64) NOT NULL,
                expert_user_id VARCHAR(64) NOT NULL,
                requested_by_user_id VARCHAR(64) NOT NULL,
                message TEXT NULL,
                status VARCHAR(20) NOT NULL DEFAULT \'pending\',
                requested_at DATETIME NOT NULL,
                decided_at DATETIME NULL,
                PRIMARY KEY (id),
                INDEX idx_space_id (space_id),
                INDEX idx_expert_user_id (expert_user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }
}
