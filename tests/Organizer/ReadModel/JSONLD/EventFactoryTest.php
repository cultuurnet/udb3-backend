<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\JSONLD;

use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use PHPUnit\Framework\TestCase;

class EventFactoryTest extends TestCase
{
    private EventFactory $factory;

    public function setUp(): void
    {
        $iriGenerator = new CallableIriGenerator(
            function ($id) {
                return 'organizers/' . $id;
            }
        );

        $this->factory = new EventFactory($iriGenerator);
    }

    /**
     * @test
     */
    public function it_creates_an_organizer_projected_to_json_ld_event_with_the_organizer_id(): void
    {
        $id = '0be365fb-d897-410d-81e5-b1bdcad63639';
        $expectedEvent = new OrganizerProjectedToJSONLD($id, 'organizers/' . $id);

        $actualEvent = $this->factory->createEvent($id);

        $this->assertEquals($expectedEvent, $actualEvent);
    }
}
