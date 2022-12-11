<?php

declare(strict_types=1);

namespace Chronhub\Aggregate;

use Symfony\Component\Uid\Uuid;
use Chronhub\Contracts\Aggregate\Identity;

/**
 * @property-read Uuid $identifier
 */
trait HasAggregateIdentity
{
    protected function __construct(public readonly Uuid $identifier)
    {
    }

    public static function fromString(string $aggregateId): static|self
    {
        return new static(Uuid::fromString($aggregateId));
    }

    public function equalsTo(Identity $rootId): bool
    {
        return $this->identifier->equals($rootId->identifier);
    }

    public function toString(): string
    {
        return $this->identifier->jsonSerialize();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
