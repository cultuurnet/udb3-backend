<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractPropertyTranslatedEventTest extends TestCase
{
    /**
     * @var AbstractPropertyTranslatedEvent|MockObject
     */
    protected $propertyTranslatedEvent;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var String
     */
    protected $title;

    public function setUp()
    {
        $this->itemId = 'Foo';
        $this->language = new Language('en');
        $this->propertyTranslatedEvent = new MockAbstractPropertyTranslatedEvent($this->itemId, $this->language);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties()
    {
        $expectedItemId = 'Foo';
        $expectedLanguage = new Language('en');
        $expectedTranslateEvent = new MockAbstractPropertyTranslatedEvent(
            $expectedItemId,
            $expectedLanguage
        );

        $this->assertEquals($expectedTranslateEvent, $this->propertyTranslatedEvent);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties()
    {
        $expectedItemId = 'Foo';
        $expectedLanguage = new Language('en');

        $itemId = $this->propertyTranslatedEvent->getItemId();
        $language = $this->propertyTranslatedEvent->getLanguage();

        $this->assertEquals($expectedItemId, $itemId);
        $this->assertEquals($expectedLanguage, $language);
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        MockAbstractPropertyTranslatedEvent $propertyTranslatedEvent
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $propertyTranslatedEvent->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_deserialize_an_array(
        array $serializedValue,
        MockAbstractPropertyTranslatedEvent $expectedPropertyTranslatedEvent
    ) {
        $this->assertEquals(
            $expectedPropertyTranslatedEvent,
            MockAbstractPropertyTranslatedEvent::deserialize($serializedValue)
        );
    }

    /**
     * @return array
     */
    public function serializationDataProvider()
    {
        return [
            'abstractPropertyTranslatedEvent' => [
                [
                    'item_id' => 'madId',
                    'language' => 'en',
                ],
                new MockAbstractPropertyTranslatedEvent(
                    'madId',
                    new Language('en')
                ),
            ],
        ];
    }
}
