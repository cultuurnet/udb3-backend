<?php

namespace test\Event\Events;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class EventCreatedTest extends TestCase
{
    /**
     * @var LocationId
     */
    private $location;

    /**
     * @var DateTimeImmutable
     */
    private $publicationDate;

    /**
     * @var EventCreated
     */
    private $eventCreated;

    protected function setUp()
    {
        $this->location = new LocationId('335be568-aaf0-4147-80b6-9267daafe23b');

        $this->publicationDate = DateTimeImmutable::createFromFormat(
            \DateTime::ISO8601,
            '2016-08-01T00:00:00+0200'
        );

        $this->eventCreated = new EventCreated(
            'id',
            new Language('es'),
            new Title('title'),
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
    public function it_stores_an_event_id()
    {
        $this->assertEquals('id', $this->eventCreated->getEventId());
    }

    /**
     * @test
     */
    public function it_stores_an_event_main_language()
    {
        $this->assertEquals(new Language('es'), $this->eventCreated->getMainLanguage());
    }

    /**
     * @test
     */
    public function it_stores_an_event_title()
    {
        $this->assertEquals(new Title('title'), $this->eventCreated->getTitle());
    }

    /**
     * @test
     */
    public function it_stores_an_event_location()
    {
        $this->assertEquals($this->location, $this->eventCreated->getLocation());
    }

    /**
     * @test
     */
    public function it_stores_an_event_calendar()
    {
        $this->assertEquals(
            new Calendar(CalendarType::PERMANENT()),
            $this->eventCreated->getCalendar()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_event_publication_date()
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
    ) {
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
    ) {
        $this->assertEquals(
            $expectedEventCreated,
            EventCreated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider()
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
                    new Title('title'),
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
                    new Title('title'),
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
                    new Title('title'),
                    new EventType('bar_id', 'bar'),
                    new LocationId('d379187b-7f71-4403-8fff-645a28be8fd0'),
                    new Calendar(
                        CalendarType::PERMANENT()
                    ),
                    null,
                    DateTimeImmutable::createFromFormat(
                        \DateTime::ATOM,
                        '2016-08-01T00:00:00+02:00'
                    )
                ),
            ],
        ];
    }
}
