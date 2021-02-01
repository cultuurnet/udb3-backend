<?php

namespace CultuurNet\UDB3\PriceInfo;

use PHPUnit\Framework\TestCase;
use ValueObjects\Exception\InvalidNativeArgumentException;

class PriceTest extends TestCase
{
    /**
     * @test
     */
    public function it_only_accepts_values_equal_or_higher_than_zero()
    {
        new Price(0);
        new Price(1050);

        $this->expectException(InvalidNativeArgumentException::class);

        new Price(-1);
    }

    /**
     * @test
     */
    public function it_can_be_created_from_a_float()
    {
        $price = Price::fromFloat(10.55);
        $this->assertEquals(1055, $price->toNative());
    }

    /**
     * @test
     */
    public function it_can_be_created_from_a_float_with_a_single_number_after_the_decimal_point()
    {
        $price = Price::fromFloat(10.5);
        $this->assertEquals(1050, $price->toNative());
    }

    /**
     * @test
     */
    public function it_can_be_created_from_a_float_with_many_numbers_after_the_decimal_point()
    {
        $price = Price::fromFloat(3.14159265359);
        $this->assertEquals(314, $price->toNative());
    }
}
