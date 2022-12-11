<?php

declare(strict_types=1);

namespace Chronhub\Aggregate;

use Throwable;
use Chronhub\Message\Message;
use Chronhub\Contracts\Aggregate\Root;
use Chronhub\Contracts\Aggregate\Identity;
use Chronhub\Contracts\Message\DomainEvent;
use Chronhub\Contracts\Message\EventHeader;
use function count;
use function reset;
use function array_map;

trait InteractWithRepository
{
    public function retrieve(Identity $aggregateId): ?Root
    {
        if ($this->cache->has($aggregateId)) {
            return $this->cache->get($aggregateId);
        }

        $aggregateRoot = $this->reconstituteAggregateRoot($aggregateId);

        if ($aggregateRoot) {
            $this->cache->put($aggregateRoot);
        }

        return $aggregateRoot;
    }

    public function persist(Root $aggregateRoot): void
    {
        $this->aggregateType->isSupported($aggregateRoot::class);

        $events = $this->releaseDecoratedEvents($aggregateRoot);

        $firstEvent = reset($events);

        if (! $firstEvent) {
            return;
        }

        $this->storeStream($firstEvent, $aggregateRoot, $events);
    }

    /**
     * Store stream conditionally of strategy used
     *
     * @throws Throwable
     */
    protected function storeStream(DomainEvent $firstEvent, Root $aggregateRoot, array $releasedEvents): void
    {
        $stream = $this->producer->toStream($aggregateRoot->aggregateId(), $releasedEvents);

        try {
            $this->producer->isFirstCommit($firstEvent)
                ? $this->chronicler->firstCommit($stream)
                : $this->chronicler->amend($stream);

            $this->cache->put($aggregateRoot);
        } catch (Throwable $exception) {
            $this->cache->forget($aggregateRoot->aggregateId());

            throw $exception;
        }
    }

    /**
     * Release and decorate recorded domain events
     *
     * @return array<DomainEvent>
     */
    protected function releaseDecoratedEvents(Root $aggregateRoot): array
    {
        $events = $aggregateRoot->releaseEvents();

        if (count($events) === 0) {
            return [];
        }

        $version = $aggregateRoot->version() - count($events);

        $aggregateId = $aggregateRoot->aggregateId();

        $headers = [
            EventHeader::AGGREGATE_ID => (string) $aggregateId,
            EventHeader::AGGREGATE_ID_TYPE => $aggregateId::class,
            EventHeader::AGGREGATE_TYPE => $aggregateRoot::class,
        ];

        return array_map(function (DomainEvent $event) use ($headers, &$version) {
            return $this->messageDecorator->decorate(
                new Message($event, $headers + [EventHeader::AGGREGATE_VERSION => ++$version])
            )->event();
        }, $events);
    }
}
