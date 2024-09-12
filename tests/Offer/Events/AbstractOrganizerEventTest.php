<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use PHPUnit\Framework\TestCase;

class AbstractOrganizerEventTest extends TestCase
{
    protected AbstractOrganizerEvent $abstractOrganizerEvent;

    protected string $itemId;

    protected string $organizerId;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->organizerId = 'my-organizer-123';
        $this->abstractOrganizerEvent = new MockAbstractOrganizerEvent(
            $this->itemId,
            $this->organizerId
        );
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedorganizerId = 'my-organizer-123';

        $expectedAbstractOrganizerEvent = new MockAbstractOrganizerEvent(
            $expectedItemId,
            $expectedorganizerId
        );

        $this->assertEquals($expectedAbstractOrganizerEvent, $this->abstractOrganizerEvent);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedOrganizerId = 'my-organizer-123';

        $itemId = $this->abstractOrganizerEvent->getItemId();
        $organizerId = $this->abstractOrganizerEvent->getOrganizerId();

        $this->assertEquals($expectedItemId, $itemId);
        $this->assertEquals($expectedOrganizerId, $organizerId);
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        MockAbstractOrganizerEvent $organizerEvent
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
    public function it_can_deserialize_an_array(
        array $serializedValue,
        MockAbstractOrganizerEvent $expectedOrganizerEvent
    ): void {
        $this->assertEquals(
            $expectedOrganizerEvent,
            MockAbstractOrganizerEvent::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'abstractOrganizerEvent' => [
                [
                    'item_id' => 'madId',
                    'organizerId' => 'my-organizer-123',
                ],
                new MockAbstractOrganizerEvent(
                    'madId',
                    'my-organizer-123'
                ),
            ],
        ];
    }
}
