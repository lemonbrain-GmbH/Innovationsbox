<?php

declare(strict_types=1);

namespace Concrete\Package\InnoModeration\Adapter\In\Http;

use Concrete\Core\Controller\AbstractController;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\CreateCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\DeleteCategoryCommand;
use Concrete\Package\InnoModeration\Application\UseCase\ManageCategory\UpdateCategoryCommand;
use Concrete\Package\InnoModeration\Port\In\ManageCategoryPort;
use Concrete\Package\InnoShared\Infrastructure\Contract\Auth\AuthInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\AuthException;
use Concrete\Package\InnoShared\Infrastructure\Http\CsrfValidationTrait;
use Concrete\Package\InnoShared\Infrastructure\Validation\InputValidator;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class CategoryApiController extends AbstractController
{
    use CsrfValidationTrait;

    public function __construct(
        private readonly ManageCategoryPort $manageCategoryPort,
        private readonly AuthInterface $auth,
        private readonly ExceptionHandlerInterface $exceptionHandler,
    ) {}

    #[OA\Get(
        path: '/moderation/category',
        summary: 'List all categories',
        description: 'Returns all moderation categories. Requires authentication.',
        security: [['cookieAuth' => []]],
        tags: ['Categories'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of categories',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'string'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'slug', type: 'string'),
                            new OA\Property(property: 'sortOrder', type: 'integer'),
                        ],
                    ),
                ),
            ],
        ),
    )]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function index(): JsonResponse
    {
        if (!$this->auth->isAuthenticated()) {
            throw new AuthException('Authentication required', [], false);
        }
        $categories = $this->manageCategoryPort->list();

        return new JsonResponse([
            'data' => array_map(fn($c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'sortOrder' => $c->getSortOrder(),
            ], $categories),
        ]);
    }

    #[OA\Post(
        path: '/moderation/category',
        summary: 'Create a new category',
        description: 'Creates a new moderation category. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['Categories'],
    )]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'Category name', maxLength: 255),
                new OA\Property(property: 'slug', type: 'string', description: 'URL slug (optional)', maxLength: 255),
                new OA\Property(property: 'sort_order', type: 'integer', description: 'Sort order (optional, defaults to 0)'),
            ],
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'Category created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'id', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'slug', type: 'string'),
                        new OA\Property(property: 'sortOrder', type: 'integer'),
                    ],
                    type: 'object',
                ),
            ],
        ),
    )]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function store(Request $request): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->requirePermission('moderation.review');
            $contentType = $request->headers->get('Content-Type', '');
            if (str_contains($contentType, 'application/json')) {
                $body = json_decode($request->getContent(), true) ?? [];
            } else {
                $body = $request->request->all();
            }
            $v = new InputValidator($body);
            $name = $v->requireString('name', 1, 255);
            $slug = $v->optionalString('slug', 255);
            $sortOrder = (int) ($body['sort_order'] ?? 0);

            $category = $this->manageCategoryPort->create(
                new CreateCategoryCommand($name, $slug, $sortOrder),
            );

            return new JsonResponse([
                'data' => [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                    'sortOrder' => $category->getSortOrder(),
                ],
            ], 201);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Put(
        path: '/moderation/category/{id}',
        summary: 'Update a category',
        description: 'Updates an existing moderation category. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['Categories'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Category ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'Category name', maxLength: 255),
                new OA\Property(property: 'slug', type: 'string', description: 'URL slug', maxLength: 255),
                new OA\Property(property: 'sort_order', type: 'integer', description: 'Sort order'),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Category updated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'id', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'slug', type: 'string'),
                        new OA\Property(property: 'sortOrder', type: 'integer'),
                    ],
                    type: 'object',
                ),
            ],
        ),
    )]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Category not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->requirePermission('moderation.review');
            $contentType = $request->headers->get('Content-Type', '');
            if (str_contains($contentType, 'application/json')) {
                $body = json_decode($request->getContent(), true) ?? [];
            } else {
                $body = $request->request->all();
            }
            $v = new InputValidator($body);
            $name = $v->optionalString('name', 255);
            $slug = $v->optionalString('slug', 255);
            $rawSortOrder = $body['sort_order'] ?? null;
            $sortOrder = $rawSortOrder !== null ? (int) $rawSortOrder : null;

            try {
                $category = $this->manageCategoryPort->update(new UpdateCategoryCommand(
                    $id,
                    $name,
                    $slug,
                    $sortOrder,
                ));
            } catch (\RuntimeException $e) {
                return new JsonResponse(['error' => $e->getMessage()], 404);
            }

            return new JsonResponse([
                'data' => [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                    'sortOrder' => $category->getSortOrder(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->exceptionHandler->toJsonResponse($e);
        }
    }

    #[OA\Delete(
        path: '/moderation/category/{id}',
        summary: 'Delete a category',
        description: 'Deletes a moderation category. Fails with 409 if the category is still assigned to ideas. Requires moderation.review permission.',
        security: [['cookieAuth' => []]],
        tags: ['Categories'],
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'Category ID', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'X-CSRF-Token', in: 'header', required: true, description: 'CSRF token from meta tag', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 204, description: 'Category deleted')]
    #[OA\Response(response: 401, description: 'Not authenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 404, description: 'Category not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    #[OA\Response(response: 409, description: 'Category still assigned to ideas', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))]
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->validateCsrfToken($request);
            $this->requirePermission('moderation.review');

            try {
                $this->manageCategoryPort->delete(new DeleteCategoryCommand($id));
            } catch (\RuntimeException $e) {
                $code = str_contains($e->getMessage(), 'assigned') ? 409 : 404;

                return new JsonResponse(['error' => $e->getMessage()], $code);
            }

            return new JsonResponse(null, 204);
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
