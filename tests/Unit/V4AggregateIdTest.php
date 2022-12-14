<?php

declare(strict_types=1);

namespace Chronhub\Aggregate\Tests\Unit;

use Symfony\Component\Uid\Uuid;
use Chronhub\Testing\UnitTestCase;
use Chronhub\Aggregate\V4AggregateId;
use Chronhub\Contracts\Aggregate\Identity;

final class V4AggregateIdTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_created(): void
    {
        $aggregateId = V4AggregateId::create();

        $this->assertInstanceOf(Identity::class, $aggregateId);

        $this->assertInstanceOf(Uuid::class, $aggregateId->identifier);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_from_string(): void
    {
        $aggregateId = V4AggregateId::create();

        $fromString = V4AggregateId::fromString((string) $aggregateId);

        $this->assertEquals($aggregateId, $fromString);
        $this->assertNotSame($aggregateId, $fromString);
    }

    /**
     * @test
     */
    public function it_can_be_compared(): void
    {
        $aggregateId = V4AggregateId::create();
        $anotherAggregateId = V4AggregateId::create();

        $this->assertNotSame($aggregateId, $anotherAggregateId);
        $this->assertFalse($aggregateId->equalsTo($anotherAggregateId));
        $this->assertFalse($anotherAggregateId->equalsTo($aggregateId));
        $this->assertTrue($aggregateId->equalsTo($aggregateId));
        $this->assertTrue($anotherAggregateId->equalsTo($anotherAggregateId));
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $aggregateId = V4AggregateId::fromString('99533317-44b3-48cc-9148-f385eddb73e9');

        $this->assertEquals('99533317-44b3-48cc-9148-f385eddb73e9', $aggregateId->toString());
        $this->assertEquals('99533317-44b3-48cc-9148-f385eddb73e9', (string) $aggregateId);
    }
}
