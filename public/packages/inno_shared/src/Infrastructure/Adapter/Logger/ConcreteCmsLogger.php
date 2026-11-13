<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Logger;

use Concrete\Package\InnoShared\Infrastructure\Contract\Logger\LoggerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

final class ConcreteCmsLogger implements LoggerInterface
{
    /**
     * @param array<string, mixed> $defaultContext
     */
    public function __construct(
        private readonly PsrLoggerInterface $logger,
        private readonly array $defaultContext = [],
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): static
    {
        return new self($this->logger, array_merge($this->defaultContext, $context));
    }

    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $this->mergeContext($context));
    }

    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $this->mergeContext($context));
    }

    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $this->mergeContext($context));
    }

    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $this->mergeContext($context));
    }

    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $this->mergeContext($context));
    }

    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $this->mergeContext($context));
    }

    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $this->mergeContext($context));
    }

    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $this->mergeContext($context));
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $this->mergeContext($context));
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function mergeContext(array $context): array
    {
        return array_merge($this->defaultContext, $context);
    }
}
