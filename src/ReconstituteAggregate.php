<?php

declare(strict_types=1);

namespace Chronhub\Aggregate;

use Generator;
use Chronhub\Contracts\Aggregate\Root;
use Chronhub\Contracts\Aggregate\Identity;
use Chronhub\Contracts\Message\DomainEvent;

trait ReconstituteAggregate
{
    /**
     * Reconstitute aggregate root from his aggregate id and conditionally from a query filter
     *
     * @param  Identity  $aggregateId
     * @param  QueryFilter|null  $queryFilter
     * @return Root|null
     */
    protected function reconstituteAggregateRoot(Identity $aggregateId, ?QueryFilter $queryFilter = null): ?Root
    {
        try {
            $history = $this->fromHistory($aggregateId, $queryFilter);

            if (! $history->valid()) {
                return null;
            }

            /** @var Root $aggregateRoot */
            $aggregateRoot = $this->aggregateType->determineFromEvent($history->current());

            return $aggregateRoot::reconstitute($aggregateId, $history);
        } catch (StreamNotFound) {
            return null;
        }
    }

    /**
     * Retrieve aggregate root events history
     *
     * @param  Identity  $aggregateId
     * @param  QueryFilter|null  $queryFilter
     * @return Generator<DomainEvent>
     *
     * @throws StreamNotFound
     */
    protected function fromHistory(Identity $aggregateId, ?QueryFilter $queryFilter): Generator
    {
        $streamName = $this->producer->toStreamName($aggregateId->toString());

        if ($queryFilter) {
            return $this->chronicler->retrieveFiltered($streamName, $queryFilter);
        }

        return $this->chronicler->retrieveAll($streamName, $aggregateId);
    }
}
