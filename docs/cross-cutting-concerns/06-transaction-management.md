# 06 Transaction Management

Transaction management is a cross-cutting concern. Application handlers that write to multiple tables or dispatch events after a write must do so atomically. `TransactionInterface` keeps that coordination out of domain code and away from Concrete CMS internals.

## Interface

```php
namespace Innovationsbox\Shared\Infrastructure\Contract\Transaction;

interface TransactionInterface
{
    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;

    /**
     * Wraps a callable in begin/commit/rollback.
     * Returns the callable's return value.
     * Rolls back and rethrows on any exception.
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed;

    /**
     * Current nesting depth. 0 = no active transaction.
     */
    public function getDepth(): int;
}
```

`transactional()` is the primary method. It calls `begin()`, runs the operation, then either `commit()` on success or `rollback()` on any `\Throwable`, which is then rethrown unchanged. Callers do not need to write try/catch/finally blocks.

`getDepth()` returns the current nesting level: `0` means no transaction is active. Useful for assertions in tests and for logging rollback context.

## Adapter: ConcreteCmsTransaction

`Innovationsbox\Shared\Infrastructure\Adapter\Transaction\ConcreteCmsTransaction` wraps `Concrete\Core\Database\Connection\Connection`, which is Doctrine DBAL.

Nested calls to `begin()` are handled transparently via DBAL savepoints. DBAL tracks nesting depth internally; `getDepth()` delegates to the connection's transaction nesting counter.

```php
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
```

## DI Binding

```php
// public/packages/inno_shared/controller.php

$this->app->bind(
    \Innovationsbox\Shared\Infrastructure\Contract\Transaction\TransactionInterface::class,
    \Innovationsbox\Shared\Infrastructure\Adapter\Transaction\ConcreteCmsTransaction::class
);
```

## Where Transactions Are Needed

| Handler | Atomic operations |
|---|---|
| `SubmitProjectIdeaHandler` | Save idea + dispatch `ProjectIdeaSubmitted` event |
| `ReviewProjectIdeaHandler` | Save review decision + dispatch `ModerationDecisionMade` event |
| `OnModerationDecisionMade` | Load idea + update status + save + dispatch `ProjectIdeaApproved` or `ProjectIdeaRejected` |
| `OnProjectIdeaApproved` | Create project space + save + dispatch `ProjectSpaceCreated` event |

Each of these involves at least one database write followed by an event dispatch. If the event listener fails, the write must be rolled back so the system is not left in an inconsistent state.

## Events Dispatched Inside Transactions

Events are dispatched inside the `transactional()` block. If a synchronous listener throws, the entire transaction rolls back. This guarantees write/event consistency.

**Consequence:** listeners that perform slow or unreliable work (sending email, calling external APIs) must not execute synchronously. Slow work should be queued and processed asynchronously.

## Usage Example

Inject `TransactionInterface` via constructor and wrap all multi-step operations with `transactional()`.

```php
namespace Innovationsbox\ProjectIdea\Application\UseCase;

use Innovationsbox\Shared\Infrastructure\Contract\Transaction\TransactionInterface;
use Innovationsbox\Shared\Infrastructure\Contract\Logger\LoggerInterface;
use Innovationsbox\Shared\Domain\Event\ProjectIdeaSubmitted;

final class SubmitProjectIdeaHandler implements SubmitProjectIdeaPort
{
    public function __construct(
        private TransactionInterface $transaction,
        private ProjectIdeaRepositoryInterface $repository,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
    ) {}

    public function handle(SubmitProjectIdeaCommand $command): ProjectIdea
    {
        return $this->transaction->transactional(function () use ($command) {
            $idea = ProjectIdea::submit(
                $command->title,
                $command->description,
                $command->submitterId,
            );

            $this->repository->save($idea);

            $this->eventDispatcher->dispatch(
                new ProjectIdeaSubmitted($idea->getId(), $idea->getSubmitterId())
            );

            $this->logger->info('Project idea submitted', [
                'ideaId'      => $idea->getId(),
                'submitterId' => $idea->getSubmitterId(),
            ]);

            return $idea;
        });
    }
}
```

If `repository->save()` succeeds but the event dispatch throws, the transaction rolls back and the row is removed. The caller receives the original exception.
