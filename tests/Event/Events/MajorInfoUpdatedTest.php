<?php

namespace test\Event\Events;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;

class MajorInfoUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        MajorInfoUpdated $majorInfoUpdated
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $majorInfoUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        MajorInfoUpdated $expectedMajorInfoUpdated
    ) {
        $this->assertEquals(
            $expectedMajorInfoUpdated,
            MajorInfoUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider()
    {
        return [
            'event' => [
                [
                    'item_id' => 'test 456',
                    'title' => 'title',
                    'theme' => [
                        'id' => 'themeid',
                        'label' => 'theme_label',
                        'domain' => 'theme',
                    ],
                    'location' => '395fe7eb-9bac-4647-acae-316b6446a85e',
                    'calendar' => [
                        'status' => [
                            'type' => 'Available',
                        ],
                        'type' => 'permanent',
                    ],
                    'event_type' => [
                        'id' => 'bar_id',
                        'label' => 'bar',
                        'domain' => 'eventtype',
                    ],
                ],
                new MajorInfoUpdated(
                    'test 456',
                    new Title('title'),
                    new EventType('bar_id', 'bar'),
                    new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
                    new Calendar(
                        CalendarType::PERMANENT()
                    ),
                    new Theme('themeid', 'theme_label')
                ),
            ],
        ];
    }
}
