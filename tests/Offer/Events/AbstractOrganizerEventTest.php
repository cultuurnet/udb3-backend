<?php

namespace CultuurNet\UDB3\Offer\Events;

use PHPUnit\Framework\TestCase;

class AbstractOrganizerEventTest extends TestCase
{
    /**
     * @var AbstractOrganizerEvent
     */
    protected $abstractOrganizerEvent;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var string
     */
    protected $organizerId;

    public function setUp()
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
    public function it_can_be_instantiated_With_properties()
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
    public function it_can_return_its_properties()
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
    public function it_can_deserialize_an_array(
        array $serializedValue,
        MockAbstractOrganizerEvent $expectedOrganizerEvent
    ) {
        $this->assertEquals(
            $expectedOrganizerEvent,
            MockAbstractOrganizerEvent::deserialize($serializedValue)
        );
    }

    /**
     * @return array
     */
    public function serializationDataProvider()
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
