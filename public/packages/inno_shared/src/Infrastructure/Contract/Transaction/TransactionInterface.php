<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Contract\Transaction;

interface TransactionInterface
{
    public function begin(): void;

    public function commit(): void;

    public function rollback(): void;

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed;

    public function getDepth(): int;
}
