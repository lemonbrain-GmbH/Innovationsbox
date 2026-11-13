<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectSpace\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoProjectSpace\Application\UseCase\AddCollaboardLink\AddCollaboardLinkCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\ChangeSpaceStatus\ChangeSpaceStatusCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\DeleteProjectSpace\DeleteProjectSpaceCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleExpertRequest\HandleExpertRequestCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\HandleJoinRequest\HandleJoinRequestCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\InviteMember\InviteMemberCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\LeaveSpace\LeaveSpaceCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveCollaboardLink\RemoveCollaboardLinkCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RemoveMember\RemoveMemberCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestExpert\RequestExpertCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\RequestToJoin\RequestToJoinCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\SetSpacePriority\SetSpacePriorityCommand;
use Concrete\Package\InnoProjectSpace\Application\UseCase\WriteStatusUpdate\WriteStatusUpdateCommand;
use Concrete\Package\InnoProjectSpace\Domain\Exception\ProjectSpaceNotFoundException;
use Concrete\Package\InnoProjectSpace\Domain\Model\ExpertRequestStatus;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;
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
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Http\CsrfValidationTrait;
use Concrete\Package\InnoShared\Port\CategoryReaderPort;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProjectSpaceApiController extends AbstractController
{
    use CsrfValidationTrait;

    public function __construct(
        private readonly ProjectSpaceRepositoryInterface $repository,
        private readonly AuthInterface $auth,
        private readonly AddCollaboardLinkPort $addCollaboardLinkPort,
        private readonly RemoveCollaboardLinkPort $removeCollaboardLinkPort,
        private readonly WriteStatusUpdatePort $writeStatusUpdatePort,
        private readonly RequestToJoinPort $requestToJoinPort,
        private readonly HandleJoinRequestPort $handleJoinRequestPort,
        private readonly ChangeSpaceStatusPort $changeSpaceStatusPort,
        private readonly InviteMemberPort $inviteMemberPort,
        private readonly RequestExpertPort $requestExpertPort,
        private readonly HandleExpertRequestPort $handleExpertRequestPort,
        private readonly LeaveSpacePort $leaveSpacePort,
        private readonly RemoveMemberPort $removeMemberPort,
        private readonly CategoryReaderPort $categoryReader,
        private readonly ExceptionHandlerInterface $exceptionHandler,
        private readonly ProjectIdeaRepositoryInterface $ideaRepository,
        private readonly UpdateHeaderImagePort $updateHeaderImagePort,
        private readonly DeleteProjectSpacePort $deleteSpacePort,
        private readonly SetSpacePriorityPort $setSpacePriorityPort,
    ) {}

    #[OA\Get(
        path: '/project-space/categories',
        summary: 'List available categories',
        description: 'Returns a list of all categories. No authentication required.',
        tags: ['Project Spaces'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of categories',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'slug', type: 'string'),
                    ],
                )),
            ],
        ),
    )]
    public function categories(): JsonResponse
    {
        return new JsonResponse(['data' => $this->categoryReader->findAllCategories()]);
    }

    #[OA\Get(
        path: '/project-space/latest-updates',
        summary: 'Latest status updates across all active spaces',
        description: 'Returns the most recent status updates across all active project spaces. No authentication required.',
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 2))]
    #[OA\Response(
        response: 200,
        description: 'List of latest status updates',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'updateId', type: 'string'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'bodyPreview', type: 'string'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'spaceId', type: 'string'),
                        new OA\Property(property: 'spaceTitle', type: 'string'),
                    ],
                )),
            ],
        ),
    )]
    public function latestUpdates(Request $request): JsonResponse
    {
        $limit = min(10, max(1, (int) ($request->query->get('limit', 2))));
        $rows = $this->repository->findLatestUpdates($limit);

        return new JsonResponse([
            'data' => array_map(function (array $row) {
                $bodyPlain = strip_tags($row['body']);
                $preview = mb_strlen($bodyPlain) > 150
                    ? mb_substr($bodyPlain, 0, 150) . '...'
                    : $bodyPlain;

                return [
                    'updateId' => $row['updateId'],
                    'title' => $row['title'],
                    'bodyPreview' => $preview,
                    'createdAt' => (new \DateTimeImmutable($row['createdAt']))->format('c'),
                    'spaceId' => $row['spaceId'],
                    'spaceTitle' => $row['spaceTitle'],
                ];
            }, $rows),
        ]);
    }

    #[OA\Get(
        path: '/project-space',
        summary: 'List public project spaces',
        description: 'Returns a paginated list of public project spaces with card data. No authentication required.',
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'active', 'completed', 'archived']))]
    #[OA\Parameter(name: 'category', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'mine', in: 'query', required: false, description: 'Filter to spaces where the current user is initiator or member', schema: new OA\Schema(type: 'boolean', default: false))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 12))]
    #[OA\Response(
        response: 200,
        description: 'Paginated list of project spaces',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProjectSpaceCard')),
                new OA\Property(property: 'total', type: 'integer'),
                new OA\Property(property: 'page', type: 'integer'),
                new OA\Property(property: 'limit', type: 'integer'),
            ],
        ),
    )]
    public function index(Request $request): JsonResponse
    {
        $statusParam = $request->query->get('status');
        $status = $statusParam !== null ? SpaceStatus::from($statusParam) : null;
        $categorySlug = $request->query->get('category');
        $search = $request->query->get('search');
        $mine = $request->query->get('mine') === 'true';
        $page = max(1, (int) ($request->query->get('page', 1)));
        $limit = min(100, max(1, (int) ($request->query->get('limit', 12))));
        $isAdmin = $this->auth->hasPermission('space.admin');

        // Only admins can see archived spaces
        if ($status === SpaceStatus::Archived && !$isAdmin) {
            $status = null;
        }

        $memberId = null;
        if ($mine && $this->auth->isAuthenticated()) {
            $memberId = (string) $this->auth->getUserId();
        }

        $categoryMap = [];
        foreach ($this->categoryReader->findAllCategories() as $cat) {
            $categoryMap[$cat['id']] = $cat;
        }

        $result = $this->repository->findPublic(
            $status,
            $page,
            $limit,
            $categorySlug !== null ? (string) $categorySlug : null,
            $search !== null ? (string) $search : null,
            $memberId,
        );

        return new JsonResponse([
            'data' => array_map(
                function ($space) use ($categoryMap) {
                    $catInfo = $categoryMap[$space->getCategoryId()] ?? null;

                    return [
                        'id' => $space->getId(),
                        'title' => $space->getTitle(),
                        'description' => $space->getDescription(),
                        'status' => $space->getStatus()->value,
                        'categoryId' => $space->getCategoryId(),
                        'categoryName' => $catInfo['name'] ?? '',
                        'categorySlug' => $catInfo['slug'] ?? '',
                        'initiatorId' => $space->getInitiatorId(),
                        'initiatorName' => $this->resolveUserName($space->getInitiatorId()),
                        'initiatorAvatarUrl' => $this->resolveUserAvatar($space->getInitiatorId()),
                        'memberCount' => count($space->getMemberIds()),
                        'latestUpdate' => !empty($space->getStatusUpdates()) ? [
                            'title' => $space->getStatusUpdates()[0]->getTitle(),
                            'createdAt' => $space->getStatusUpdates()[0]->getCreatedAt()->format('c'),
                        ] : null,
                        'createdAt' => $space->getCreatedAt()->format('c'),
                        'userRole' => $this->getUserRole($space),
                        'headerImageUrl' => $space->getHeaderImageFileId() !== null
                            ? $this->resolveFileUrl($space->getHeaderImageFileId())
                            : '',
                    ];
                },
                $result['data'],
            ),
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[OA\Get(
        path: '/project-space/{id}',
        summary: 'Get project space details',
        description: 'Returns details of a single project space. Response shape depends on the caller\'s role: public visitors get basic info; members also get collaboard links, status updates, and member list; initiators and admins additionally see pending membership requests.',
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Space details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/ProjectSpaceDetail'),
            ],
        ),
    )]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function show(Request $request, string $id): JsonResponse
    {
        $space = $this->repository->findById($id);
        if ($space === null) {
            throw ProjectSpaceNotFoundException::withId($id);
        }

        $userRole = $this->getUserRole($space);

        $categoryMap = [];
        foreach ($this->categoryReader->findAllCategories() as $cat) {
            $categoryMap[$cat['id']] = $cat;
        }
        $catInfo = $categoryMap[$space->getCategoryId()] ?? null;

        $data = [
            'id' => $space->getId(),
            'title' => $space->getTitle(),
            'description' => $space->getDescription(),
            'status' => $space->getStatus()->value,
            'categoryId' => $space->getCategoryId(),
            'categoryName' => $catInfo['name'] ?? '',
            'categorySlug' => $catInfo['slug'] ?? '',
            'initiatorId' => $space->getInitiatorId(),
            'initiatorName' => $this->resolveUserName($space->getInitiatorId()),
            'initiatorAvatarUrl' => $this->resolveUserAvatar($space->getInitiatorId()),
            'memberCount' => count($space->getMemberIds()),
            'createdAt' => $space->getCreatedAt()->format('c'),
            'updatedAt' => $space->getUpdatedAt()->format('c'),
            'userRole' => $userRole,
            'currentUserId' => $this->auth->isAuthenticated() ? (string) $this->auth->getUserId() : null,
            'headerImageUrl' => $space->getHeaderImageFileId() !== null
                ? $this->resolveFileUrl($space->getHeaderImageFileId())
                : '',
            'priority' => $space->getPriority(),
        ];

        // Status updates are public
        $data['statusUpdates'] = array_map(
            fn($update) => [
                'id' => $update->getId(),
                'title' => $update->getTitle(),
                'body' => $update->getBody(),
                'authorId' => $update->getAuthorId(),
                'createdAt' => $update->getCreatedAt()->format('c'),
                'attachments' => array_map(fn($a) => [
                    'id' => $a->getId(),
                    'filename' => $a->getFilename(),
                    'mimeType' => $a->getMimeType(),
                    'size' => $a->getSize(),
                    'isImage' => $a->isImage(),
                    'url' => $this->resolveFileUrl($a->getFileManagerId()),
                ], $update->getAttachments()),
            ],
            $space->getStatusUpdates(),
        );

        // Collaboards and members are member-only
        if ($userRole === 'initiator' || $userRole === 'member' || $userRole === 'admin') {
            $data['collaboardLinks'] = array_map(
                fn($link) => [
                    'id' => $link->getId(),
                    'title' => $link->getTitle(),
                    'url' => $link->getUrl(),
                    'addedAt' => $link->getAddedAt()->format('c'),
                ],
                $space->getCollaboardLinks(),
            );
            $data['members'] = $this->resolveMembers($space->getMemberIds(), $space->getInitiatorId());
        }

        if ($userRole === 'initiator' || $userRole === 'admin') {
            $data['membershipRequests'] = array_map(
                fn($req) => [
                    'id' => $req->getId(),
                    'userId' => $req->getUserId(),
                    'userName' => $this->resolveUserName($req->getUserId()),
                    'status' => $req->getStatus()->value,
                    'requestedAt' => $req->getRequestedAt()->format('c'),
                    'decidedAt' => $req->getDecidedAt()?->format('c'),
                ],
                $space->getMembershipRequests(),
            );

            $data['expertRequests'] = array_map(
                fn($req) => [
                    'id' => $req->getId(),
                    'expertUserId' => $req->getExpertUserId(),
                    'expertName' => $this->resolveUserName($req->getExpertUserId()),
                    'expertExpertise' => $this->resolveUserExpertise($req->getExpertUserId()),
                    'message' => $req->getMessage(),
                    'status' => $req->getStatus()->value,
                    'requestedAt' => $req->getRequestedAt()->format('c'),
                    'decidedAt' => $req->getDecidedAt()?->format('c'),
                ],
                $space->getExpertRequests(),
            );
        }

        // Include original idea attachments
        $ideaId = $space->getProjectIdeaId();
        if ($ideaId !== '') {
            try {
                $idea = $this->ideaRepository->findById($ideaId);
                if ($idea !== null && count($idea->getAttachments()) > 0) {
                    $data['ideaAttachments'] = array_map(fn($a) => [
                        'id' => $a->getId(),
                        'filename' => $a->getFilename(),
                        'mimeType' => $a->getMimeType(),
                        'size' => $a->getSize(),
                        'isImage' => $a->isImage(),
                        'url' => $this->resolveFileUrl($a->getFileManagerId()),
                    ], $idea->getAttachments());
                }
            } catch (\Throwable) {
                // idea package may not be installed
            }
        }

        return new JsonResponse(['data' => $data]);
    }

    private function resolveFileUrl(int $fileManagerId): string
    {
        try {
            $file = \Concrete\Core\File\File::getByID($fileManagerId);
            if ($file) {
                $version = $file->getApprovedVersion();
                if ($version) {
                    return (string) $version->getURL();
                }
            }
        } catch (\Throwable) {
        }

        return '';
    }

    #[OA\Post(
        path: '/project-space/{id}/collaboard',
        summary: 'Add a Collaboard link',
        description: 'Adds a Collaboard collaboration link to the project space.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'url', type: 'string', format: 'uri'),
            ],
        ),
    )]
    #[OA\Response(response: 201, description: 'Collaboard link added', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function addCollaboard(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $body = json_decode($request->getContent(), true) ?? [];

            $v = new \Concrete\Package\InnoShared\Infrastructure\Validation\InputValidator($body);
            $title = $v->requireString('title', 1, 255);
            $url = $v->requireString('url', 1, 2000);

            $link = $this->addCollaboardLinkPort->handle(new AddCollaboardLinkCommand(
                spaceId: $id,
                title: $title,
                url: $url,
            ));

            return new JsonResponse(['data' => ['id' => $link->getId()]], 201);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Delete(
        path: '/project-space/{id}/collaboard/{linkId}',
        summary: 'Remove a Collaboard link',
        description: 'Removes a Collaboard collaboration link from the project space.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'linkId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Collaboard link removed', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function removeCollaboard(Request $request, string $id, string $linkId): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->removeCollaboardLinkPort->handle(new RemoveCollaboardLinkCommand(
                spaceId: $id,
                linkId: $linkId,
            ));

            return new JsonResponse(['status' => 'removed']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Post(
        path: '/project-space/{id}/status-update',
        summary: 'Post a status update',
        description: 'Adds a status update to the project space.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'body', type: 'string'),
            ],
        ),
    )]
    #[OA\Response(response: 201, description: 'Status update created', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function addStatusUpdate(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $contentType = $request->headers->get('Content-Type', '');
            if (str_contains($contentType, 'application/json')) {
                $body = json_decode($request->getContent(), true) ?? [];
            } else {
                $body = $request->request->all();
            }

            $v = new \Concrete\Package\InnoShared\Infrastructure\Validation\InputValidator($body);
            $title = $v->requireString('title', 1, 255);
            $bodyText = $v->requireString('body', 1, 10000);

            $update = $this->writeStatusUpdatePort->handle(new WriteStatusUpdateCommand(
                spaceId: $id,
                title: $title,
                body: $bodyText,
            ));

            // Import uploaded files and persist directly
            $this->importUpdateFiles($request, $update);
            if (count($update->getAttachments()) > 0) {
                $db = $this->app->make('database')->connection();
                foreach ($update->getAttachments() as $att) {
                    $db->insert('ib_update_attachments', [
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

            return new JsonResponse(['data' => ['id' => $update->getId()]], 201);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Put(
        path: '/project-space/{id}/status-update/{updateId}',
        summary: 'Edit a status update',
        description: 'Edits an existing status update. Only the space initiator or admin can edit.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'updateId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'body', type: 'string'),
        ],
    ))]
    #[OA\Response(response: 200, description: 'Update edited', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space or update not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function editStatusUpdate(Request $request, string $id, string $updateId): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            if (!$this->auth->isAuthenticated()) {
                throw new \Concrete\Package\InnoShared\Infrastructure\Exception\AuthException('Authentication required', [], false);
            }

            $space = $this->repository->findById($id);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($id);
            }

            if ($space->getInitiatorId() !== (string) $this->auth->getUserId() && !$this->auth->hasPermission('space.admin')) {
                throw new \Concrete\Package\InnoShared\Infrastructure\Exception\AuthException('Only the space initiator can edit updates', [], true);
            }

            $contentType = $request->headers->get('Content-Type', '');
            if (str_contains($contentType, 'application/json')) {
                $body = json_decode($request->getContent(), true) ?? [];
            } else {
                $body = $request->request->all();
            }
            $v = new \Concrete\Package\InnoShared\Infrastructure\Validation\InputValidator($body);
            $title = $v->requireString('title', 1, 255);
            $bodyText = $v->requireString('body', 1, 10000);
            $sanitizedBody = strip_tags($bodyText, '<p><br><strong><em><ul><ol><li><a><h2><h3><h4>');
            $space->editStatusUpdate($updateId, $title, $sanitizedBody);

            // Remove files if requested
            $removeFiles = $body['removeFiles'] ?? [];
            if (is_array($removeFiles)) {
                $update = $this->findStatusUpdate($space, $updateId);
                if ($update !== null) {
                    foreach ($removeFiles as $attId) {
                        $update->removeAttachment((string) $attId);
                    }
                }
            }

            // Import new files
            $update = $this->findStatusUpdate($space, $updateId);
            if ($update !== null) {
                $this->importUpdateFiles($request, $update);
            }

            $this->repository->save($space);

            return new JsonResponse(['status' => 'updated']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Delete(
        path: '/project-space/{id}/status-update/{updateId}',
        summary: 'Delete a status update',
        description: 'Deletes a status update. Only the space initiator or admin can delete.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'updateId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Update deleted', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space or update not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function deleteStatusUpdate(Request $request, string $id, string $updateId): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            if (!$this->auth->isAuthenticated()) {
                throw new \Concrete\Package\InnoShared\Infrastructure\Exception\AuthException('Authentication required', [], false);
            }

            $space = $this->repository->findById($id);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($id);
            }

            if ($space->getInitiatorId() !== (string) $this->auth->getUserId() && !$this->auth->hasPermission('space.admin')) {
                throw new \Concrete\Package\InnoShared\Infrastructure\Exception\AuthException('Only the space initiator can delete updates', [], true);
            }

            $space->deleteStatusUpdate($updateId);
            $this->repository->save($space);

            return new JsonResponse(['status' => 'deleted']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Post(
        path: '/project-space/{id}/join-request',
        summary: 'Request to join a project space',
        description: 'Submits a membership request for the current user.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 201, description: 'Join request submitted', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function joinRequest(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $membershipRequest = $this->requestToJoinPort->handle(new RequestToJoinCommand(
                spaceId: $id,
            ));

            return new JsonResponse(['data' => ['id' => $membershipRequest->getId()]], 201);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Put(
        path: '/project-space/{id}/join-request/{requestId}',
        summary: 'Accept or reject a join request',
        description: 'Allows the space initiator or an admin to accept or reject a pending membership request.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'requestId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'accept', type: 'boolean'),
            ],
        ),
    )]
    #[OA\Response(response: 200, description: 'Join request handled', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function handleJoinRequest(Request $request, string $id, string $requestId): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $body = json_decode($request->getContent(), true) ?? [];

            $this->handleJoinRequestPort->handle(new HandleJoinRequestCommand(
                spaceId: $id,
                requestId: $requestId,
                accept: (bool) ($body['accept'] ?? false),
            ));

            return new JsonResponse(['status' => 'handled']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Put(
        path: '/project-space/{id}/status',
        summary: 'Change the space status',
        description: 'Transitions a project space to a new status. Valid transitions: draft → active → completed → archived.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'archived']),
            ],
        ),
    )]
    #[OA\Response(response: 200, description: 'Status updated', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 400, description: 'Invalid transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function changeStatus(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $body = json_decode($request->getContent(), true) ?? [];

            $this->changeSpaceStatusPort->handle(new ChangeSpaceStatusCommand(
                spaceId: $id,
                newStatus: $body['status'] ?? '',
            ));

            return new JsonResponse(['status' => 'updated']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Post(
        path: '/project-space/{id}/invite',
        summary: 'Invite a user to a project space',
        description: 'Directly invites a user to become a member of the project space.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'userId', type: 'string'),
            ],
        ),
    )]
    #[OA\Response(response: 201, description: 'User invited', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function invite(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $body = json_decode($request->getContent(), true) ?? [];

            $this->inviteMemberPort->handle(new InviteMemberCommand(
                spaceId: $id,
                userId: $body['userId'] ?? '',
            ));

            return new JsonResponse(['status' => 'invited'], 201);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Get(
        path: '/project-space/{id}/experts',
        summary: 'List available experts',
        description: 'Returns experts from the Experte group who are not already members or have pending requests.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'List of available experts',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'userId', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'expertise', type: 'string'),
                    ],
                )),
            ],
        ),
    )]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function listExperts(Request $request, string $id): JsonResponse
    {
        try {
            if (!$this->auth->isAuthenticated()) {
                throw new \Concrete\Package\InnoShared\Infrastructure\Exception\AuthException('Authentication required', [], false);
            }

            $space = $this->repository->findById($id);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($id);
            }

            // Get experts from CMS group
            $group = \Concrete\Core\User\Group\Group::getByName('Experte');
            $experts = [];
            if ($group) {
                $memberIds = $space->getMemberIds();
                $pendingExpertIds = array_map(
                    fn($r) => $r->getExpertUserId(),
                    array_filter($space->getExpertRequests(), fn($r) => $r->getStatus() === ExpertRequestStatus::Pending)
                );

                foreach ($group->getGroupMembers() as $member) {
                    $userId = (string) $member->getUserID();
                    if (in_array($userId, $memberIds, true) || in_array($userId, $pendingExpertIds, true)) {
                        continue;
                    }
                    $ui = \Concrete\Core\User\UserInfo::getByID((int) $userId);
                    $experts[] = [
                        'userId' => $userId,
                        'name' => $ui ? $ui->getAttribute('display_name') ?: $ui->getUserName() : 'Benutzer ' . $userId,
                        'expertise' => $ui ? (string) $ui->getAttribute('expertise_area') : '',
                    ];
                }
            }

            return new JsonResponse(['data' => $experts]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Post(
        path: '/project-space/{id}/expert-request',
        summary: 'Request an expert',
        description: 'Sends an expert request to join the project space.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['expertUserId'],
        properties: [
            new OA\Property(property: 'expertUserId', type: 'string'),
            new OA\Property(property: 'message', type: 'string', nullable: true),
        ],
    ))]
    #[OA\Response(response: 201, description: 'Expert request sent', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function requestExpert(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $body = json_decode($request->getContent(), true) ?? [];
            $this->requestExpertPort->handle(new RequestExpertCommand(
                spaceId: $id,
                expertUserId: $body['expertUserId'] ?? '',
                message: isset($body['message']) ? (string) $body['message'] : null,
            ));

            return new JsonResponse(['status' => 'requested'], 201);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Put(
        path: '/project-space/{id}/expert-request/{requestId}',
        summary: 'Accept or decline an expert request',
        description: 'Allows the expert to accept or decline the request.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'requestId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'accept', type: 'boolean'),
        ],
    ))]
    #[OA\Response(response: 200, description: 'Expert request handled', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 404, description: 'Space or request not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function handleExpertRequest(Request $request, string $id, string $requestId): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $body = json_decode($request->getContent(), true) ?? [];
            $this->handleExpertRequestPort->handle(new HandleExpertRequestCommand(
                spaceId: $id,
                requestId: $requestId,
                accept: (bool) ($body['accept'] ?? false),
            ));

            return new JsonResponse(['status' => 'handled']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Post(
        path: '/project-space/{id}/leave',
        summary: 'Leave a project space',
        description: 'Current user leaves the project space.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Left the space', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function leaveSpace(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->leaveSpacePort->handle(new LeaveSpaceCommand(spaceId: $id));

            return new JsonResponse(['status' => 'left']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Delete(
        path: '/project-space/{id}/members/{userId}',
        summary: 'Remove a member from a project space',
        description: 'Removes a member. Only the space initiator or admin can remove members.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Member removed', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function removeMember(Request $request, string $id, string $userId): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->removeMemberPort->handle(new RemoveMemberCommand(spaceId: $id, userId: $userId));

            return new JsonResponse(['status' => 'removed']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Post(
        path: '/project-space/{id}/header-image',
        summary: 'Upload a header image for a project space',
        description: 'Uploads and sets the header image on a project space. Only the initiator, moderator, or admin may call this.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: 'headerImage', type: 'string', format: 'binary'),
                ],
            ),
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Header image updated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'headerImageUrl', type: 'string'),
            ],
        ),
    )]
    #[OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function uploadHeaderImage(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);

            if (!$this->auth->isAuthenticated()) {
                throw new \Concrete\Package\InnoShared\Infrastructure\Exception\AuthException('Authentication required', [], false);
            }

            $space = $this->repository->findById($id);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($id);
            }

            $userId = (string) $this->auth->getUserId();
            $isInitiator = $userId === $space->getInitiatorId();
            $isAdmin = $this->auth->hasPermission('space.admin');
            $isModerator = $this->auth->hasPermission('space.moderate');

            if (!$isInitiator && !$isAdmin && !$isModerator) {
                throw new \Concrete\Package\InnoShared\Infrastructure\Exception\AuthException('Only the initiator, moderator, or admin can update the header image', [], true);
            }

            $file = $request->files->get('headerImage');
            if (!($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) || !$file->isValid()) {
                return new JsonResponse(['error' => ['message' => 'No valid file uploaded.']], 422);
            }

            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file->getMimeType(), $allowedMimes, true)) {
                return new JsonResponse(['error' => ['message' => 'Header image must be JPEG, PNG, GIF, or WebP.']], 422);
            }

            if ($file->getSize() > 10 * 1024 * 1024) {
                return new JsonResponse(['error' => ['message' => 'Header image must be at most 10 MB.']], 422);
            }

            $imageSize = @getimagesize($file->getPathname());
            if ($imageSize === false || $imageSize[0] < 800) {
                return new JsonResponse(['error' => ['message' => 'Header image must be at least 800px wide.']], 422);
            }

            $importer = new \Concrete\Core\File\Importer();
            $fileVersion = $importer->importUploadedFile($file);
            if (!($fileVersion instanceof \Concrete\Core\Entity\File\Version)) {
                return new JsonResponse(['error' => ['message' => 'Failed to import the uploaded file.']], 500);
            }

            $fileId = (int) $fileVersion->getFile()->getFileID();
            $this->updateHeaderImagePort->updateHeaderImage($id, $fileId);

            return new JsonResponse(['headerImageUrl' => $this->resolveFileUrl($fileId)]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Delete(
        path: '/project-space/{id}/header-image',
        summary: 'Delete the header image of a project space',
        description: 'Removes the header image from a project space. Only the initiator, moderator, or admin may call this.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Header image removed', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function deleteHeaderImage(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);

            if (!$this->auth->isAuthenticated()) {
                throw new \Concrete\Package\InnoShared\Infrastructure\Exception\AuthException('Authentication required', [], false);
            }

            $space = $this->repository->findById($id);
            if ($space === null) {
                throw ProjectSpaceNotFoundException::withId($id);
            }

            $userId = (string) $this->auth->getUserId();
            $isInitiator = $userId === $space->getInitiatorId();
            $isAdmin = $this->auth->hasPermission('space.admin');
            $isModerator = $this->auth->hasPermission('space.moderate');

            if (!$isInitiator && !$isAdmin && !$isModerator) {
                throw new \Concrete\Package\InnoShared\Infrastructure\Exception\AuthException('Only the initiator, moderator, or admin can remove the header image', [], true);
            }

            $this->updateHeaderImagePort->updateHeaderImage($id, null);

            return new JsonResponse(['status' => 'deleted']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Delete(
        path: '/project-space/{id}',
        summary: 'Delete an archived project space',
        description: 'Permanently deletes an archived project space. Only administrators can delete.',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Space deleted', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Space not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function deleteSpace(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->deleteSpacePort->handle(new DeleteProjectSpaceCommand(spaceId: $id));

            return new JsonResponse(['status' => 'deleted']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Get(
        path: '/expert-request/mine',
        summary: 'List my expert requests',
        description: 'Returns expert requests for the current user (pending or decided within last 90 days).',
        security: [['cookieAuth' => []]],
        tags: ['Project Spaces'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of expert requests',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'string'),
                        new OA\Property(property: 'spaceId', type: 'string'),
                        new OA\Property(property: 'spaceTitle', type: 'string'),
                        new OA\Property(property: 'requestedByName', type: 'string'),
                        new OA\Property(property: 'message', type: 'string', nullable: true),
                        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'accepted', 'declined']),
                        new OA\Property(property: 'requestedAt', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'decidedAt', type: 'string', format: 'date-time', nullable: true),
                    ],
                )),
            ],
        ),
    )]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function myExpertRequests(Request $request): JsonResponse
    {
        try {
            if (!$this->auth->isAuthenticated()) {
                throw new \Concrete\Package\InnoShared\Infrastructure\Exception\AuthException('Authentication required', [], false);
            }

            $userId = (string) $this->auth->getUserId();
            $db = $this->app->make('database')->connection();
            $cutoff = (new \DateTimeImmutable())->modify('-90 days')->format('Y-m-d H:i:s');
            $rows = $db->fetchAllAssociative(
                'SELECT er.*, ps.title as space_title, ps.initiator_id FROM ib_expert_requests er JOIN ib_project_spaces ps ON er.space_id = ps.id WHERE er.expert_user_id = ? AND (er.status = ? OR er.decided_at >= ?) ORDER BY er.requested_at DESC',
                [$userId, 'pending', $cutoff]
            );

            $data = array_map(function ($row) {
                return [
                    'id' => $row['id'],
                    'spaceId' => $row['space_id'],
                    'spaceTitle' => $row['space_title'],
                    'requestedByName' => $this->resolveUserName($row['requested_by_user_id']),
                    'message' => $row['message'],
                    'status' => $row['status'],
                    'requestedAt' => $row['requested_at'],
                    'decidedAt' => $row['decided_at'],
                ];
            }, $rows);

            return new JsonResponse(['data' => $data]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    /**
     * Resolve a CMS user ID to a display name.
     */
    private function resolveUserName(string $userId): string
    {
        try {
            $ui = \Concrete\Core\User\UserInfo::getByID((int) $userId);
            if ($ui) {
                return $ui->getUserName();
            }
        } catch (\Throwable) {
        }

        return 'Benutzer ' . $userId;
    }

    private function resolveUserAvatar(string $userId): string
    {
        try {
            $ui = \Concrete\Core\User\UserInfo::getByID((int) $userId);
            if ($ui) {
                $avatar = $ui->getUserAvatar();
                if ($avatar) {
                    return (string) $avatar->getPath();
                }
            }
        } catch (\Throwable) {
        }

        return '';
    }

    private function resolveUserExpertise(string $userId): string
    {
        try {
            $ui = \Concrete\Core\User\UserInfo::getByID((int) $userId);
            if ($ui) {
                return (string) $ui->getAttribute('expertise_area');
            }
        } catch (\Throwable) {
        }

        return '';
    }

    /**
     * Resolve a list of user IDs to [{id, name, role, avatarUrl}] objects.
     *
     * @param string[] $userIds
     * @return array<int, array{id: string, name: string, role: string, avatarUrl: string}>
     */
    private function resolveMembers(array $userIds, string $initiatorId): array
    {
        return array_map(fn(string $uid) => [
            'id' => $uid,
            'name' => $this->resolveUserName($uid),
            'role' => $uid === $initiatorId ? 'initiator' : 'developer',
            'avatarUrl' => $this->resolveUserAvatar($uid),
        ], $userIds);
    }

    /**
     * Returns the current user's role relative to this space.
     * - 'initiator': the space creator
     * - 'member': accepted Entwickler
     * - 'admin': CMS administrator (can manage any space)
     * - 'visitor': logged in but not a member
     * - null: not authenticated
     */
    private function getUserRole(ProjectSpace $space): ?string
    {
        if (!$this->auth->isAuthenticated()) {
            return null;
        }

        $userId = (string) $this->auth->getUserId();

        if ($this->auth->hasPermission('space.admin')) {
            return 'admin';
        }

        if ($userId === $space->getInitiatorId()) {
            return 'initiator';
        }

        if (in_array($userId, $space->getMemberIds(), true)) {
            return 'member';
        }

        return 'visitor';
    }

    private function findStatusUpdate(ProjectSpace $space, string $updateId): ?\Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdate
    {
        foreach ($space->getStatusUpdates() as $update) {
            if ($update->getId() === $updateId) {
                return $update;
            }
        }

        return null;
    }

    private function importUpdateFiles(Request $request, \Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdate $update): void
    {
        $uploadedFiles = $request->files->get('files', []);
        if (!is_array($uploadedFiles) || count($uploadedFiles) === 0) {
            return;
        }

        $importer = new \Concrete\Core\File\Importer();
        foreach ($uploadedFiles as $uploadedFile) {
            if (!$uploadedFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile || !$uploadedFile->isValid()) {
                continue;
            }

            $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
            $fileSize = (int) $uploadedFile->getSize();
            $filename = preg_replace('/[^\w.\-]/', '_', $uploadedFile->getClientOriginalName());

            $attachment = \Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdateAttachment::create(
                id: uniqid('uatt_', true),
                filename: $filename,
                fileManagerId: 0,
                mimeType: $mimeType,
                size: $fileSize,
            );

            $fileVersion = $importer->importUploadedFile($uploadedFile);
            if ($fileVersion instanceof \Concrete\Core\Entity\File\Version) {
                $validatedAttachment = \Concrete\Package\InnoProjectSpace\Domain\Model\StatusUpdateAttachment::create(
                    id: $attachment->getId(),
                    filename: $filename,
                    fileManagerId: (int) $fileVersion->getFile()->getFileID(),
                    mimeType: $mimeType,
                    size: $fileSize,
                );
                $update->addAttachment($validatedAttachment);
            }
        }
    }

    #[OA\Put(
        path: '/project-space/{id}/priority',
        summary: 'Set space priority for homepage teaser',
        description: 'Set priority (1-3) to feature a space on the homepage teaser, or 0 to remove. Admin only. Maximum 3 priority spaces allowed.',
        tags: ['Project Spaces'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['priority'],
            properties: [
                new OA\Property(property: 'priority', type: 'integer', minimum: 0, maximum: 3),
            ],
        ),
    )]
    #[OA\Response(response: 200, description: 'Priority updated')]
    #[OA\Response(response: 403, description: 'Not authorized')]
    #[OA\Response(response: 422, description: 'Maximum priority spaces reached')]
    public function setPriority(string $id, Request $request): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $data = json_decode($request->getContent(), true);
            $priority = (int) ($data['priority'] ?? 0);

            $this->setSpacePriorityPort->handle(new SetSpacePriorityCommand(
                spaceId: $id,
                priority: $priority,
            ));

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }
}
