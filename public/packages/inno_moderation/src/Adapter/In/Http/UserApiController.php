<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Core\User\Group\Group;
use Concrete\Core\User\User;
use Concrete\Core\User\UserInfo;
use Concrete\Core\User\UserList;
use Concrete\Package\InnoShared\Domain\Event\UserAccountDeactivated;
use Concrete\Package\InnoShared\Domain\Event\UserFlaggedAsExpert;
use Concrete\Package\InnoShared\Domain\Event\UserUnflaggedAsExpert;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Concrete\Package\InnoShared\Infrastructure\Http\CsrfValidationTrait;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UserApiController extends AbstractController
{
    use CsrfValidationTrait;

    public function __construct(
        private readonly AuthInterface $auth,
        private readonly ExceptionHandlerInterface $exceptionHandler,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    #[OA\Get(
        path: '/moderation/user',
        summary: 'List all users with expert status',
        description: 'Returns all active users including their expert flag. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['User Management'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of users',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'string'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'email', type: 'string', format: 'email'),
                            new OA\Property(property: 'isExpert', type: 'boolean'),
                        ],
                    ),
                ),
            ],
        ),
    )]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden — missing moderation.review permission', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function index(): JsonResponse
    {
        try {
            $this->requirePermission('moderation.review');

            $experteGroup = Group::getByName('Experte');
            $expertUserIds = [];
            if ($experteGroup) {
                foreach ($experteGroup->getGroupMembers() as $member) {
                    $expertUserIds[] = (string) $member->getUserID();
                }
            }

            $userList = new UserList();
            $userList->filterByIsActive(1);
            $userList->sortByUserName();
            $users = [];

            foreach ($userList->getResults() as $ui) {
                $userId = (string) $ui->getUserID();
                $users[] = [
                    'id' => $userId,
                    'name' => $ui->getAttribute('display_name') ?: $ui->getUserName(),
                    'email' => $ui->getUserEmail(),
                    'isExpert' => in_array($userId, $expertUserIds, true),
                ];
            }

            return new JsonResponse(['data' => $users]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Put(
        path: '/moderation/user/{id}/expert',
        summary: 'Toggle expert flag for a user',
        description: 'Adds or removes the user from the Experte group, dispatching the appropriate event. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['User Management'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'User ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['expert'],
            properties: [
                new OA\Property(property: 'expert', type: 'boolean', description: 'Set to true to grant expert status, false to revoke'),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Expert status updated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'updated'),
                new OA\Property(property: 'isExpert', type: 'boolean'),
            ],
        ),
    )]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 500, description: 'Experte group not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function toggleExpert(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->requirePermission('moderation.review');

            $body = json_decode($request->getContent(), true) ?? [];
            $makeExpert = (bool) ($body['expert'] ?? false);

            $ui = UserInfo::getByID((int) $id);
            if ($ui === null) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            $experteGroup = Group::getByName('Experte');
            if ($experteGroup === null) {
                return new JsonResponse(['error' => 'Experte group not found'], 500);
            }

            $user = User::getByUserID((int) $id);
            $currentUserId = (string) $this->auth->getUserId();

            if ($makeExpert) {
                $user->enterGroup($experteGroup);
                $this->eventDispatcher->dispatch(
                    new UserFlaggedAsExpert($id, $currentUserId),
                    UserFlaggedAsExpert::NAME,
                );
            } else {
                $user->exitGroup($experteGroup);
                $this->eventDispatcher->dispatch(
                    new UserUnflaggedAsExpert($id, $currentUserId),
                    UserUnflaggedAsExpert::NAME,
                );
            }

            return new JsonResponse(['status' => 'updated', 'isExpert' => $makeExpert]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    public function deactivateUser(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->requirePermission('moderation.review');

            $body = json_decode($request->getContent(), true) ?? [];
            $reason = trim((string) ($body['reason'] ?? ''));
            if ($reason === '') {
                return new JsonResponse(['error' => 'Reason is required'], 400);
            }

            $ui = UserInfo::getByID((int) $id);
            if ($ui === null) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            if (!$ui->isActive()) {
                return new JsonResponse(['error' => 'User is already deactivated'], 409);
            }

            $currentUserId = (string) $this->auth->getUserId();

            $ui->deactivate();

            $this->eventDispatcher->dispatch(
                new UserAccountDeactivated(
                    $id,
                    $ui->getUserEmail(),
                    $ui->getAttribute('display_name') ?: $ui->getUserName(),
                    $reason,
                    $currentUserId,
                ),
                UserAccountDeactivated::NAME,
            );

            return new JsonResponse(['status' => 'deactivated'], 200);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    public function deactivateOwnAccount(Request $request): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);

            if (!$this->auth->isAuthenticated()) {
                throw new AuthException('Not authenticated', [], false);
            }

            $body = json_decode($request->getContent(), true) ?? [];
            $password = (string) ($body['password'] ?? '');
            if ($password === '') {
                return new JsonResponse(['error' => 'Password is required'], 400);
            }

            $userId = (string) $this->auth->getUserId();
            $ui = UserInfo::getByID((int) $userId);
            if ($ui === null) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            // Verify password
            if (!$ui->passwordMatches($password)) {
                return new JsonResponse(['error' => 'Invalid password'], 403);
            }

            $ui->deactivate();

            $this->eventDispatcher->dispatch(
                new UserAccountDeactivated(
                    $userId,
                    $ui->getUserEmail(),
                    $ui->getAttribute('display_name') ?: $ui->getUserName(),
                    '',
                    $userId,
                ),
                UserAccountDeactivated::NAME,
            );

            // Log the user out after deactivation
            $user = User::getByUserID((int) $userId);
            $user->logout();

            return new JsonResponse(['status' => 'deactivated'], 200);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    public function counts(): JsonResponse
    {
        try {
            if (!$this->auth->isAuthenticated()) {
                throw new AuthException('Not authenticated', [], false);
            }

            $userId = (int) $this->auth->getUserId();
            $countsQuery = $this->app->make(\Concrete\Package\InnoModeration\Adapter\Out\Persistence\UserCountsQuery::class);

            $user = User::getByUserID($userId);
            $isModerator = $this->auth->hasPermission('moderation.review');
            $isExpert = false;
            $experteGroup = Group::getByName('Experte');
            if ($experteGroup && $user) {
                $isExpert = $user->inGroup($experteGroup);
            }

            $counts = $countsQuery->getCounts($userId, $isModerator, $isExpert);

            return new JsonResponse($counts);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    private function requirePermission(string $permission): void
    {
        if (!$this->auth->hasPermission($permission)) {
            throw new AuthException(
                "Permission denied for {$permission}",
                ['permission' => $permission],
                $this->auth->isAuthenticated(),
            );
        }
    }
}
