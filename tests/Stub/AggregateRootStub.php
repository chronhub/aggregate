<?php

declare(strict_types=1);

namespace Chronhub\Aggregate\Tests\Stub;

use Chronhub\Contracts\Aggregate\Root;
use Chronhub\Contracts\Aggregate\Identity;
use Chronhub\Aggregate\HasAggregateBehaviour;
use Chronhub\Aggregate\Tests\Double\SomeEvent;
use function count;

class AggregateRootStub implements Root
{
    use HasAggregateBehaviour;

    private int $appliedEvents = 0;

    public static function create(Identity $aggregateId, SomeEvent ...$events): self
    {
        $aggregateRoot = new self($aggregateId);

        foreach ($events as $event) {
            $aggregateRoot->recordThat($event);
        }

        return $aggregateRoot;
    }

    public function countRecordedEvents(): int
    {
        return count($this->recordedEvents);
    }

    public function getAppliedEvents(): int
    {
        return $this->appliedEvents;
    }

    protected function applySomeEvent(SomeEvent $event): void
    {
        $this->appliedEvents++;
    }
}
