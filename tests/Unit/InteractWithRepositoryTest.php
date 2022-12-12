<?php

declare(strict_types=1);

namespace Chronhub\Aggregate\Tests\Unit;

use Generator;
use RuntimeException;
use Prophecy\Argument;
use Chronhub\Stream\GenericStream;
use Chronhub\Aggregate\V4AggregateId;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Contracts\Aggregate\Type;
use Chronhub\Stream\GenericStreamName;
use Chronhub\Contracts\Aggregate\Cache;
use Chronhub\Contracts\Message\Envelop;
use Chronhub\Contracts\Message\Decorator;
use Chronhub\Contracts\Stream\StreamName;
use Chronhub\Contracts\Aggregate\Identity;
use Chronhub\Contracts\Message\DomainEvent;
use Chronhub\Contracts\Message\EventHeader;
use Chronhub\Contracts\Chronicler\Chronicler;
use Chronhub\Contracts\Stream\StreamProducer;
use Chronhub\Aggregate\Tests\Double\SomeEvent;
use Chronhub\Aggregate\Tests\ProphecyTestCase;
use Chronhub\Aggregate\Tests\Stub\AggregateRootStub;
use Chronhub\Aggregate\Tests\Stub\InteractWithRepositoryStub;
use function iterator_to_array;

final class InteractWithRepositoryTest extends ProphecyTestCase
{
    private Chronicler|ObjectProphecy $chronicler;

    private StreamProducer|ObjectProphecy $streamProducer;

    private Type|ObjectProphecy $aggregateType;

    private Cache|ObjectProphecy $aggregateCache;

    private Identity|ObjectProphecy $someIdentity;

    private StreamName $streamName;

    private string $identityString = '9ef864f7-43e2-48c8-9944-639a2d927a06';

    public function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->prophesize(Chronicler::class);
        $this->streamProducer = $this->prophesize(StreamProducer::class);
        $this->aggregateType = $this->prophesize(Type::class);
        $this->aggregateCache = $this->prophesize(Cache::class);
        $this->someIdentity = V4AggregateId::fromString($this->identityString);
        $this->streamName = new GenericStreamName('operation');
    }

    /**
     * @test
     */
    public function it_assert_stub_accessor(): void
    {
        $stub = $this->aggregateRepositoryStub(null);

        $this->assertEquals($this->chronicler->reveal(), $stub->chronicler);
        $this->assertEquals($this->aggregateCache->reveal(), $stub->cache);
        $this->assertEquals($this->streamProducer->reveal(), $stub->producer);
    }

    /**
     * @test
     */
    public function it_retrieve_aggregate_from_cache(): void
    {
        $expectedAggregateRoot = AggregateRootStub::create($this->someIdentity);

        $this->aggregateCache->has($this->someIdentity)->willReturn(true)->shouldBeCalledOnce();
        $this->aggregateCache->get($this->someIdentity)->willReturn($expectedAggregateRoot);

        $stub = $this->aggregateRepositoryStub(null);

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertEquals($expectedAggregateRoot, $aggregateRoot);
    }

    /**
     * @test
     */
    public function it_reconstitute_aggregate_if_aggregate_does_not_exist_already_in_cache_and_put_in_cache_(): void
    {
        $expectedAggregateRoot = AggregateRootStub::create($this->someIdentity);

        $this->aggregateCache->has($this->someIdentity)->willReturn(false)->shouldBeCalledOnce();
        $this->aggregateCache->get($this->someIdentity)->shouldNotBeCalled();
        $this->aggregateCache->put($expectedAggregateRoot)->shouldBeCalledOnce();

        $stub = $this->aggregateRepositoryStub(null);
        $stub->withReconstituteAggregateRoot($expectedAggregateRoot);

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertEquals($expectedAggregateRoot, $aggregateRoot);
    }

    /**
     * @test
     */
    public function it_does_not_put_in_cache_if_reconstitute_aggregate_return_null_aggregate(): void
    {
        $expectedAggregateRoot = AggregateRootStub::create($this->someIdentity);

        $this->aggregateCache->has($this->someIdentity)->willReturn(false)->shouldBeCalledOnce();
        $this->aggregateCache->get($this->someIdentity)->shouldNotBeCalled();
        $this->aggregateCache->put($expectedAggregateRoot)->shouldNotBeCalled();

        $stub = $this->aggregateRepositoryStub(null);
        $stub->withReconstituteAggregateRoot(null);

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertNull($aggregateRoot);
    }

    /**
     * @test
     */
    public function it_forget_aggregate_from_cache_if_persist_first_commit_raise_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $exception = new RuntimeException('foo');

        $events = [SomeEvent::fromContent(['foo' => 'bar'])];

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->isSupported($aggregateRoot::class)->shouldBeCalledOnce();

        $stream = new GenericStream($this->streamName, $events);

        $this->streamProducer
            ->toStream($this->someIdentity, Argument::type('array'))
            ->willReturn($stream)
            ->shouldBeCalledOnce();

        $this->streamProducer->isFirstCommit(Argument::type(SomeEvent::class))->willReturn(true)->shouldBeCalledOnce();
        $this->chronicler->firstCommit($stream)
            ->willThrow($exception)
            ->shouldBeCalledOnce();

        $this->aggregateCache->forget($this->someIdentity)->shouldBeCalledOnce();

        $stub = $this->aggregateRepositoryStub(null);

        $stub->persist($aggregateRoot);
    }

    /**
     * @test
     */
    public function it_forget_aggregate_from_cache_if_persist_raise_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $exception = new RuntimeException('foo');

        $events = [SomeEvent::fromContent(['foo' => 'bar'])];

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->isSupported($aggregateRoot::class)->shouldBeCalledOnce();

        $stream = new GenericStream($this->streamName, $events);

        $this->streamProducer
            ->toStream($this->someIdentity, Argument::type('array'))
            ->willReturn($stream)
            ->shouldBeCalledOnce();

        $this->streamProducer
            ->isFirstCommit(Argument::type(DomainEvent::class))
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $this->chronicler->amend($stream)
            ->willThrow($exception)
            ->shouldBeCalledOnce();

        $this->aggregateCache
            ->forget($this->someIdentity)
            ->shouldBeCalledOnce();

        $stub = $this->aggregateRepositoryStub(null);

        $stub->persist($aggregateRoot);
    }

    /**
     * @test
     */
    public function it_does_not_persist_aggregate_with_empty_domain_events_to_release(): void
    {
        $events = [];

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->isSupported($aggregateRoot::class)->shouldBeCalledOnce();

        $stream = new GenericStream($this->streamName, $events);

        $this->streamProducer->toStream($this->someIdentity, $events)->shouldNotBeCalled();
        $this->streamProducer->isFirstCommit($this->prophesize(DomainEvent::class)->reveal())->shouldNotBeCalled();
        $this->chronicler->firstCommit($stream)->shouldNotBeCalled();
        $this->chronicler->amend($stream)->shouldNotBeCalled();
        $this->aggregateCache->forget($this->someIdentity)->shouldNotBeCalled();

        $stub = $this->aggregateRepositoryStub(null);

        $stub->persist($aggregateRoot);
    }

    /**
     * @test
     * @dataProvider provideMessageDecoratorOrNull
     */
    public function it_persists_aggregate_root_with_first_commit_and_decorate_domain_events(?Decorator $messageDecorator): void
    {
        $events = iterator_to_array($this->provideFourDomainEvents());

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->isSupported($aggregateRoot::class)->shouldBeCalledOnce();

        $stream = new GenericStream($this->streamName, $events);

        $this->streamProducer
            ->toStream($this->someIdentity, Argument::that(function (array $events) use ($messageDecorator): array {
                $position = 0;

                foreach ($events as $event) {
                    $eventHeaders = $event->headers();

                    $expectedHeaders = [
                        EventHeader::AGGREGATE_ID => $this->identityString,
                        EventHeader::AGGREGATE_ID_TYPE => V4AggregateId::class,
                        EventHeader::AGGREGATE_TYPE => AggregateRootStub::class,
                        EventHeader::AGGREGATE_VERSION => $position + 1,
                    ];

                    if ($messageDecorator) {
                        $expectedHeaders['some'] = 'header';
                    }

                    $this->assertEquals($expectedHeaders, $eventHeaders);

                    $position++;
                }

                $this->assertEquals(4, $position);

                return $events;
            }))
            ->willReturn($stream)
            ->shouldBeCalledOnce();

        $this->streamProducer->isFirstCommit(Argument::type(SomeEvent::class))->willReturn(true)->shouldBeCalledOnce();
        $this->chronicler->firstCommit($stream)->shouldBeCalledOnce();
        $this->aggregateCache->put($aggregateRoot)->shouldBeCalledOnce();

        $stub = $this->aggregateRepositoryStub($messageDecorator);

        $stub->persist($aggregateRoot);
    }

    /**
     * @test
     * @dataProvider provideMessageDecoratorOrNull
     */
    public function it_persists_aggregate_root_and_decorate_domain_events(?Decorator $messageDecorator): void
    {
        /** @var AggregateRootStub $aggregateRoot */
        $aggregateRoot = AggregateRootStub::reconstitute($this->someIdentity, $this->provideFourDomainEvents());

        $this->assertEquals(4, $aggregateRoot->version());

        $events = iterator_to_array($this->provideFourDomainEvents());

        $aggregateRoot->recordSomeEvents(...$events);

        $this->assertEquals(8, $aggregateRoot->version());

        $this->aggregateType->isSupported($aggregateRoot::class)->shouldBeCalledOnce();

        $stream = new GenericStream($this->streamName, $events);

        $this->streamProducer
            ->toStream($this->someIdentity, Argument::that(function (array $events) use ($messageDecorator): array {
                $position = 4;

                foreach ($events as $event) {
                    $eventHeaders = $event->headers();

                    $expectedHeaders = [
                        EventHeader::AGGREGATE_ID => $this->identityString,
                        EventHeader::AGGREGATE_ID_TYPE => V4AggregateId::class,
                        EventHeader::AGGREGATE_TYPE => AggregateRootStub::class,
                        EventHeader::AGGREGATE_VERSION => $position + 1,
                    ];

                    if ($messageDecorator) {
                        $expectedHeaders['some'] = 'header';
                    }

                    $this->assertEquals($expectedHeaders, $eventHeaders);

                    $position++;
                }

                $this->assertEquals(8, $position);

                return $events;
            }))
            ->willReturn($stream)
            ->shouldBeCalledOnce();

        $this->streamProducer->isFirstCommit(Argument::type(SomeEvent::class))->willReturn(false)->shouldBeCalledOnce();
        $this->chronicler->amend($stream)->shouldBeCalledOnce();
        $this->aggregateCache->put($aggregateRoot)->shouldBeCalledOnce();

        $stub = $this->aggregateRepositoryStub($messageDecorator);

        $stub->persist($aggregateRoot);
    }

    public function provideMessageDecoratorOrNull(): Generator
    {
        yield[null];

        yield[
            new class implements Decorator
            {
                public function decorate(Envelop $message): Envelop
                {
                    return $message->withHeader('some', 'header');
                }
            },
        ];
    }

    public function provideFourDomainEvents(): Generator
    {
        yield from [
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
        ];

        return 4;
    }

    private function aggregateRepositoryStub(?Decorator $messageDecorator): InteractWithRepositoryStub
    {
        return new InteractWithRepositoryStub(
            $this->chronicler->reveal(),
            $this->streamProducer->reveal(),
            $this->aggregateCache->reveal(),
            $this->aggregateType->reveal(),
            $messageDecorator ?? new class implements Decorator
            {
                public function decorate(Envelop $message): Envelop
                {
                    return $message;
                }
            }
        );
    }
}
