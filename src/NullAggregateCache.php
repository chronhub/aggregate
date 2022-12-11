<?php

declare(strict_types=1);

namespace Chronhub\Aggregate;

use Chronhub\Contracts\Aggregate\Root;
use Chronhub\Contracts\Aggregate\Cache;
use Chronhub\Contracts\Aggregate\Identity;

final class NullAggregateCache implements Cache
{
    public function put(Root $aggregateRoot): void
    {
    }

    public function get(Identity $aggregateId): ?Root
    {
        return null;
    }

    public function forget(Identity $aggregateId): void
    {
    }

    public function flush(): bool
    {
        return true;
    }

    public function has(Identity $aggregateId): bool
    {
        return false;
    }

    public function count(): int
    {
        return 0;
    }
}
