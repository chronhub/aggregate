<?php

declare(strict_types=1);

namespace Chronhub\Aggregate;

use Chronhub\Contracts\Aggregate\Root;
use Chronhub\Contracts\Aggregate\Cache;
use Chronhub\Contracts\Aggregate\Identity;
use Illuminate\Contracts\Cache\Repository;

final class AggregateTaggedCache implements Cache
{
    private int $count = 0;

    public function __construct(private readonly Repository $cache,
                                private readonly string $cacheTag,
                                private readonly int $limit = 10000)
    {
    }

    public function put(Root $aggregateRoot): void
    {
        if ($this->count === $this->limit) {
            $this->flush();
        }

        $aggregateId = $aggregateRoot->aggregateId();

        if (! $this->has($aggregateId)) {
            $this->count++;
        }

        $cacheKey = $this->determineCacheKey($aggregateId);

        $this->cache->tags([$this->cacheTag])->forever($cacheKey, $aggregateRoot);
    }

    public function get(Identity $aggregateId): ?Root
    {
        $cacheKey = $this->determineCacheKey($aggregateId);

        return $this->cache->tags([$this->cacheTag])->get($cacheKey);
    }

    public function forget(Identity $aggregateId): void
    {
        if ($this->has($aggregateId)) {
            $cacheKey = $this->determineCacheKey($aggregateId);

            if ($this->cache->tags([$this->cacheTag])->forget($cacheKey)) {
                $this->count--;
            }
        }
    }

    public function flush(): bool
    {
        $this->count = 0;

        return $this->cache->tags([$this->cacheTag])->flush();
    }

    public function has(Identity $aggregateId): bool
    {
        $cacheKey = $this->determineCacheKey($aggregateId);

        return $this->cache->tags([$this->cacheTag])->has($cacheKey);
    }

    public function count(): int
    {
        return $this->count;
    }

    private function determineCacheKey(Identity $aggregateId): string
    {
        return class_basename($aggregateId::class).':'.$aggregateId->toString();
    }
}
