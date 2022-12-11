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

    public function tryFrom(Root|string|DomainEvent $event): string
    {
        if ($event instanceof DomainEvent) {
            $aggregateType = $event->header(EventHeader::AGGREGATE_TYPE);

            $this->isSupported($aggregateType);

            return $aggregateType;
        }

        if ($event instanceof Root) {
            $this->isSupported($event::class);

            return $event::class;
        }

        $this->isSupported($event);

        return $this->concrete;
    }

    public function isSupported(string $aggregateRoot): void
    {
        if (! $this->supportAggregateRoot($aggregateRoot)) {
            throw new InvalidArgumentException("Aggregate root $aggregateRoot class is not supported");
        }
    }

    /**
     * Assert if given aggregate root is supported as a top class or from inheritance
     *
     * @param  string  $aggregateRoot
     * @return bool
     */
    private function supportAggregateRoot(string $aggregateRoot): bool
    {
        if ($aggregateRoot === $this->concrete) {
            return true;
        }

        return in_array($aggregateRoot, $this->map, true);
    }

    public function current(): string
    {
        return $this->concrete;
    }
}
