<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoModeration\Application\UseCase\ReviewProjectIdea\ReviewProjectIdeaCommand;
use Concrete\Package\InnoModeration\Domain\Exception\ModerationReviewNotFoundException;
use Concrete\Package\InnoModeration\Domain\Model\ModerationDecision;
use Concrete\Package\InnoModeration\Port\In\ReviewProjectIdeaPort;
use Concrete\Package\InnoModeration\Port\Out\ModerationReviewRepositoryInterface;
use Concrete\Package\InnoProjectIdea\Port\Out\ProjectIdeaRepositoryInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;
use Concrete\Package\InnoShared\Infrastructure\Http\CsrfValidationTrait;
use Concrete\Package\InnoShared\Port\CategoryReaderPort;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ModerationApiController extends AbstractController
{
    use CsrfValidationTrait;

    public function __construct(
        private readonly ModerationReviewRepositoryInterface $repository,
        private readonly ReviewProjectIdeaPort $reviewPort,
        private readonly AuthInterface $auth,
        private readonly ExceptionHandlerInterface $exceptionHandler,
        private readonly ProjectIdeaRepositoryInterface $ideaRepository,
        private readonly CategoryReaderPort $categoryReader,
    ) {}

    #[OA\Get(
        path: '/moderation',
        summary: 'List pending moderation reviews',
        description: 'Returns all reviews with status Pending. Requires moderation.review permission (roles: moderator, administrator).',
        security: [['cookieAuth' => []]],
        tags: ['Moderation'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of pending reviews',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ModerationReviewSummary'),
                ),
            ],
        ),
    )]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden — missing moderation.review permission', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function index(Request $request): JsonResponse
    {
        try {
            $this->requirePermission('moderation.review');

            $reviews = $this->repository->findByDecision(ModerationDecision::Pending);
            $categoryMap = $this->buildCategoryMap();

            $data = [];
            foreach ($reviews as $r) {
                $idea = $this->ideaRepository->findById($r->getIdeaId());
                if ($idea === null) {
                    continue; // skip stale reviews for deleted ideas
                }
                $data[] = [
                    'id' => $r->getId(),
                    'ideaId' => $r->getIdeaId(),
                    'ideaTitle' => $idea->getTitle(),
                    'submitterName' => $this->resolveUserName($idea->getSubmitterId()),
                    'category' => $categoryMap[$idea->getCategory()] ?? $idea->getCategory(),
                    'createdAt' => $r->getCreatedAt()->format('c'),
                ];
            }

            return new JsonResponse(['data' => $data]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Get(
        path: '/moderation/{id}',
        summary: 'View moderation review detail',
        description: 'Returns the full review with embedded idea data. Auto-locks the idea to UnderReview status if it was Submitted. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['Moderation'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Review ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Review detail with embedded idea',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/ModerationReviewDetail'),
            ],
        ),
    )]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Review not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $this->requirePermission('moderation.review');

            $review = $this->repository->findById($id);
            if ($review === null) {
                throw ModerationReviewNotFoundException::withId($id);
            }

            $idea = $this->ideaRepository->findById($review->getIdeaId());

            // Auto-lock: set idea to UnderReview when moderator views it
            if ($idea !== null && $idea->getStatus() === \Concrete\Package\InnoProjectIdea\Domain\Model\IdeaStatus::Submitted) {
                $idea->markUnderReview();
                $this->ideaRepository->save($idea);
            }

            $data = [
                'id' => $review->getId(),
                'ideaId' => $review->getIdeaId(),
                'decision' => $review->getDecision()->value,
                'reviewerId' => $review->getReviewerId(),
                'notes' => $review->getNotes(),
                'createdAt' => $review->getCreatedAt()->format('c'),
                'reviewedAt' => $review->getReviewedAt()?->format('c'),
            ];

            if ($idea !== null) {
                $categoryMap = $this->buildCategoryMap();
                $data['idea'] = [
                    'title' => $idea->getTitle(),
                    'description' => $idea->getDescription(),
                    'category' => $idea->getCategory(),
                    'categoryName' => $categoryMap[$idea->getCategory()] ?? $idea->getCategory(),
                    'submitterId' => $idea->getSubmitterId(),
                    'submitterName' => $this->resolveUserName($idea->getSubmitterId()),
                    'status' => $idea->getStatus()->value,
                    'createdAt' => $idea->getCreatedAt()->format('c'),
                    'attachments' => array_map(fn($a) => [
                        'id' => $a->getId(),
                        'filename' => $a->getFilename(),
                        'mimeType' => $a->getMimeType(),
                        'size' => $a->getSize(),
                        'isImage' => $a->isImage(),
                        'url' => $this->resolveFileUrl($a->getFileManagerId()),
                    ], $idea->getAttachments()),
                ];
            }

            return new JsonResponse(['data' => $data]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Post(
        path: '/moderation/{id}/approve',
        summary: 'Approve a project idea',
        description: 'Approves the moderation review, triggering idea approval and project space creation. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['Moderation'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Review ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/ModerationNotesRequest'))]
    #[OA\Response(response: 200, description: 'Idea approved', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Review not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->requirePermission('moderation.review');

            $body = json_decode($request->getContent(), true) ?? [];
            $this->reviewPort->handle(new ReviewProjectIdeaCommand(
                reviewId: $id,
                reviewerId: (string) $this->auth->getUserId(),
                approved: true,
                notes: $body['notes'] ?? '',
                titleOverride: isset($body['title']) ? (string) $body['title'] : null,
                descriptionOverride: isset($body['description']) ? (string) $body['description'] : null,
                categoryOverride: isset($body['category']) ? (string) $body['category'] : null,
            ));

            return new JsonResponse(['status' => 'approved']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Post(
        path: '/moderation/{id}/reject',
        summary: 'Reject a project idea',
        description: 'Rejects the moderation review with a reason. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['Moderation'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Review ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/ModerationNotesRequest'))]
    #[OA\Response(response: 200, description: 'Idea rejected', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Review not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->requirePermission('moderation.review');

            $body = json_decode($request->getContent(), true) ?? [];
            $this->reviewPort->handle(new ReviewProjectIdeaCommand(
                reviewId: $id,
                reviewerId: (string) $this->auth->getUserId(),
                approved: false,
                notes: $body['notes'] ?? '',
            ));

            return new JsonResponse(['status' => 'rejected']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Post(
        path: '/moderation/{id}/feedback',
        summary: 'Request feedback from idea submitter',
        description: 'Sets the review to feedback_requested status and notifies the submitter. Notes are required. Optional deadline. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['Moderation'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Review ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['notes'],
        properties: [
            new OA\Property(property: 'notes', type: 'string', description: 'Feedback notes for the submitter'),
            new OA\Property(property: 'deadline', type: 'string', format: 'date', description: 'Optional feedback deadline (YYYY-MM-DD)', nullable: true),
        ],
    ))]
    #[OA\Response(response: 200, description: 'Feedback requested', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Review not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 422, description: 'Validation error — notes required', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function requestFeedback(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->requirePermission('moderation.review');
            $body = json_decode($request->getContent(), true) ?? [];
            $notes = trim((string) ($body['notes'] ?? ''));
            if ($notes === '') {
                throw new ValidationException('Notes are required for feedback.', ['field' => 'notes']);
            }
            $this->reviewPort->handle(new ReviewProjectIdeaCommand(
                reviewId: $id,
                reviewerId: (string) $this->auth->getUserId(),
                approved: false,
                notes: $notes,
                feedbackRequested: true,
                feedbackDeadline: isset($body['deadline']) ? (string) $body['deadline'] : null,
            ));

            return new JsonResponse(['status' => 'feedback_requested']);
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

    /** @return array<string, string> category ID => name */
    private function buildCategoryMap(): array
    {
        $map = [];
        foreach ($this->categoryReader->findAllCategories() as $cat) {
            $map[$cat['id']] = $cat['name'];
        }

        return $map;
    }
}
