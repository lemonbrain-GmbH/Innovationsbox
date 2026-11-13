<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\ErrorHandling;

use Concrete\Package\InnoShared\Infrastructure\Contract\Config\ConfigInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\ErrorHandling\ExceptionHandlerInterface;
use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Concrete\Package\InnoShared\Infrastructure\Exception\BaseException;
use Symfony\Component\HttpFoundation\JsonResponse;

class ConcreteCmsExceptionHandler implements ExceptionHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConfigInterface $config,
    ) {}

    public function toJsonResponse(\Throwable $e): JsonResponse
    {
        if ($e instanceof BaseException) {
            $this->logger->warning($e->getMessage(), $e->getContext());

            return $this->buildResponse(
                $e->getHttpStatusCode(),
                $e->getUserMessage(),
                $e,
            );
        }

        $this->logger->critical($e->getMessage(), [
            'exception' => get_class($e),
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->buildResponse(
            500,
            'An internal error occurred. Please try again later.',
            $e,
        );
    }

    private function buildResponse(int $status, string $userMessage, \Throwable $e): JsonResponse
    {
        $body = [
            'error' => [
                'message' => $userMessage,
                'code' => $status,
            ],
        ];

        if ($this->config->get('app.debug', false)) {
            $body['error']['debug'] = [
                'exception' => (new \ReflectionClass($e))->getShortName(),
                'detail' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        return new JsonResponse($body, $status);
    }
}
