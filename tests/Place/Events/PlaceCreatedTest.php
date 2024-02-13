<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class PlaceCreatedTest extends TestCase
{
    private Address $address;
    private DateTimeImmutable $publicationDate;
    private PlaceCreated $placeCreated;

    protected function setUp(): void
    {
        $this->address = new Address(
            new Street('Blubstraat 69'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            new CountryCode('BE')
        );

        $this->publicationDate = DateTimeImmutable::createFromFormat(
            \DateTime::ISO8601,
            '2016-08-01T00:00:00+0200'
        );

        $this->placeCreated = new PlaceCreated(
            'id',
            new Language('es'),
            'title',
            new EventType('id', 'label'),
            $this->address,
            new Calendar(CalendarType::PERMANENT()),
            $this->publicationDate
        );
    }

    /**
     * @test
     */
    public function it_converts_to_granular_events(): void
    {
        $expected = [
            new TitleUpdated('id', 'title'),
            new TypeUpdated('id', new EventType('id', 'label')),
            new AddressUpdated('id', $this->address),
            new CalendarUpdated('id', new Calendar(CalendarType::PERMANENT())),
        ];

        $actual = $this->placeCreated->toGranularEvents();

        $this->assertInstanceOf(ConvertsToGranularEvents::class, $this->placeCreated);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_implements_main_language_defined(): void
    {
        $this->assertInstanceOf(MainLanguageDefined::class, $this->placeCreated);
        $this->assertEquals(new Language('es'), $this->placeCreated->getMainLanguage());
    }

    /**
     * @test
     */
    public function it_stores_a_place_id(): void
    {
        $this->assertEquals('id', $this->placeCreated->getPlaceId());
    }

    /**
     * @test
     */
    public function it_stores_a_place_title(): void
    {
        $this->assertEquals('title', $this->placeCreated->getTitle());
    }

    /**
     * @test
     */
    public function it_stores_a_place_address(): void
    {
        $this->assertEquals($this->address, $this->placeCreated->getAddress());
    }

    /**
     * @test
     */
    public function it_stores_a_place_calendar(): void
    {
        $this->assertEquals(
            new Calendar(CalendarType::PERMANENT()),
            $this->placeCreated->getCalendar()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_place_publication_date(): void
    {
        $this->assertEquals(
            $this->publicationDate,
            $this->placeCreated->getPublicationDate()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        PlaceCreated $placeCreated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $placeCreated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        PlaceCreated $expectedPlaceCreated
    ): void {
        $this->assertEquals(
            $expectedPlaceCreated,
            PlaceCreated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            [
                [
                    'place_id' => 'test 456',
                    'main_language' => 'es',
                    'title' => 'title',
                    'address' => [
                        'streetAddress' => 'De straat',
                        'postalCode' => '9620',
                        'addressLocality' => 'Zottegem',
                        'addressCountry' => 'BE',
                    ],
                    'calendar' => [
                        'type' => 'permanent',
                        'status' => [
                            'type' => 'Available',
                        ],
                        'bookingAvailability' => [
                            'type' => 'Available',
                        ],
                    ],
                    'event_type' => [
                        'id' => 'bar_id',
                        'label' => 'bar',
                        'domain' => 'eventtype',
                    ],
                    'publication_date' => null,
                ],
                new PlaceCreated(
                    'test 456',
                    new Language('es'),
                    'title',
                    new EventType('bar_id', 'bar'),
                    new Address(
                        new Street('De straat'),
                        new PostalCode('9620'),
                        new Locality('Zottegem'),
                        new CountryCode('BE')
                    ),
                    new Calendar(
                        CalendarType::PERMANENT()
                    )
                ),
            ],
            [
                [
                    'place_id' => 'test 456',
                    'main_language' => 'es',
                    'title' => 'title',
                    'address' => [
                        'streetAddress' => 'De straat',
                        'postalCode' => '9620',
                        'addressLocality' => 'Zottegem',
                        'addressCountry' => 'BE',
                    ],
                    'calendar' => [
                        'type' => 'permanent',
                        'status' => [
                            'type' => 'Available',
                        ],
                        'bookingAvailability' => [
                            'type' => 'Available',
                        ],
                    ],
                    'event_type' => [
                        'id' => 'bar_id',
                        'label' => 'bar',
                        'domain' => 'eventtype',
                    ],
                    'publication_date' => null,
                ],
                new PlaceCreated(
                    'test 456',
                    new Language('es'),
                    'title',
                    new EventType('bar_id', 'bar'),
                    new Address(
                        new Street('De straat'),
                        new PostalCode('9620'),
                        new Locality('Zottegem'),
                        new CountryCode('BE')
                    ),
                    new Calendar(
                        CalendarType::PERMANENT()
                    )
                ),
            ],
            [
                [
                    'place_id' => 'test 456',
                    'main_language' => 'es',
                    'title' => 'title',
                    'address' => [
                        'streetAddress' => 'De straat',
                        'postalCode' => '9620',
                        'addressLocality' => 'Zottegem',
                        'addressCountry' => 'BE',
                    ],
                    'calendar' => [
                        'type' => 'permanent',
                        'status' => [
                            'type' => 'Available',
                        ],
                        'bookingAvailability' => [
                            'type' => 'Available',
                        ],
                    ],
                    'event_type' => [
                        'id' => 'bar_id',
                        'label' => 'bar',
                        'domain' => 'eventtype',
                    ],
                    'publication_date' => '2016-08-01T00:00:00+02:00',
                ],
                new PlaceCreated(
                    'test 456',
                    new Language('es'),
                    'title',
                    new EventType('bar_id', 'bar'),
                    new Address(
                        new Street('De straat'),
                        new PostalCode('9620'),
                        new Locality('Zottegem'),
                        new CountryCode('BE')
                    ),
                    new Calendar(
                        CalendarType::PERMANENT()
                    ),
                    DateTimeImmutable::createFromFormat(
                        DateTimeInterface::ATOM,
                        '2016-08-01T00:00:00+02:00'
                    )
                ),
            ],
        ];
    }
}
