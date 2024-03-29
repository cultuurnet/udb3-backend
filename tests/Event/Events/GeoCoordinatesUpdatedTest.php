<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use PHPUnit\Framework\TestCase;

class GeoCoordinatesUpdatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized_and_deserialized(): void
    {
        $expectedEvent = new GeoCoordinatesUpdated(
            'f281bc85-3ee4-43a7-b42d-a8982ec9bbc4',
            new Coordinates(
                new Latitude(0.00456),
                new Longitude(-1.24567)
            )
        );

        $expectedArray = [
            'item_id' => 'f281bc85-3ee4-43a7-b42d-a8982ec9bbc4',
            'coordinates' => [
                'lat' => 0.00456,
                'long' => -1.24567,
            ],
        ];

        $actualArray = $expectedEvent->serialize();

        $event = GeoCoordinatesUpdated::deserialize($expectedArray);

        $this->assertEquals($expectedArray, $actualArray);
        $this->assertEquals($expectedEvent, $event);
    }
}
