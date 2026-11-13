<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Core\Support\Facade\Events;
use Concrete\Package\InnoModeration\Adapter\In\Http\CategoryApiController;
use Concrete\Package\InnoModeration\Adapter\In\Http\ModerationApiController;
use Concrete\Package\InnoModeration\Adapter\In\Http\UserApiController;
use Concrete\Package\InnoModeration\Adapter\Out\Persistence\ConcreteCategoryRepository;
use Concrete\Package\InnoModeration\Adapter\Out\Persistence\ConcreteModerationReviewRepository;
use Concrete\Package\InnoModeration\Application\EventHandler\OnProjectIdeaResubmitted;
use Concrete\Package\InnoModeration\Application\EventHandler\OnProjectIdeaSubmitted;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\ManageCategoryHandler;
use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaHandler;
use Concrete\Package\InnoModeration\Port\In\ManageCategoryPort;
use Concrete\Package\InnoModeration\Port\In\ReviewProjectIdeaPort;
use Concrete\Package\InnoModeration\Port\Out\CategoryRepositoryPort;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoShared\Port\CategoryReaderPort;

class Controller extends Package
{
    protected $pkgHandle = 'inno_moderation';
    protected $appVersionRequired = '9.5.0';
    protected $pkgVersion = '1.2.0';

    public function getPackageDescription(): string
    {
        return t('Review queue for submitted project ideas. Approve or reject before publishing.');
    }

    public function getPackageName(): string
    {
        return t('Innovationsbox – Moderation');
    }

    public function on_start(): void
    {
        $this->app->bind(ModerationReviewRepositoryInterface::class, ConcreteModerationReviewRepository::class);
        $this->app->bind(ReviewProjectIdeaPort::class, ReviewProjectIdeaHandler::class);

        // Category bindings
        $this->app->bind(CategoryRepositoryPort::class, ConcreteCategoryRepository::class);
        $this->app->bind(CategoryReaderPort::class, ConcreteCategoryRepository::class);
        $this->app->bind(ManageCategoryPort::class, ManageCategoryHandler::class);

        // REST API routes
        $router = $this->app->make(Router::class);

        // Category routes — must be before {id} wildcard to avoid conflicts
        $catCtrl = CategoryApiController::class;
        $router->register('/api/moderation/category', "{$catCtrl}::index", 'api.moderation.category.index', [], [], '', [], ['GET']);
        $router->register('/api/moderation/category', "{$catCtrl}::store", 'api.moderation.category.store', [], [], '', [], ['POST']);
        $router->register('/api/moderation/category/{id}', "{$catCtrl}::update", 'api.moderation.category.update', [], [], '', [], ['PUT']);
        $router->register('/api/moderation/category/{id}', "{$catCtrl}::destroy", 'api.moderation.category.destroy', [], [], '', [], ['DELETE']);

        // User routes
        $userCtrl = UserApiController::class;
        $router->register('/api/moderation/user', "{$userCtrl}::index", 'api.moderation.user.index', [], [], '', [], ['GET']);
        $router->register('/api/moderation/user/{id}/expert', "{$userCtrl}::toggleExpert", 'api.moderation.user.toggle_expert', [], [], '', [], ['PUT']);
        $router->register('/api/moderation/user/{id}', "{$userCtrl}::deactivateUser", 'api.moderation.user.deactivate', [], [], '', [], ['DELETE']);
        $router->register('/api/user/account', "{$userCtrl}::deactivateOwnAccount", 'api.user.account.deactivate', [], [], '', [], ['DELETE']);
        $router->register('/api/user/counts', "{$userCtrl}::counts", 'api.user.counts', [], [], '', [], ['GET']);

        // Moderation review routes
        $modCtrl = ModerationApiController::class;
        $router->register('/api/moderation', "{$modCtrl}::index", 'api.moderation.index', [], [], '', [], ['GET']);
        $router->register('/api/moderation/{id}', "{$modCtrl}::show", 'api.moderation.show', [], [], '', [], ['GET']);
        $router->register('/api/moderation/{id}/approve', "{$modCtrl}::approve", 'api.moderation.approve', [], [], '', [], ['POST']);
        $router->register('/api/moderation/{id}/reject', "{$modCtrl}::reject", 'api.moderation.reject', [], [], '', [], ['POST']);
        $router->register('/api/moderation/{id}/feedback', "{$modCtrl}::requestFeedback", 'api.moderation.feedback', [], [], '', [], ['POST']);

        // Create a pending review whenever a new idea is submitted
        Events::addListener(
            'innovationsbox.project_idea.submitted',
            [$this->app->make(OnProjectIdeaSubmitted::class), 'handle']
        );

        // Reset review to pending when idea is resubmitted after feedback
        Events::addListener(
            'innovationsbox.project_idea.resubmitted',
            [$this->app->make(OnProjectIdeaResubmitted::class), 'handle']
        );
    }

    public function install(): void
    {
        parent::install();

        $db = $this->app->make('database')->connection();

        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_moderation_reviews (
                id VARCHAR(64) NOT NULL,
                idea_id VARCHAR(64) NOT NULL,
                decision VARCHAR(20) NOT NULL DEFAULT \'pending\',
                reviewer_id VARCHAR(64) NULL,
                notes TEXT NULL,
                title_override VARCHAR(255) NULL,
                description_override TEXT NULL,
                category_override VARCHAR(64) NULL,
                feedback_deadline VARCHAR(20) NULL,
                created_at DATETIME NOT NULL,
                reviewed_at DATETIME NULL,
                PRIMARY KEY (id),
                INDEX idx_idea_id (idea_id),
                INDEX idx_decision (decision)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $db->executeStatement('
            CREATE TABLE IF NOT EXISTS ib_categories (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        // Seed initial categories
        $now = date('Y-m-d H:i:s');
        $seeds = [
            ['cat_bauen', 'Bauen', 'bauen', 0],
            ['cat_wohnen', 'Wohnen', 'wohnen', 1],
            ['cat_landwirtschaft', 'Landwirtschaft', 'landwirtschaft', 2],
            ['cat_tourismus', 'Tourismus', 'tourismus', 3],
            ['cat_mobilitaet', 'Mobilität', 'mobilitaet', 4],
        ];
        foreach ($seeds as [$id, $name, $slug, $order]) {
            $db->executeStatement(
                'INSERT IGNORE INTO ib_categories (id, name, slug, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$id, $name, $slug, $order, $now, $now],
            );
        }
    }

    public function upgrade(): void
    {
        parent::upgrade();

        $db = $this->app->make('database')->connection();
        $sm = $db->getSchemaManager();
        if (!$sm->tablesExist(['ib_categories'])) {
            $db->executeStatement('
                CREATE TABLE IF NOT EXISTS ib_categories (
                    id VARCHAR(64) NOT NULL PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    slug VARCHAR(100) NOT NULL UNIQUE,
                    sort_order INT NOT NULL DEFAULT 0,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    INDEX idx_sort (sort_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');

            // Seed initial categories
            $now = date('Y-m-d H:i:s');
            $seeds = [
                ['cat_bauen', 'Bauen', 'bauen', 0],
                ['cat_wohnen', 'Wohnen', 'wohnen', 1],
                ['cat_landwirtschaft', 'Landwirtschaft', 'landwirtschaft', 2],
                ['cat_tourismus', 'Tourismus', 'tourismus', 3],
                ['cat_mobilitaet', 'Mobilität', 'mobilitaet', 4],
            ];
            foreach ($seeds as [$id, $name, $slug, $order]) {
                $db->executeStatement(
                    'INSERT IGNORE INTO ib_categories (id, name, slug, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                    [$id, $name, $slug, $order, $now, $now],
                );
            }
        }

        // Add feedback_deadline column to existing reviews table
        if ($sm->tablesExist(['ib_moderation_reviews'])) {
            $reviewColumns = array_map(fn($c) => $c->getName(), $sm->listTableColumns('ib_moderation_reviews'));
            if (!in_array('feedback_deadline', $reviewColumns, true)) {
                $db->executeStatement('ALTER TABLE ib_moderation_reviews ADD COLUMN feedback_deadline VARCHAR(20) NULL');
            }
        }

        if (!$sm->tablesExist(['ib_moderation_reviews'])) {
            $db->executeStatement('
                CREATE TABLE IF NOT EXISTS ib_moderation_reviews (
                    id VARCHAR(64) NOT NULL,
                    idea_id VARCHAR(64) NOT NULL,
                    decision VARCHAR(20) NOT NULL DEFAULT \'pending\',
                    reviewer_id VARCHAR(64) NULL,
                    notes TEXT NULL,
                    title_override VARCHAR(255) NULL,
                    description_override TEXT NULL,
                    category_override VARCHAR(64) NULL,
                    created_at DATETIME NOT NULL,
                    reviewed_at DATETIME NULL,
                    PRIMARY KEY (id),
                    INDEX idx_idea_id (idea_id),
                    INDEX idx_decision (decision)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');
        }
    }
}
