<?php

declare(strict_types=1);

namespace Concrete\Package\InnoProjectIdea\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoProjectIdea\Application\UseCase\DeleteProjectIdea\DeleteProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\EditProjectIdea\EditProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Application\UseCase\SubmitProjectIdea\SubmitProjectIdeaCommand;
use Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException;
use Concrete\Package\InnoProjectIdea\Port\In\DeleteProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\In\EditProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\In\SubmitProjectIdeaPort;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Concrete\Package\InnoShared\Infrastructure\Http\CsrfValidationTrait;
use Concrete\Package\InnoShared\Infrastructure\Validation\InputValidator;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProjectIdeaApiController extends AbstractController
{
    use CsrfValidationTrait;

    public function __construct(
        private readonly ProjectIdeaRepositoryInterface $repository,
        private readonly SubmitProjectIdeaPort $submitPort,
        private readonly EditProjectIdeaPort $editPort,
        private readonly DeleteProjectIdeaPort $deletePort,
        private readonly AuthInterface $auth,
        private readonly ExceptionHandlerInterface $exceptionHandler,
    ) {}

    #[OA\Get(
        path: '/project-idea',
        summary: 'List all project ideas',
        description: 'Returns a list of all project ideas with their ID, title, and current status.',
        tags: ['Project Ideas'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of project ideas',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ProjectIdeaSummary'),
                ),
            ],
        ),
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $ideas = $this->repository->findAll();

            // Non-admin users only see approved ideas
            $isAdmin = $this->auth->hasPermission('idea.admin');
            if (!$isAdmin) {
                $ideas = array_filter(
                    $ideas,
                    fn($idea) => $idea->getStatus() === \Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus::Approved,
                );
            }

            return new JsonResponse(['data' => array_map(
                fn($idea) => [
                    'id' => $idea->getId(),
                    'title' => $idea->getTitle(),
                    'status' => $idea->getStatus()->value,
                ],
                array_values($ideas),
            )]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Post(
        path: '/project-idea',
        summary: 'Submit a new project idea',
        description: 'Creates a new project idea. Requires the idea.submit permission (roles: initiator, administrator).',
        security: [['cookieAuth' => []]],
        tags: ['Project Ideas'],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/SubmitProjectIdeaRequest'),
    )]
    #[OA\Response(
        response: 201,
        description: 'Idea created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'id', type: 'string', example: 'idea_6627a1b2e3f41'),
                    ],
                    type: 'object',
                ),
            ],
        ),
    )]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden — missing idea.submit permission', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function store(Request $request): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            if (!$this->auth->hasPermission('idea.submit')) {
                throw new AuthException(
                    'Permission denied for idea.submit',
                    ['permission' => 'idea.submit'],
                    $this->auth->isAuthenticated(),
                );
            }

            $contentType = $request->headers->get('Content-Type', '');
            if (str_contains($contentType, 'application/json')) {
                $body = json_decode($request->getContent(), true) ?? [];
            } else {
                // multipart/form-data — fields are in POST params
                $body = $request->request->all();
            }

            $v = new InputValidator($body);
            $title = $v->requireString('title', 3, 255);
            $description = $v->requireString('description', 10, 5000);
            $category = $v->optionalString('category', 100) ?? '';

            // Validate header image before creating the idea
            $headerImageFile = $request->files->get('headerImage');
            if ($headerImageFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile && $headerImageFile->isValid()) {
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($headerImageFile->getMimeType(), $allowedMimes, true)) {
                    return new JsonResponse(['error' => ['message' => 'Header image must be JPEG, PNG, GIF, or WebP.']], 422);
                }
                if ($headerImageFile->getSize() > 10 * 1024 * 1024) {
                    return new JsonResponse(['error' => ['message' => 'Header image must be at most 10 MB.']], 422);
                }
                $imageSize = @getimagesize($headerImageFile->getPathname());
                if ($imageSize === false || $imageSize[0] < 800) {
                    return new JsonResponse(['error' => ['message' => 'Header image must be at least 800px wide.']], 422);
                }
            }

            $idea = $this->submitPort->handle(new SubmitProjectIdeaCommand(
                title: $title,
                description: $description,
                category: $category,
                submitterId: (string) $this->auth->getUserId(),
            ));

            // Import header image (single file, separate from attachments)
            $headerImageImported = false;
            if ($headerImageFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile && $headerImageFile->isValid()) {
                $importer = new \Concrete\Core\File\Importer();
                $fileVersion = $importer->importUploadedFile($headerImageFile);
                if ($fileVersion instanceof \Concrete\Core\Entity\File\Version) {
                    $idea->setHeaderImageFileId((int) $fileVersion->getFile()->getFileID());
                    $headerImageImported = true;
                }
            }

            // Import uploaded files (also calls repository->save when files are present)
            $this->importFiles($request, $idea);

            // If header image was set but no attachments triggered a save, persist explicitly
            if ($headerImageImported) {
                $uploadedFiles = $request->files->get('files', []);
                if (!is_array($uploadedFiles) || count($uploadedFiles) === 0) {
                    $this->repository->save($idea);
                }
            }

            return new JsonResponse(['data' => ['id' => $idea->getId()]], 201);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Get(
        path: '/project-idea/{id}',
        summary: 'Get project idea details',
        description: 'Returns the full details of a single project idea.',
        tags: ['Project Ideas'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Idea details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/ProjectIdeaDetail'),
            ],
        ),
    )]
    #[OA\Response(response: 404, description: 'Idea not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $idea = $this->repository->findById($id);
            if ($idea === null) {
                throw ProjectIdeaNotFoundException::withId($id);
            }

            // Non-public ideas are only visible to the submitter or admins
            $isAdmin = $this->auth->hasPermission('idea.admin');
            $isOwner = $this->auth->isAuthenticated()
                && $idea->getSubmitterId() === (string) $this->auth->getUserId();
            if ($idea->getStatus() !== \Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus::Approved
                && !$isAdmin && !$isOwner) {
                throw ProjectIdeaNotFoundException::withId($id);
            }

            return new JsonResponse(['data' => [
                'id' => $idea->getId(),
                'title' => $idea->getTitle(),
                'description' => $idea->getDescription(),
                'category' => $idea->getCategory(),
                'status' => $idea->getStatus()->value,
                'submitterId' => $idea->getSubmitterId(),
                'headerImageUrl' => $idea->getHeaderImageFileId() !== null
                    ? $this->resolveFileUrl($idea->getHeaderImageFileId())
                    : '',
            ]]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Put(
        path: '/project-idea/{id}',
        summary: 'Update a project idea',
        description: 'Updates an existing project idea.',
        tags: ['Project Ideas'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Idea updated')]
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $contentType = $request->headers->get('Content-Type', '');
            if (str_contains($contentType, 'application/json')) {
                $body = json_decode($request->getContent(), true) ?? [];
            } else {
                $body = $request->request->all();
            }

            $isAdmin = $this->auth->hasPermission('idea.admin');
            $idea = $this->repository->findById($id);
            if ($idea === null) {
                throw \Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException::withId($id);
            }

            $v = new InputValidator($body);
            $title = $v->requireString('title', 3, 255);
            $description = $v->requireString('description', 10, 5000);
            $category = $v->optionalString('category', 100) ?? '';

            if ($isAdmin) {
                // Admins can update text fields via applyOverrides (bypasses status guard)
                $idea->applyOverrides($title, $description, $category);
                $this->repository->save($idea);
            } else {
                // Regular users go through the edit handler (checks ownership + status)
                $this->editPort->handle(new EditProjectIdeaCommand(
                    ideaId: $id,
                    title: $title,
                    description: $description,
                    category: $category,
                ));
                // Re-fetch after handler may have changed status
                $idea = $this->repository->findById($id);
                if ($idea === null) {
                    throw \Concrete\Package\InnoProjectIdea\Domain\Exception\ProjectIdeaNotFoundException::withId($id);
                }
            }

            // Remove files if requested (admins bypass guardEditable via direct repo)
            $removeFiles = $body['removeFiles'] ?? [];
            if (is_array($removeFiles) && count($removeFiles) > 0) {
                if ($isAdmin) {
                    // Direct DB removal for admins
                    $db = $this->app->make('database')->connection();
                    foreach ($removeFiles as $attId) {
                        $db->executeStatement('DELETE FROM ib_idea_attachments WHERE id = ? AND idea_id = ?', [(string) $attId, $id]);
                    }
                    // Re-fetch to get updated attachments
                    $idea = $this->repository->findById($id);
                } else {
                    // Verify ownership before allowing file removal
                    if ($idea->getSubmitterId() !== (string) $this->auth->getUserId()) {
                        throw new AuthException('Only the idea owner can remove files', [], true);
                    }
                    foreach ($removeFiles as $attId) {
                        $idea->removeAttachment((string) $attId);
                    }
                    $this->repository->save($idea);
                }
            }

            // Handle header image upload or removal
            $removeHeaderImage = ($body['removeHeaderImage'] ?? '') === '1';
            $headerImageFile = $request->files->get('headerImage');
            if ($removeHeaderImage && $headerImageFile === null) {
                $idea->setHeaderImageFileId(null);
                $this->repository->save($idea);
            } elseif ($headerImageFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile && $headerImageFile->isValid()) {
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($headerImageFile->getMimeType(), $allowedMimes, true)) {
                    return new JsonResponse(['error' => ['message' => 'Header image must be JPEG, PNG, GIF, or WebP.']], 422);
                }
                if ($headerImageFile->getSize() > 10 * 1024 * 1024) {
                    return new JsonResponse(['error' => ['message' => 'Header image must be at most 10 MB.']], 422);
                }
                $imageSize = @getimagesize($headerImageFile->getPathname());
                if ($imageSize === false || $imageSize[0] < 800) {
                    return new JsonResponse(['error' => ['message' => 'Header image must be at least 800px wide.']], 422);
                }
                $importer = new \Concrete\Core\File\Importer();
                $fileVersion = $importer->importUploadedFile($headerImageFile);
                if ($fileVersion instanceof \Concrete\Core\Entity\File\Version) {
                    $idea->setHeaderImageFileId((int) $fileVersion->getFile()->getFileID());
                    $this->repository->save($idea);
                }
            }

            // Import new uploaded files (works for both - addAttachment only checks guardEditable for non-admin)
            if ($idea !== null) {
                $this->importFilesForAdmin($request, $idea, $isAdmin);
            }

            return new JsonResponse(['status' => 'updated']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Get(
        path: '/project-idea/mine',
        summary: 'List my ideas',
        description: 'Returns all project ideas submitted by the currently authenticated user, in all statuses.',
        security: [['cookieAuth' => []]],
        tags: ['Project Ideas'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of user\'s ideas',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ProjectIdeaDetail'),
                ),
            ],
        ),
    )]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function mine(Request $request): JsonResponse
    {
        try {
            if (!$this->auth->isAuthenticated()) {
                throw new AuthException('Authentication required', [], false);
            }

            $ideas = $this->repository->findBySubmitterId((string) $this->auth->getUserId());

            return new JsonResponse(['data' => array_map(
                fn($idea) => [
                    'id' => $idea->getId(),
                    'title' => $idea->getTitle(),
                    'description' => $idea->getDescription(),
                    'category' => $idea->getCategory(),
                    'status' => $idea->getStatus()->value,
                    'rejectionReason' => $idea->getRejectionReason(),
                    'feedbackNotes' => $idea->getFeedbackNotes(),
                    'feedbackDeadline' => $idea->getFeedbackDeadline(),
                    'projectSpaceId' => $idea->getProjectSpaceId(),
                    'attachments' => array_map(fn($a) => [
                        'id' => $a->getId(),
                        'filename' => $a->getFilename(),
                        'mimeType' => $a->getMimeType(),
                        'size' => $a->getSize(),
                        'isImage' => $a->isImage(),
                        'url' => $this->resolveFileUrl($a->getFileManagerId()),
                    ], $idea->getAttachments()),
                    'headerImageUrl' => $idea->getHeaderImageFileId() !== null
                        ? $this->resolveFileUrl($idea->getHeaderImageFileId())
                        : '',
                    'createdAt' => $idea->getCreatedAt()->format('c'),
                ],
                $ideas,
            )]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Delete(
        path: '/project-idea/{id}',
        summary: 'Delete a project idea',
        description: 'Deletes a project idea. Only the submitter or an admin can delete.',
        security: [['cookieAuth' => []]],
        tags: ['Project Ideas'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Idea ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Idea deleted', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Idea not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->deletePort->handle(new DeleteProjectIdeaCommand(ideaId: $id));

            return new JsonResponse(['status' => 'deleted']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    private function importFiles(Request $request, \Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea $idea): void
    {
        $this->importFilesForAdmin($request, $idea, false);
    }

    private function importFilesForAdmin(Request $request, \Concrete\Package\InnoProjectIdea\Domain\Model\ProjectIdea $idea, bool $isAdmin): void
    {
        $uploadedFiles = $request->files->get('files', []);
        if (!is_array($uploadedFiles) || count($uploadedFiles) === 0) {
            return;
        }

        $importer = new \Concrete\Core\File\Importer();
        $db = $this->app->make('database')->connection();

        foreach ($uploadedFiles as $uploadedFile) {
            if (!$uploadedFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile || !$uploadedFile->isValid()) {
                continue;
            }

            $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
            $fileSize = (int) $uploadedFile->getSize();
            $filename = preg_replace('/[^\w.\-]/', '_', $uploadedFile->getClientOriginalName());

            // Validate MIME type and size before importing (applies to all paths)
            $attachment = \Concrete\Package\InnoProjectIdea\Domain\Model\IdeaAttachment::create(
                id: uniqid('att_', true),
                filename: $filename,
                fileManagerId: 0, // placeholder, updated after import
                mimeType: $mimeType,
                size: $fileSize,
            );

            $fileVersion = $importer->importUploadedFile($uploadedFile);
            if ($fileVersion instanceof \Concrete\Core\Entity\File\Version) {
                $fileManagerId = (int) $fileVersion->getFile()->getFileID();

                if ($isAdmin) {
                    // Insert directly to bypass guardEditable
                    $db->insert('ib_idea_attachments', [
                        'id' => $attachment->getId(),
                        'idea_id' => $idea->getId(),
                        'file_manager_id' => $fileManagerId,
                        'filename' => $attachment->getFilename(),
                        'mime_type' => $attachment->getMimeType(),
                        'size' => $attachment->getSize(),
                        'added_at' => $attachment->getAddedAt()->format('Y-m-d H:i:s'),
                    ]);
                } else {
                    $validatedAttachment = \Concrete\Package\InnoProjectIdea\Domain\Model\IdeaAttachment::create(
                        id: $attachment->getId(),
                        filename: $filename,
                        fileManagerId: $fileManagerId,
                        mimeType: $mimeType,
                        size: $fileSize,
                    );
                    $idea->addAttachment($validatedAttachment);
                }
            }
        }

        if (!$isAdmin) {
            $this->repository->save($idea);
        }
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
}
