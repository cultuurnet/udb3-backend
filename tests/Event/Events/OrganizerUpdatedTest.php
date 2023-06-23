<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;

class OrganizerUpdatedTest extends TestCase
{
    public function serializationDataProvider(): array
    {
        return [
            [
                [
                    'item_id' => 'event-123',
                    'organizerId' => 'organizer-456',
                ],
                new OrganizerUpdated(
                    'event-123',
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
    ): void {
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
    ): void {
        $this->assertEquals(
            $expectedOrganizerUpdated,
            OrganizerUpdated::deserialize($serializedValue)
        );
    }
}
