<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
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
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            new Calendar(CalendarType::permanent()),
            new Category(new CategoryID('1.8.3.5.0'), new CategoryLabel('Amusementsmuziek'), CategoryDomain::theme())
        );

        $eventWithoutTheme = new MajorInfoUpdated(
            $eventId,
            'title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            new Calendar(CalendarType::permanent())
        );

        $expectedWithTheme = [
            new TitleUpdated($eventId, 'title'),
            new TypeUpdated(
                $eventId,
                new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType())
            ),
            new ThemeUpdated($eventId, new Theme('1.8.3.5.0', 'Amusementsmuziek')),
            new LocationUpdated($eventId, new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e')),
            new CalendarUpdated($eventId, new Calendar(CalendarType::permanent())),
        ];

        $expectedWithoutTheme = [
            new TitleUpdated($eventId, 'title'),
            new TypeUpdated(
                $eventId,
                new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType())
            ),
            new LocationUpdated($eventId, new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e')),
            new CalendarUpdated($eventId, new Calendar(CalendarType::permanent())),
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
                        'id' => '1.8.3.5.0',
                        'label' => 'Amusementsmuziek',
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
                        'id' => '0.50.4.0.0',
                        'label' => 'Concert',
                        'domain' => 'eventtype',
                    ],
                ],
                new MajorInfoUpdated(
                    'test 456',
                    'title',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
                    new Calendar(
                        CalendarType::permanent()
                    ),
                    new Category(new CategoryID('1.8.3.5.0'), new CategoryLabel('Amusementsmuziek'), CategoryDomain::theme())
                ),
            ],
        ];
    }
}
