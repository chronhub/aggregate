<?php

declare(strict_types=1);

namespace Chronhub\Aggregate;

use Generator;
use Chronhub\Contracts\Aggregate\Root;
use Chronhub\Contracts\Aggregate\Identity;
use Chronhub\Contracts\Message\DomainEvent;
use function end;
use function explode;

trait HasAggregateBehaviour
{
    /**
     * Aggregate root version
     *
     * @var int
     */
    private int $version = 0;

    /**
     * Recorded domain events
     *
     * @var array<DomainEvent>
     */
    private array $recordedEvents = [];

    protected function __construct(private readonly Identity $aggregateId)
    {
    }

    public function aggregateId(): Identity
    {
        return $this->aggregateId;
    }

    public function version(): int
    {
        return $this->version;
    }

    protected function recordThat(DomainEvent $event): void
    {
        $this->apply($event);

        $this->recordedEvents[] = $event;
    }

    /**
     * Apply domain events to aggregate root
     *
     * @param  DomainEvent  $event
     * @return void
     */
    protected function apply(DomainEvent $event): void
    {
        $parts = explode('\\', $event::class);

        $this->{'apply'.end($parts)}($event);

        $this->version++;
    }

    public function releaseEvents(): array
    {
        $releasedEvents = $this->recordedEvents;

        $this->recordedEvents = [];

        return $releasedEvents;
    }

    public static function reconstitute(Identity $aggregateId, Generator $events): ?Root
    {
        /** @var Root $aggregateRoot */
        $aggregateRoot = new static($aggregateId);

        foreach ($events as $event) {
            $aggregateRoot->apply($event);
        }

        $aggregateRoot->version = (int) $events->getReturn();

        return $aggregateRoot->version() > 0 ? $aggregateRoot : null;
    }
}
