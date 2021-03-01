<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

use PHPUnit\Framework\TestCase;

class AgeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_never_be_lower_than_zero()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given integer should be greater or equal to zero. Got -1 instead.');

        new Age(-1);
    }

    /**
     * @test
     */
    public function it_should_return_an_integer()
    {
        $age = new Age(10);
        $this->assertEquals(10, $age->toInteger());
    }

    /**
     * @test
     */
    public function it_should_be_allowed_to_be_zero()
    {
        $age = new Age(0);
        $this->assertEquals(0, $age->toInteger());
    }
}
