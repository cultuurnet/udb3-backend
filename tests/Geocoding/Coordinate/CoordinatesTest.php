<?php

namespace CultuurNet\UDB3\Geocoding\Coordinate;

class CoordinatesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_can_be_compared_to_another_instance_of_coordinates()
    {
        $coordinates = new Coordinates(
            new Latitude(1.07845),
            new Longitude(2.76412)
        );

        $sameCoordinates = new Coordinates(
            new Latitude(1.07845),
            new Longitude(2.76412)
        );

        $otherCoordinates = new Coordinates(
            new Latitude(4.07845),
            new Longitude(2.76412)
        );

        $this->assertTrue($coordinates->sameAs($sameCoordinates));
        $this->assertFalse($coordinates->sameAs($otherCoordinates));
    }

    /**
     * @test
     * @dataProvider validLatLonStringProvider
     *
     * @param string $latLonString
     * @param Coordinates $expectedCoordinates
     */
    public function it_can_be_created_from_a_valid_lat_lon_string(
        $latLonString,
        Coordinates $expectedCoordinates
    ) {
        $coordinates = Coordinates::fromLatLonString($latLonString);
        $this->assertEquals($expectedCoordinates, $coordinates);
    }

    /**
     * @test
     * @dataProvider invalidLatLonStringProvider
     *
     * @param string $latLonString
     */
    public function it_throws_an_exception_if_the_given_string_is_invalid($latLonString)
    {
        $this->expectException(\InvalidArgumentException::class);
        Coordinates::fromLatLonString($latLonString);
    }

    /**
     * @return array
     */
    public function validLatLonStringProvider()
    {
        return [
            [
                "-90,-180",
                new Coordinates(
                    new Latitude(-90.0),
                    new Longitude(-180.0)
                ),
            ],
            [
                "-90, -180",
                new Coordinates(
                    new Latitude(-90.0),
                    new Longitude(-180.0)
                ),
            ],
            [
                "90,180",
                new Coordinates(
                    new Latitude(90.0),
                    new Longitude(180.0)
                ),
            ],
            [
                "90, 180",
                new Coordinates(
                    new Latitude(90.0),
                    new Longitude(180.0)
                ),
            ],
            [
                "+90,+180",
                new Coordinates(
                    new Latitude(90.0),
                    new Longitude(180.0)
                ),
            ],
            [
                "+90, +180",
                new Coordinates(
                    new Latitude(90.0),
                    new Longitude(180.0)
                ),
            ],
            [
                "-90,+180",
                new Coordinates(
                    new Latitude(-90.0),
                    new Longitude(180.0)
                ),
            ],
            [
                "-90, +180",
                new Coordinates(
                    new Latitude(-90.0),
                    new Longitude(180.0)
                ),
            ],
            [
                " -90 , +180 ",
                new Coordinates(
                    new Latitude(-90.0),
                    new Longitude(180.0)
                ),
            ],
            [
                " 18.555 , -45.5789 ",
                new Coordinates(
                    new Latitude(18.555),
                    new Longitude(-45.5789)
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    public function invalidLatLonStringProvider()
    {
        return [
            ['-45|90'],
            ['-45,456 , 90,56789'],
            ['-91 , 90.56789'],
            ['-45.456 , 181'],
        ];
    }
}
