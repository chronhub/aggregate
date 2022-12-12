<?php

declare(strict_types=1);

namespace Chronhub\Aggregate\Tests\Stub;

use Chronhub\Contracts\Aggregate\Root;
use Chronhub\Contracts\Aggregate\Type;
use Chronhub\Contracts\Aggregate\Identity;
use Chronhub\Aggregate\ReconstituteAggregate;
use Chronhub\Contracts\Chronicler\Chronicler;
use Chronhub\Contracts\Stream\StreamProducer;
use Chronhub\Contracts\Chronicler\QueryFilter;

final class ReconstituteAggregateRootStub
{
    use ReconstituteAggregate;

    public function __construct(protected readonly Chronicler $chronicler,
                                protected readonly StreamProducer $producer,
                                protected readonly Type $aggregateType)
    {
    }

    public function reconstitute(Identity $aggregateId,
                                 ?QueryFilter $queryFilter = null): ?Root
    {
        return $this->reconstituteAggregateRoot($aggregateId, $queryFilter);
    }
}
