<?php

declare(strict_types=1);

namespace Chronhub\Aggregate\Tests\Unit;

use Generator;
use Chronhub\Testing\UnitTestCase;
use Chronhub\Aggregate\V4AggregateId;
use Chronhub\Contracts\Aggregate\Root;
use Chronhub\Testing\Double\Message\SomeEvent;
use Chronhub\Aggregate\Tests\Stub\AggregateRootStub;

final class HasAggregateBehaviorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::create($aggregateId);

        $this->assertEquals(0, $aggregateRoot->countRecordedEvents());
        $this->assertEquals(0, $aggregateRoot->getAppliedEvents());
        $this->assertEquals(0, $aggregateRoot->version());
        $this->assertEquals($aggregateId, $aggregateRoot->aggregateId());
    }

    /**
     * @test
     */
    public function it_record_events(): void
    {
        $events = [
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
        ];

        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::create($aggregateId, ...$events);

        $this->assertEquals(3, $aggregateRoot->countRecordedEvents());
        $this->assertEquals(3, $aggregateRoot->version());
        $this->assertEquals(3, $aggregateRoot->getAppliedEvents());
    }

    /**
     * @test
     */
    public function it_release_events(): void
    {
        $events = [
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
        ];

        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::create($aggregateId, ...$events);

        $this->assertEquals(3, $aggregateRoot->getAppliedEvents());
        $this->assertEquals(3, $aggregateRoot->countRecordedEvents());
        $this->assertEquals(3, $aggregateRoot->version());

        $releasedEvents = $aggregateRoot->releaseEvents();

        $this->assertEquals(0, $aggregateRoot->countRecordedEvents());
        $this->assertEquals($events, $releasedEvents);
    }

    /**
     * @test
     */
    public function it_reconstitute_aggregate_from_events(): void
    {
        $events = $this->provideDomainEvents();

        $aggregateId = V4AggregateId::create();

        /** @var AggregateRootStub $aggregateRoot */
        $aggregateRoot = AggregateRootStub::reconstitute($aggregateId, $events);

        $this->assertInstanceOf(Root::class, $aggregateRoot);
        $this->assertInstanceOf(AggregateRootStub::class, $aggregateRoot);

        $this->assertEquals(3, $aggregateRoot->version());
        $this->assertEquals(3, $aggregateRoot->getAppliedEvents());
    }

    /**
     * @test
     */
    public function it_return_null_aggregate_when_reconstitute_with_empty_events(): void
    {
        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::reconstitute($aggregateId, $this->provideEmptyEvents());

        $this->assertNull($aggregateRoot);
    }

    /**
     * @test
     */
    public function it_return_null_aggregate_when_reconstitute_with_no_get_return_from_generator(): void
    {
        $aggregateId = V4AggregateId::create();

        $aggregateRoot = AggregateRootStub::reconstitute($aggregateId, $this->provideEventsWithNoReturn());

        $this->assertNull($aggregateRoot);
    }

    public function provideDomainEvents(): Generator
    {
        yield from [
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
        ];

        return 3;
    }

    public function provideEventsWithNoReturn(): Generator
    {
        yield from [
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
        ];
    }

    public function provideEmptyEvents(): Generator
    {
        yield from [];

        return 0;
    }
}
