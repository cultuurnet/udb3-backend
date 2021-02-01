<?php

namespace CultuurNet\UDB3\Place\Events;

use PHPUnit\Framework\TestCase;

class OrganizerUpdatedTest extends TestCase
{
    public function serializationDataProvider()
    {
        return [
            [
                [
                    'item_id' => 'place123',
                    'organizerId' => 'organizer-456',
                ],
                new OrganizerUpdated(
                    'place123',
                    'organizer-456'
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        OrganizerUpdated $organizerUpdated
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $organizerUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        OrganizerUpdated $expectedOrganizerUpdated
    ) {
        $this->assertEquals(
            $expectedOrganizerUpdated,
            OrganizerUpdated::deserialize($serializedValue)
        );
    }
}
