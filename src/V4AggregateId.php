<?php

declare(strict_types=1);

namespace Chronhub\Aggregate;

use Symfony\Component\Uid\Uuid;
use Chronhub\Contracts\Aggregate\Identity;

final class V4AggregateId implements Identity
{
    use HasAggregateIdentity;

    /**
     * Create new instance of aggregate id
     *
     * @return static|Identity
     */
    public static function create(): V4AggregateId|Identity
    {
        return new V4AggregateId(Uuid::v4());
    }
}
