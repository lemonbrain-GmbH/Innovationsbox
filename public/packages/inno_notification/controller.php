<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Core\Support\Facade\Events;
use Concrete\Package\InnoNotification\Adapter\In\Http\NotificationApiController;
use Concrete\Package\InnoNotification\Adapter\Out\Email\ConcreteEmailService;
use Concrete\Package\InnoNotification\Application\EventHandler\OnExpertRequestAccepted;
use Concrete\Package\InnoNotification\Application\EventHandler\OnExpertRequestDeclined;
use Concrete\Package\InnoNotification\Application\EventHandler\OnExpertRequested;
use Concrete\Package\InnoNotification\Application\EventHandler\OnJoinRequestAccepted;
use Concrete\Package\InnoNotification\Application\EventHandler\OnJoinRequestReceived;
use Concrete\Package\InnoNotification\Application\EventHandler\OnJoinRequestRejected;
use Concrete\Package\InnoNotification\Application\EventHandler\OnMemberInvited;
use Concrete\Package\InnoNotification\Application\EventHandler\OnMemberLeft;
use Concrete\Package\InnoNotification\Application\EventHandler\OnMemberRemoved;
use Concrete\Package\InnoNotification\Application\EventHandler\OnModerationFeedbackRequested;
use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaApproved;
use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaRejected;
use Concrete\Package\InnoNotification\Application\EventHandler\OnProjectIdeaSubmitted;
use Concrete\Package\InnoNotification\Application\EventHandler\OnStatusUpdatePublished;
use Concrete\Package\InnoNotification\Application\EventHandler\OnUserAccountDeactivated;
use Concrete\Package\InnoNotification\Application\EventHandler\OnUserFlaggedAsExpert;
use Concrete\Package\InnoNotification\Application\EventHandler\OnUserRegistered;
use Concrete\Package\InnoNotification\Application\EventHandler\OnUserUnflaggedAsExpert;
use Concrete\Package\InnoNotification\Port\Out\EmailServiceInterface;
use Concrete\Package\InnoShared\Infrastructure\Adapter\User\ConcreteCmsUserResolver;
use Concrete\Package\InnoShared\Port\Out\UserResolverPort;

class Controller extends Package
{
    protected $pkgHandle = 'inno_notification';
    protected $appVersionRequired = '9.5.0';
    protected $pkgVersion = '1.2.0';

    public function getPackageDescription(): string
    {
        return t('Sends email and system notifications in response to domain events.');
    }

    public function getPackageName(): string
    {
        return t('Innovationsbox – Notification');
    }

    public function on_start(): void
    {
        $this->app->bind(EmailServiceInterface::class, ConcreteEmailService::class);
        $this->app->bind(UserResolverPort::class, ConcreteCmsUserResolver::class);

        // REST API — allows users to read/mark their notifications
        $router = $this->app->make(Router::class);
        $router->register('/api/notification', [NotificationApiController::class, 'index'], 'api.notification.index', ['GET']);
        $router->register('/api/notification/{id}/read', [NotificationApiController::class, 'markRead'], 'api.notification.mark_read', ['POST']);

        // Fan-out: listen to events from all other contexts and trigger notifications
        Events::addListener(
            'innovationsbox.project_idea.submitted',
            [$this->app->make(OnProjectIdeaSubmitted::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.project_idea.approved',
            [$this->app->make(OnProjectIdeaApproved::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.project_idea.rejected',
            [$this->app->make(OnProjectIdeaRejected::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.moderation.feedback_requested',
            [$this->app->make(OnModerationFeedbackRequested::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.project_space.join_request_received',
            [$this->app->make(OnJoinRequestReceived::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.project_space.join_request_accepted',
            [$this->app->make(OnJoinRequestAccepted::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.project_space.join_request_rejected',
            [$this->app->make(OnJoinRequestRejected::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.project_space.member_invited',
            [$this->app->make(OnMemberInvited::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.expert.requested',
            [$this->app->make(OnExpertRequested::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.expert.request_accepted',
            [$this->app->make(OnExpertRequestAccepted::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.expert.request_declined',
            [$this->app->make(OnExpertRequestDeclined::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.project_space.status_update_published',
            [$this->app->make(OnStatusUpdatePublished::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.project_space.member_left',
            [$this->app->make(OnMemberLeft::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.project_space.member_removed',
            [$this->app->make(OnMemberRemoved::class), 'handle']
        );
        Events::addListener(
            'on_user_add',
            [$this->app->make(OnUserRegistered::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.user.flagged_as_expert',
            [$this->app->make(OnUserFlaggedAsExpert::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.user.unflagged_as_expert',
            [$this->app->make(OnUserUnflaggedAsExpert::class), 'handle']
        );
        Events::addListener(
            'innovationsbox.user.account_deactivated',
            [$this->app->make(OnUserAccountDeactivated::class), 'handle']
        );
    }

    public function install(): void
    {
        parent::install();
        $this->setDefaultConfig();
    }

    public function upgrade(): void
    {
        parent::upgrade();
        $this->setDefaultConfig();
    }

    private function setDefaultConfig(): void
    {
        // notification.admin_email is no longer used — notifications go to all Moderator group members.
        // Clean up leftover config from previous versions.
        $config = $this->app->make(Repository::class);
        if ($config->has('innovationsbox.notification.admin_email')) {
            $config->save('innovationsbox.notification.admin_email', null);
        }
    }
}
