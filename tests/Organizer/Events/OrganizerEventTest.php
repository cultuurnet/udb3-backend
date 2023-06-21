<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

class OrganizerEventTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        MockOrganizerEvent $organizerEvent
    ): void {
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
    ): void {
        $this->assertEquals(
            $expectedUnlabelled,
            MockOrganizerEvent::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
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
