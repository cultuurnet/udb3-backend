<?php

namespace CultuurNet\UDB3\Geocoding\Coordinate;

class LongitudeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_does_not_accept_a_double_under_negative_180()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Longitude(-180.1);
    }

    /**
     * @test
     */
    public function it_does_not_accept_a_double_over_180()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Longitude(180.1);
    }

    /**
     * @test
     */
    public function it_accepts_any_doubles_between_negative_180_and_180()
    {
        new Longitude(-180.0);
        new Longitude(-5.123456789);
        new Longitude(-0.25);
        new Longitude(0.0);
        new Longitude(0.25);
        new Longitude(5.123456789);
        new Longitude(180.0);
    }
}
