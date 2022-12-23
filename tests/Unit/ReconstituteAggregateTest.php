<?php

declare(strict_types=1);

namespace Chronhub\Aggregate\Tests\Unit;

use Generator;
use Chronhub\Aggregate\V4AggregateId;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Contracts\Aggregate\Type;
use Chronhub\Stream\GenericStreamName;
use Chronhub\Testing\ProphecyTestCase;
use Chronhub\Contracts\Stream\StreamName;
use Chronhub\Contracts\Aggregate\Identity;
use Chronhub\Contracts\Message\DomainEvent;
use Chronhub\Contracts\Chronicler\Chronicler;
use Chronhub\Contracts\Stream\StreamProducer;
use Chronhub\Contracts\Chronicler\QueryFilter;
use Chronhub\Testing\Double\Message\SomeEvent;
use Chronhub\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Aggregate\Tests\Stub\AggregateRootStub;
use Chronhub\Aggregate\Tests\Stub\ReconstituteAggregateRootStub;
use function count;
use function iterator_to_array;

final class ReconstituteAggregateTest extends ProphecyTestCase
{
    private Chronicler|ObjectProphecy $chronicler;

    private StreamProducer|ObjectProphecy $streamProducer;

    private Type|ObjectProphecy $aggregateType;

    private Identity|ObjectProphecy $someIdentity;

    private StreamName $streamName;

    private string $identityString = '9ef864f7-43e2-48c8-9944-639a2d927a06';

    public function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->prophesize(Chronicler::class);
        $this->streamProducer = $this->prophesize(StreamProducer::class);
        $this->aggregateType = $this->prophesize(Type::class);
        $this->someIdentity = V4AggregateId::fromString($this->identityString);
        $this->streamName = new GenericStreamName('balance');
    }

    /**
     * @test
     */
    public function it_reconstitute_aggregate_root_from_history(): void
    {
        $events = iterator_to_array($this->provideDummyEvents());
        $countEvents = count($events);

        $this->streamProducer
            ->toStreamName($this->identityString)
            ->willReturn($this->streamName)
            ->shouldBeCalledOnce();

        $this->aggregateType
            ->from($events[0])
            ->willReturn(AggregateRootStub::class)
            ->shouldBeCalledOnce();

        $this->chronicler
            ->retrieveAll($this->streamName, $this->someIdentity)
            ->willYield($events, $countEvents)
            ->shouldBeCalledOnce();

        $stub = new ReconstituteAggregateRootStub(
            $this->chronicler->reveal(),
            $this->streamProducer->reveal(),
            $this->aggregateType->reveal()
        );

        $reconstituteAggregateRoot = $stub->reconstitute($this->someIdentity);

        $this->assertInstanceOf(AggregateRootStub::class, $reconstituteAggregateRoot);
        $this->assertEquals($countEvents, $reconstituteAggregateRoot->version());
        $this->assertEquals($countEvents, $reconstituteAggregateRoot->getAppliedEvents());
    }

    /**
     * @test
     */
    public function it_reconstitute_aggregate_root_from_filtered_history(): void
    {
        $events = iterator_to_array($this->provideDummyEvents());

        $this->streamProducer
            ->toStreamName($this->identityString)
            ->willReturn($this->streamName)
            ->shouldBeCalledOnce();

        $this->aggregateType
            ->from($events[0])
            ->willReturn(AggregateRootStub::class)
            ->shouldBeCalledOnce();

        $queryFilter = $this->prophesize(QueryFilter::class)->reveal();

        $this->chronicler
            ->retrieveFiltered($this->streamName, $queryFilter)
            ->willYield([$events[0], $events[1]], 2)
            ->shouldBeCalledOnce();

        $stub = new ReconstituteAggregateRootStub(
            $this->chronicler->reveal(),
            $this->streamProducer->reveal(),
            $this->aggregateType->reveal()
        );

        /** @var AggregateRootStub $reconstituteAggregateRoot */
        $reconstituteAggregateRoot = $stub->reconstitute($this->someIdentity, $queryFilter);

        $this->assertInstanceOf(AggregateRootStub::class, $reconstituteAggregateRoot);
        $this->assertEquals(2, $reconstituteAggregateRoot->version());
        $this->assertEquals(2, $reconstituteAggregateRoot->getAppliedEvents());
    }

    /**
     * @test
     */
    public function it_return_null_aggregate_root_from_empty_history(): void
    {
        $events = [];

        $this->streamProducer
            ->toStreamName($this->identityString)
            ->willReturn($this->streamName)
            ->shouldBeCalledOnce();

        $this->aggregateType
            ->from(AggregateRootStub::class)
            ->shouldNotBeCalled();

        $this->chronicler
            ->retrieveAll($this->streamName, $this->someIdentity)
            ->willYield($events, 0)
            ->shouldBeCalledOnce();

        $stub = new ReconstituteAggregateRootStub(
            $this->chronicler->reveal(),
            $this->streamProducer->reveal(),
            $this->aggregateType->reveal()
        );

        $reconstituteAggregateRoot = $stub->reconstitute($this->someIdentity);

        $this->assertNull($reconstituteAggregateRoot);
    }

    /**
     * @test
     */
    public function it_return_null_aggregate_root_when_stream_not_found_exception_is_raised_from_chronicler(): void
    {
        $this->streamProducer
            ->toStreamName($this->identityString)
            ->willReturn($this->streamName)
            ->shouldBeCalledOnce();

        $this->aggregateType
            ->from($this->prophesize(DomainEvent::class)->reveal())
            ->shouldNotBeCalled();

        $this->chronicler
            ->retrieveAll($this->streamName, $this->someIdentity)
            ->willThrow(StreamNotFound::withStreamName($this->streamName))
            ->shouldBeCalledOnce();

        $stub = new ReconstituteAggregateRootStub(
            $this->chronicler->reveal(),
            $this->streamProducer->reveal(),
            $this->aggregateType->reveal()
        );

        $reconstituteAggregateRoot = $stub->reconstitute($this->someIdentity);

        $this->assertNull($reconstituteAggregateRoot);
    }

    private function provideDummyEvents(): Generator
    {
        yield from [
            SomeEvent::fromContent(['count' => 1]),
            SomeEvent::fromContent(['count' => 2]),
            SomeEvent::fromContent(['count' => 3]),
            SomeEvent::fromContent(['count' => 4]),
        ];

        return 4;
    }
}
