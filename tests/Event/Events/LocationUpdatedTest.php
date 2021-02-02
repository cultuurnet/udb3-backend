<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use PHPUnit\Framework\TestCase;

class LocationUpdatedTest extends TestCase
{
    /**
     * @var string
     */
    private $eventId;

    /**
     * @var LocationId
     */
    private $locationId;

    /**
     * @var array
     */
    private $locationUpdatedAsArray;

    /**
     * @var LocationUpdated
     */
    private $locationUpdated;

    protected function setUp()
    {
        $this->eventId = '3ed90f18-93a3-4340-981d-12e57efa0211';

        $this->locationId = new LocationId('57738178-28a5-4afb-90c0-fd0beba172a8');

        $this->locationUpdatedAsArray = [
            'item_id' => $this->eventId,
            'location_id' => '57738178-28a5-4afb-90c0-fd0beba172a8',
        ];

        $this->locationUpdated = new LocationUpdated(
            $this->eventId,
            $this->locationId
        );
    }

    /**
     * @test
     */
    public function it_stores_an_event_id()
    {
        $this->assertEquals($this->eventId, $this->locationUpdated->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_a_location_id()
    {
        $this->assertEquals($this->locationId, $this->locationUpdated->getLocationId());
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $this->assertEquals(
            $this->locationUpdatedAsArray,
            $this->locationUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $this->assertEquals(
            $this->locationUpdated,
            LocationUpdated::deserialize($this->locationUpdatedAsArray)
        );
    }
}
