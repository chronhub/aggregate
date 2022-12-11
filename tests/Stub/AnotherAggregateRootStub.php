<?php

declare(strict_types=1);

namespace Chronhub\Aggregate\Tests\Stub;

use Chronhub\Contracts\Aggregate\Root;
use Chronhub\Contracts\Aggregate\Identity;
use Chronhub\Aggregate\HasAggregateBehaviour;
use Chronhub\Aggregate\Tests\Double\SomeEvent;

final class AnotherAggregateRootStub implements Root
{
    use HasAggregateBehaviour;

    public static function create(Identity $aggregateId, SomeEvent ...$events): self
    {
        $aggregateRoot = new self($aggregateId);

        foreach ($events as $event) {
            $aggregateRoot->recordThat($event);
        }

        return $aggregateRoot;
    }
}
