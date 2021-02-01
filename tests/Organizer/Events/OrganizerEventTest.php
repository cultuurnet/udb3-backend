<?php

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

class OrganizerEventTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $expectedSerializedValue
     * @param MockOrganizerEvent $organizerEvent
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        MockOrganizerEvent $organizerEvent
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $organizerEvent->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        MockOrganizerEvent $expectedUnlabelled
    ) {
        $this->assertEquals(
            $expectedUnlabelled,
            MockOrganizerEvent::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider()
    {
        return [
            'organizerEvent' => [
                [
                    'organizer_id' => 'organizer_id',
                ],
                new MockOrganizerEvent(
                    'organizer_id'
                ),
            ],
        ];
    }
}
