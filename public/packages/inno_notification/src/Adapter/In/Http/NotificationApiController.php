<?php

declare(strict_types=1);

namespace Concrete\Package\InnoNotification\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Http\CsrfValidationTrait;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class NotificationApiController extends AbstractController
{
    use CsrfValidationTrait;

    public function __construct(
        private readonly ExceptionHandlerInterface $exceptionHandler,
    ) {}
    #[OA\Get(
        path: '/notification',
        summary: 'List notifications',
        description: 'Returns notifications for the current user. Currently returns an empty array (not yet implemented).',
        tags: ['Notifications'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of notifications',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ],
        ),
    )]
    public function index(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => []]);
    }

    #[OA\Post(
        path: '/notification/{id}/read',
        summary: 'Mark notification as read',
        description: 'Marks a single notification as read. Currently a stub (not yet implemented).',
        tags: ['Notifications'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Notification ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Notification marked as read', content: new OA\JsonContent(ref: '#/components/schemas/StatusResponse'))]
    #[OA\Response(response: 404, description: 'Notification not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function markRead(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);

            return new JsonResponse(['status' => 'read']);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }
}
