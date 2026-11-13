# 05 Caching

Caching is a cross-cutting concern shared by all domain packages. It is abstracted behind `CacheInterface` so that domain code never touches the Concrete CMS cache layer directly.

## Interface

```php
namespace Innovationsbox\Shared\Infrastructure\Contract\Cache;

interface CacheInterface extends \Psr\SimpleCache\CacheInterface
{
    /**
     * Returns the cached value for $key, or calls $callback,
     * stores the result for $ttl seconds, and returns it.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function getOrSet(string $key, int $ttl, callable $callback): mixed;

    /**
     * Store a value and associate it with one or more tags
     * for later grouped invalidation.
     */
    public function setTagged(string $key, mixed $value, int $ttl, array $tags): void;

    /**
     * Delete all cache entries that were stored with $tag.
     */
    public function flushTag(string $tag): void;
}
```

PSR-16 (`Psr\SimpleCache\CacheInterface`) provides the standard methods: `get()`, `set()`, `delete()`, `has()`, `clear()`, `getMultiple()`, `setMultiple()`, `deleteMultiple()`.

## Adapter: ConcreteCmsCache

`Innovationsbox\Shared\Infrastructure\Adapter\Cache\ConcreteCmsCache` wraps the Concrete CMS Stash `PoolInterface`.

**Key normalisation:** dots (`.`) in key strings are converted to slashes, then the key is prefixed with `innovationsbox/`. This matches the path-based structure Stash uses internally.

Example: `innovationsbox:project_idea:idea:abc-123:detail` becomes the internal path `innovationsbox/project_idea/idea/abc-123/detail`.

**Tag implementation:** tags are not natively supported by Stash at the application level, so they are implemented via a tag index. Each tag is stored as a cached array at `innovationsbox/_tags/{tag}` containing the list of keys associated with that tag. `setTagged()` writes the entry and appends the key to every tag index. `flushTag()` reads the index, deletes each listed key, then clears the index entry.

## NullCache

`Innovationsbox\Shared\Infrastructure\Adapter\Cache\NullCache` is a no-op implementation of `CacheInterface`. Every read returns `null` (or the default), writes are discarded silently. Bound in tests via the DI container and optionally in development to disable caching without changing application code.

## DI Binding

```php
// public/packages/inno_shared/controller.php

$this->app->bind(
    \Innovationsbox\Shared\Infrastructure\Contract\Cache\CacheInterface::class,
    \Innovationsbox\Shared\Infrastructure\Adapter\Cache\ConcreteCmsCache::class
);
```

In test bootstraps, bind `NullCache` instead.

## Cache Key Convention

```
innovationsbox:{package}:{entity}:{id}:{variant}
```

| Segment | Example |
|---|---|
| `package` | `project_idea`, `moderation`, `project_space` |
| `entity` | `idea`, `review`, `space` |
| `id` | UUID or slug |
| `variant` | `detail`, `list`, `status:submitted` |

Examples:

- `innovationsbox:project_idea:idea:abc-123:detail`
- `innovationsbox:project_idea:ideas:status:submitted`
- `innovationsbox:moderation:review:idea:abc-123`

## Tag Convention

```
{entity}:{id}
```

Use the entity name and its identifier. For collections use a descriptive label without an ID.

Examples:

- `project_idea:abc-123` — all cached views of a specific idea
- `project_idea:list` — all idea list queries
- `moderation:reviews` — all review cache entries

## TTL Strategy

| Data type | TTL | Reason |
|---|---|---|
| Single entity by ID | 1800 s (30 min) | Changes infrequently; invalidated on write |
| List / collection queries | 600 s (10 min) | More volatile; new entries appear |
| Public page data | 3600 s (1 hour) | Rarely changes |
| User / auth context | 0 (no cache) | Must always be fresh |
| Config values | 3600 s (1 hour) | Changes only on deploy |

## Invalidation via Domain Events

Cache invalidation is triggered by domain events. Event handlers in each package call `flushTag()` after a write.

| Event | Tags flushed |
|---|---|
| `ProjectIdeaSubmitted` | `project_idea:list` |
| `ProjectIdeaApproved` | `project_idea:{id}`, `project_idea:list`, `moderation:reviews` |
| `ProjectIdeaRejected` | `project_idea:{id}`, `project_idea:list`, `moderation:reviews` |
| `ProjectSpaceCreated` | `project_space:list` |

## Usage Example

Repositories inject `CacheInterface` and use `getOrSet()` for reads and `setTagged()` / `flushTag()` for writes.

```php
namespace Innovationsbox\ProjectIdea\Adapter\Out\Persistence;

use Innovationsbox\Shared\Infrastructure\Contract\Cache\CacheInterface;

final class CachedProjectIdeaRepository implements ProjectIdeaRepositoryInterface
{
    public function __construct(
        private ProjectIdeaRepositoryInterface $inner,
        private CacheInterface $cache,
    ) {}

    public function findById(string $id): ?ProjectIdea
    {
        $key = "innovationsbox:project_idea:idea:{$id}:detail";

        return $this->cache->getOrSet($key, 1800, fn () => $this->inner->findById($id));
    }

    public function save(ProjectIdea $idea): void
    {
        $this->inner->save($idea);
        $this->cache->flushTag("project_idea:{$idea->getId()}");
        $this->cache->flushTag('project_idea:list');
    }
}
```
