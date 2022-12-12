<?php

declare(strict_types=1);

namespace Chronhub\Aggregate\Tests\Unit;

use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Contracts\Aggregate\Type;
use Chronhub\Contracts\Aggregate\Cache;
use Chronhub\Contracts\Message\Envelop;
use Chronhub\Contracts\Message\Decorator;
use Chronhub\Aggregate\AggregateRepository;
use Chronhub\Contracts\Chronicler\Chronicler;
use Chronhub\Contracts\Stream\StreamProducer;
use Chronhub\Aggregate\Tests\ProphecyTestCase;

final class AggregateRepositoryTest extends ProphecyTestCase
{
    private Chronicler|ObjectProphecy $chronicler;

    private StreamProducer|ObjectProphecy $streamProducer;

    private Type|ObjectProphecy $aggregateType;

    private Cache|ObjectProphecy $aggregateCache;

    public function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->prophesize(Chronicler::class);
        $this->streamProducer = $this->prophesize(StreamProducer::class);
        $this->aggregateType = $this->prophesize(Type::class);
        $this->aggregateCache = $this->prophesize(Cache::class);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $repository = new AggregateRepository(
            $this->chronicler->reveal(),
            $this->streamProducer->reveal(),
            $this->aggregateCache->reveal(),
            $this->aggregateType->reveal(),
            new class implements Decorator
            {
                public function decorate(Envelop $message): Envelop
                {
                    return $message;
                }
            }
        );

        $this->assertSame($this->chronicler->reveal(), $repository->chronicler);
        $this->assertSame($this->streamProducer->reveal(), $repository->producer);
        $this->assertSame($this->aggregateCache->reveal(), $repository->cache);
    }
}
