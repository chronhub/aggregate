<?php

declare(strict_types=1);

namespace Chronhub\Aggregate\Tests\Unit;

use Generator;
use Chronhub\Aggregate\V4AggregateId;
use Chronhub\Aggregate\NullAggregateCache;
use Chronhub\Aggregate\Tests\UnitTestCase;
use Chronhub\Contracts\Aggregate\Identity;
use Chronhub\Aggregate\Tests\Stub\AggregateRootStub;
use Chronhub\Aggregate\Tests\Stub\AnotherAggregateRootStub;

final class NullAggregateCacheTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider provideAggregateId
     */
    public function it_always_return_null_to_get_aggregate(Identity $aggregateId): void
    {
        $aggregateCache = new NullAggregateCache();

        $this->assertNull($aggregateCache->get($aggregateId));
    }

    /**
     * @test
     * @dataProvider provideAggregateId
     */
    public function it_always_return_null_to_check_if_aggregate_exists_in_cache(Identity $aggregateId): void
    {
        $aggregateCache = new NullAggregateCache();

        $this->assertFalse($aggregateCache->has($aggregateId));
    }

    /**
     * @test
     */
    public function it_always_return_zero_when_counting_aggregates_in_cache(): void
    {
        $aggregateCache = new NullAggregateCache();

        $aggregateCache->put(AggregateRootStub::create(V4AggregateId::create()));
        $aggregateCache->put(AnotherAggregateRootStub::create(V4AggregateId::create()));

        $this->assertEquals(0, $aggregateCache->count());
    }

    /**
     * @test
     */
    public function it_always_return_true_to_flush_aggregate_cache(): void
    {
        $aggregateCache = new NullAggregateCache();

        $this->assertTrue($aggregateCache->flush());

        $aggregateCache->put(AggregateRootStub::create(V4AggregateId::create()));
        $aggregateCache->put(AnotherAggregateRootStub::create(V4AggregateId::create()));

        $this->assertTrue($aggregateCache->flush());
    }

    public function provideAggregateId(): Generator
    {
        yield [V4AggregateId::create()];
        yield [V4AggregateId::create()];
        yield [V4AggregateId::create()];
    }
}
