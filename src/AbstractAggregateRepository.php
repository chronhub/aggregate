<?php

declare(strict_types=1);

namespace Chronhub\Aggregate;

use Chronhub\Contracts\Aggregate\Type;
use Chronhub\Contracts\Aggregate\Cache;
use Chronhub\Contracts\Message\Decorator;
use Chronhub\Contracts\Aggregate\Repository;
use Chronhub\Contracts\Chronicler\Chronicler;
use Chronhub\Contracts\Stream\StreamProducer;

abstract class AbstractAggregateRepository implements Repository
{
    use ReconstituteAggregate;
    use InteractWithRepository;

    public function __construct(
        public readonly Chronicler $chronicler,
        public readonly StreamProducer $producer,
        public readonly Cache $cache,
        protected readonly Type $aggregateType,
        protected readonly Decorator $messageDecorator
    ) {
    }
}
