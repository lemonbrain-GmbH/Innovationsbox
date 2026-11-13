<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Transaction;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Package\InnoShared\Infrastructure\Contract\Transaction\TransactionInterface;

class ConcreteCmsTransaction implements TransactionInterface
{
    public function __construct(private readonly Connection $connection) {}

    public function begin(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    public function transactional(callable $operation): mixed
    {
        $this->begin();
        try {
            $result = $operation();
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getDepth(): int
    {
        return $this->connection->getTransactionNestingLevel();
    }
}
