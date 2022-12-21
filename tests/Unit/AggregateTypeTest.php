<?php

declare(strict_types=1);

namespace Chronhub\Aggregate\Tests\Unit;

use stdClass;
use Generator;
use InvalidArgumentException;
use Chronhub\Testing\UnitTestCase;
use Chronhub\Aggregate\AggregateType;
use Chronhub\Aggregate\V4AggregateId;
use Chronhub\Contracts\Message\DomainEvent;
use Chronhub\Contracts\Message\EventHeader;
use Chronhub\Testing\Double\Message\SomeEvent;
use Chronhub\Aggregate\Tests\Stub\AggregateRootStub;
use Chronhub\Aggregate\Tests\Stub\AggregateRootChildStub;
use Chronhub\Aggregate\Tests\Stub\AnotherAggregateRootStub;

final class AggregateTypeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $aggregateType = new AggregateType(AnotherAggregateRootStub::class);

        $this->assertEquals(AnotherAggregateRootStub::class, $aggregateType->current());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_aggregate_root_is_not_a_valid_class_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aggregate root must be a FQCN');

        new AggregateType('invalid_class');
    }

    /**
     * @test
     */
    public function it_raise_exception_when_children_are_not_subclass_of_aggregate_root(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class '.stdClass::class.' must inherit from '.AnotherAggregateRootStub::class);

        new AggregateType(AnotherAggregateRootStub::class, [stdClass::class]);
    }

    /**
     * @test
     * @dataProvider provideValidAggregateTypeHeader
     */
    public function it_support_aggregate_root(string $aggregateTypeHeader): void
    {
        $aggregateType = new AggregateType(
            AggregateRootStub::class, [AggregateRootChildStub::class]
        );

        $domainEvent = SomeEvent::fromContent([])
            ->withHeaders([EventHeader::AGGREGATE_TYPE => $aggregateTypeHeader]);

        /** @var DomainEvent $domainEvent */
        $aggregateRoot = $aggregateType->tryFrom($domainEvent);

        $this->assertEquals($aggregateTypeHeader, $aggregateRoot);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_aggregate_root_is_not_supported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aggregate root '.AnotherAggregateRootStub::class.' class is not supported');

        $aggregateType = new AggregateType(AggregateRootStub::class, [AggregateRootChildStub::class]);

        $aggregateType->isSupported(AnotherAggregateRootStub::class);
    }

    /**
     * @test
     */
    public function it_determine_type_from_aggregate_root_object(): void
    {
        $aggregateType = new AggregateType(AggregateRootStub::class, [AggregateRootChildStub::class]);

        $this->assertEquals(
            AggregateRootStub::class,
            $aggregateType->tryFrom(AggregateRootStub::create(V4AggregateId::create()))
        );

        $this->assertEquals(
            AggregateRootStub::class,
            $aggregateType->tryFrom(AggregateRootChildStub::create(V4AggregateId::create()))
        );
    }

    /**
     * @test
     */
    public function it_determine_type_from_aggregate_root_string_class(): void
    {
        $aggregateType = new AggregateType(
            AggregateRootStub::class, [AggregateRootChildStub::class]
        );

        $this->assertEquals(
            AggregateRootStub::class,
            $aggregateType->tryFrom(AggregateRootStub::class)
        );

        $this->assertEquals(
            AggregateRootStub::class,
            $aggregateType->tryFrom(AggregateRootChildStub::class)
        );
    }

    public function provideValidAggregateTypeHeader(): Generator
    {
        yield [AggregateRootStub::class];
        yield [AggregateRootChildStub::class];
    }
}
