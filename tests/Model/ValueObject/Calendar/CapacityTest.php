<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CapacityTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_limited(): void
    {
        $capacity = Capacity::limited(30, 12);

        $this->assertSame(CapacityType::Limited, $capacity->getType());
        $this->assertSame(30, $capacity->getMaximum());
        $this->assertSame(12, $capacity->getTaken());
        $this->assertFalse($capacity->isUnknown());
    }

    /**
     * @test
     */
    public function it_can_be_limited_without_taken(): void
    {
        $capacity = Capacity::limited(30);

        $this->assertSame(30, $capacity->getMaximum());
        $this->assertNull($capacity->getTaken());
    }

    /**
     * @test
     */
    public function it_can_be_unlimited(): void
    {
        $capacity = Capacity::unlimited(140);

        $this->assertSame(CapacityType::Unlimited, $capacity->getType());
        $this->assertNull($capacity->getMaximum());
        $this->assertSame(140, $capacity->getTaken());
        $this->assertFalse($capacity->isUnknown());
    }

    /**
     * @test
     */
    public function it_can_be_unlimited_without_taken(): void
    {
        $this->assertNull(Capacity::unlimited()->getTaken());
    }

    /**
     * @test
     */
    public function it_can_be_unknown(): void
    {
        $capacity = Capacity::unknown();

        $this->assertTrue($capacity->isUnknown());
        $this->assertNull($capacity->getType());
        $this->assertNull($capacity->getMaximum());
        $this->assertNull($capacity->getTaken());
    }

    /**
     * @test
     */
    public function it_allows_taken_to_be_higher_than_the_maximum(): void
    {
        $capacity = Capacity::limited(5, 9);

        $this->assertSame(9, $capacity->getTaken());
    }

    /**
     * @test
     */
    public function it_allows_taken_to_be_zero(): void
    {
        $this->assertSame(0, Capacity::limited(5, 0)->getTaken());
    }

    /**
     * @test
     */
    public function it_requires_a_maximum_of_at_least_one(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Capacity::limited(0);
    }

    /**
     * @test
     */
    public function it_does_not_allow_a_negative_taken(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Capacity::unlimited(-1);
    }
}
