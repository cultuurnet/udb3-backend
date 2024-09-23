<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Theme;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class EventCreatedTest extends TestCase
{
    private LocationId $location;

    private DateTimeImmutable $publicationDate;

    private EventCreated $eventCreated;

    protected function setUp(): void
    {
        $this->location = new LocationId('335be568-aaf0-4147-80b6-9267daafe23b');

        $this->publicationDate = DateTimeFactory::fromISO8601('2016-08-01T00:00:00+0200');

        $this->eventCreated = new EventCreated(
            'id',
            new Language('es'),
            'title',
            new EventType('id', 'label'),
            $this->location,
            new Calendar(CalendarType::PERMANENT()),
            new Theme('id', 'label'),
            $this->publicationDate
        );
    }

    /**
     * @test
     */
    public function it_converts_to_granular_events(): void
    {
        $eventId = '09994540-289f-4ab4-bf77-b83443d3d0fc';

        $eventWithTheme = new EventCreated(
            $eventId,
            new Language('nl'),
            'Example title',
            new EventType('0.50.4.0.0', 'Concert'),
            $this->location,
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.3.5.0', 'Amusementsmuziek')
        );

        $eventWithoutTheme = new EventCreated(
            $eventId,
            new Language('nl'),
            'Example title',
            new EventType('0.50.4.0.0', 'Concert'),
            $this->location,
            new Calendar(CalendarType::PERMANENT())
        );

        $expectedWithTheme = [
            new TitleUpdated($eventId, 'Example title'),
            new TypeUpdated($eventId, new EventType('0.50.4.0.0', 'Concert')),
            new ThemeUpdated($eventId, new Theme('1.8.3.5.0', 'Amusementsmuziek')),
            new LocationUpdated($eventId, $this->location),
            new CalendarUpdated($eventId, new Calendar(CalendarType::PERMANENT())),
        ];

        $expectedWithoutTheme = [
            new TitleUpdated($eventId, 'Example title'),
            new TypeUpdated($eventId, new EventType('0.50.4.0.0', 'Concert')),
            new LocationUpdated($eventId, $this->location),
            new CalendarUpdated($eventId, new Calendar(CalendarType::PERMANENT())),
        ];

        $this->assertInstanceOf(ConvertsToGranularEvents::class, $eventWithTheme);
        $this->assertInstanceOf(ConvertsToGranularEvents::class, $eventWithoutTheme);
        $this->assertEquals($expectedWithTheme, $eventWithTheme->toGranularEvents());
        $this->assertEquals($expectedWithoutTheme, $eventWithoutTheme->toGranularEvents());
    }

    /**
     * @test
     */
    public function it_implements_main_language_defined(): void
    {
        $event = new EventCreated(
            '09994540-289f-4ab4-bf77-b83443d3d0fc',
            new Language('fr'),
            'Example title',
            new EventType('0.50.4.0.0', 'Concert'),
            $this->location,
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.3.5.0', 'Amusementsmuziek')
        );

        $this->assertInstanceOf(MainLanguageDefined::class, $event);
        $this->assertEquals(new Language('fr'), $event->getMainLanguage());
    }

    /**
     * @test
     */
    public function it_stores_an_event_id(): void
    {
        $this->assertEquals('id', $this->eventCreated->getEventId());
    }

    /**
     * @test
     */
    public function it_stores_an_event_main_language(): void
    {
        $this->assertEquals(new Language('es'), $this->eventCreated->getMainLanguage());
    }

    /**
     * @test
     */
    public function it_stores_an_event_title(): void
    {
        $this->assertEquals('title', $this->eventCreated->getTitle());
    }

    /**
     * @test
     */
    public function it_stores_an_event_location(): void
    {
        $this->assertEquals($this->location, $this->eventCreated->getLocation());
    }

    /**
     * @test
     */
    public function it_stores_an_event_calendar(): void
    {
        $this->assertEquals(
            new Calendar(CalendarType::PERMANENT()),
            $this->eventCreated->getCalendar()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_event_publication_date(): void
    {
        $this->assertEquals(
            $this->publicationDate,
            $this->eventCreated->getPublicationDate()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        EventCreated $eventCreated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $eventCreated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        EventCreated $expectedEventCreated
    ): void {
        $this->assertEquals(
            $expectedEventCreated,
            EventCreated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            [
                [
                    'event_id' => 'test 456',
                    'main_language' => 'es',
                    'title' => 'title',
                    'theme' => null,
                    'location' => 'd379187b-7f71-4403-8fff-645a28be8fd0',
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
                    'publication_date' => null,
                ],
                new EventCreated(
                    'test 456',
                    new Language('es'),
                    'title',
                    new EventType('bar_id', 'bar'),
                    new LocationId('d379187b-7f71-4403-8fff-645a28be8fd0'),
                    new Calendar(
                        CalendarType::PERMANENT()
                    )
                ),
            ],
            [
                [
                    'event_id' => 'test 456',
                    'main_language' => 'es',
                    'title' => 'title',
                    'theme' => [
                        'id' => '123',
                        'label' => 'foo',
                        'domain' => 'theme',
                    ],
                    'location' => 'd379187b-7f71-4403-8fff-645a28be8fd0',
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
                    'publication_date' => null,
                ],
                new EventCreated(
                    'test 456',
                    new Language('es'),
                    'title',
                    new EventType('bar_id', 'bar'),
                    new LocationId('d379187b-7f71-4403-8fff-645a28be8fd0'),
                    new Calendar(
                        CalendarType::PERMANENT()
                    ),
                    new Theme('123', 'foo')
                ),
            ],
            [
                [
                    'event_id' => 'test 456',
                    'main_language' => 'es',
                    'title' => 'title',
                    'theme' => null,
                    'location' => 'd379187b-7f71-4403-8fff-645a28be8fd0',
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
                    'publication_date' => '2016-08-01T00:00:00+02:00',
                ],
                new EventCreated(
                    'test 456',
                    new Language('es'),
                    'title',
                    new EventType('bar_id', 'bar'),
                    new LocationId('d379187b-7f71-4403-8fff-645a28be8fd0'),
                    new Calendar(
                        CalendarType::PERMANENT()
                    ),
                    null,
                    DateTimeFactory::fromAtom('2016-08-01T00:00:00+02:00')
                ),
            ],
        ];
    }
}
