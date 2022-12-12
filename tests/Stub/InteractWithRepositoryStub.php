<?php

declare(strict_types=1);

namespace Chronhub\Aggregate\Tests\Stub;

use Chronhub\Contracts\Aggregate\Type;
use Chronhub\Contracts\Aggregate\Cache;
use Chronhub\Contracts\Message\Decorator;
use Chronhub\Contracts\Aggregate\Identity;
use Chronhub\Contracts\Chronicler\Chronicler;
use Chronhub\Contracts\Stream\StreamProducer;
use Chronhub\Aggregate\InteractWithRepository;

final class InteractWithRepositoryStub
{
    use InteractWithRepository;

    private ?AggregateRootStub $someAggregateRoot;

    public function __construct(public readonly Chronicler $chronicler,
                                public readonly StreamProducer $producer,
                                public readonly Cache $cache,
                                protected readonly Type $aggregateType,
                                protected readonly Decorator $messageDecorator)
    {
    }

    public function withReconstituteAggregateRoot(?AggregateRootStub $someAggregateRoot): void
    {
        $this->someAggregateRoot = $someAggregateRoot;
    }

    protected function reconstituteAggregateRoot(Identity $aggregateId): ?AggregateRootStub
    {
        return $this->someAggregateRoot;
    }
}
