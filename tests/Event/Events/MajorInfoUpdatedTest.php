<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Theme;
use PHPUnit\Framework\TestCase;

class MajorInfoUpdatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_converted_to_modern_granular_events(): void
    {
        $eventId = '08efd45f-3319-4321-bde6-6fb30fc80d41';

        $eventWithTheme = new MajorInfoUpdated(
            $eventId,
            'title',
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.3.5.0', 'Amusementsmuziek')
        );

        $eventWithoutTheme = new MajorInfoUpdated(
            $eventId,
            'title',
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            new Calendar(CalendarType::PERMANENT())
        );

        $expectedWithTheme = [
            new TitleUpdated($eventId, 'title'),
            new TypeUpdated($eventId, new EventType('0.50.4.0.0', 'Concert')),
            new ThemeUpdated($eventId, new Theme('1.8.3.5.0', 'Amusementsmuziek')),
            new LocationUpdated($eventId, new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e')),
            new CalendarUpdated($eventId, new Calendar(CalendarType::PERMANENT())),
        ];

        $expectedWithoutTheme = [
            new TitleUpdated($eventId, 'title'),
            new TypeUpdated($eventId, new EventType('0.50.4.0.0', 'Concert')),
            new LocationUpdated($eventId, new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e')),
            new CalendarUpdated($eventId, new Calendar(CalendarType::PERMANENT())),
        ];

        $this->assertEquals($expectedWithTheme, $eventWithTheme->toGranularEvents());
        $this->assertEquals($expectedWithoutTheme, $eventWithoutTheme->toGranularEvents());
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        MajorInfoUpdated $majorInfoUpdated
    ): void {
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
        array $serializedValue,
        MajorInfoUpdated $expectedMajorInfoUpdated
    ): void {
        $this->assertEquals(
            $expectedMajorInfoUpdated,
            MajorInfoUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
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
                        'bookingAvailability' => [
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
                    'title',
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
