<?php

namespace Event\Commands;

use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use PHPUnit\Framework\TestCase;

class UpdateLocationTest extends TestCase
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
     * @var UpdateLocation
     */
    private $updateLocation;

    protected function setUp()
    {
        $this->eventId = '3ed90f18-93a3-4340-981d-12e57efa0211';

        $this->locationId = new LocationId('57738178-28a5-4afb-90c0-fd0beba172a8');

        $this->updateLocation = new UpdateLocation(
            $this->eventId,
            $this->locationId
        );
    }

    /**
     * @test
     */
    public function it_stores_an_event_id()
    {
        $this->assertEquals($this->eventId, $this->updateLocation->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_a_location()
    {
        $this->assertEquals($this->locationId, $this->updateLocation->getLocationId());
    }
}
