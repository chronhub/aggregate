<?php

declare(strict_types=1);

namespace Chronhub\Aggregate;

use InvalidArgumentException;
use Chronhub\Contracts\Aggregate\Root;
use Chronhub\Contracts\Aggregate\Type;
use Chronhub\Contracts\Message\DomainEvent;
use Chronhub\Contracts\Message\EventHeader;
use function is_a;
use function in_array;
use function class_exists;

final class AggregateType implements Type
{
    public function __construct(private readonly string $concrete,
                                private readonly array $map = [])
    {
        if (! class_exists($concrete)) {
            throw new InvalidArgumentException('Aggregate root must be a FQCN');
        }

        foreach ($map as $className) {
            if (! is_a($className, $this->concrete, true)) {
                throw new InvalidArgumentException("Class $className must inherit from $concrete");
            }
        }
    }

    public function from(Root|string|DomainEvent $event): string
    {
        if ($event instanceof Root) {
            $this->assertAggregateIsSupported($event::class);

            return $event::class;
        }

        if ($event instanceof DomainEvent) {
            $aggregateType = $event->header(EventHeader::AGGREGATE_TYPE);

            $this->assertAggregateIsSupported($aggregateType);

            return $aggregateType;
        }

        $this->assertAggregateIsSupported($event);

        return $this->concrete;
    }

    public function isSupported(string $aggregateRoot): bool
    {
        if ($aggregateRoot === $this->concrete) {
            return true;
        }

        return in_array($aggregateRoot, $this->map, true);
    }

    /**
     * @param  string  $aggregate
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function assertAggregateIsSupported(string $aggregate): void
    {
        if (! $this->isSupported($aggregate)) {
            throw new InvalidArgumentException("Aggregate root $aggregate class is not supported");
        }
    }

    public function current(): string
    {
        return $this->concrete;
    }
}
