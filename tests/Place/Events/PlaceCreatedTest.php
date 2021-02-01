<?php

namespace test\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class PlaceCreatedTest extends TestCase
{
    /**
     * @var Address
     */
    private $address;

    /**
     * @var DateTimeImmutable
     */
    private $publicationDate;

    /**
     * @var PlaceCreated
     */
    private $placeCreated;

    protected function setUp()
    {
        $this->address = new Address(
            new Street('Blubstraat 69'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            Country::fromNative('BE')
        );

        $this->publicationDate = \DateTimeImmutable::createFromFormat(
            \DateTime::ISO8601,
            '2016-08-01T00:00:00+0200'
        );

        $this->placeCreated = new PlaceCreated(
            'id',
            new Language('es'),
            new Title('title'),
            new EventType('id', 'label'),
            $this->address,
            new Calendar(CalendarType::PERMANENT()),
            new Theme('id', 'label'),
            $this->publicationDate
        );
    }

    /**
     * @test
     */
    public function it_stores_a_place_id()
    {
        $this->assertEquals('id', $this->placeCreated->getPlaceId());
    }

    /**
     * @test
     */
    public function it_stores_a_place_title()
    {
        $this->assertEquals(new Title('title'), $this->placeCreated->getTitle());
    }

    /**
     * @test
     */
    public function it_stores_a_place_address()
    {
        $this->assertEquals($this->address, $this->placeCreated->getAddress());
    }

    /**
     * @test
     */
    public function it_stores_a_place_calendar()
    {
        $this->assertEquals(
            new Calendar(CalendarType::PERMANENT()),
            $this->placeCreated->getCalendar()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_place_publication_date()
    {
        $this->assertEquals(
            $this->publicationDate,
            $this->placeCreated->getPublicationDate()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_place_theme()
    {
        $this->assertEquals(
            new Theme('id', 'label'),
            $this->placeCreated->getTheme()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        PlaceCreated $placeCreated
    ) {
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
    ) {
        $this->assertEquals(
            $expectedPlaceCreated,
            PlaceCreated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider()
    {
        return [
            [
                [
                    'place_id' => 'test 456',
                    'main_language' => 'es',
                    'title' => 'title',
                    'theme' => null,
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
                    new Title('title'),
                    new EventType('bar_id', 'bar'),
                    new Address(
                        new Street('De straat'),
                        new PostalCode('9620'),
                        new Locality('Zottegem'),
                        Country::fromNative('BE')
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
                    'theme' => [
                        'id' => '123',
                        'label' => 'foo',
                        'domain' => 'theme',
                    ],
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
                    new Title('title'),
                    new EventType('bar_id', 'bar'),
                    new Address(
                        new Street('De straat'),
                        new PostalCode('9620'),
                        new Locality('Zottegem'),
                        Country::fromNative('BE')
                    ),
                    new Calendar(
                        CalendarType::PERMANENT()
                    ),
                    new Theme('123', 'foo')
                ),
            ],
            [
                [
                    'place_id' => 'test 456',
                    'main_language' => 'es',
                    'title' => 'title',
                    'theme' => null,
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
                    new Title('title'),
                    new EventType('bar_id', 'bar'),
                    new Address(
                        new Street('De straat'),
                        new PostalCode('9620'),
                        new Locality('Zottegem'),
                        Country::fromNative('BE')
                    ),
                    new Calendar(
                        CalendarType::PERMANENT()
                    ),
                    null,
                    \DateTimeImmutable::createFromFormat(
                        \DateTime::ATOM,
                        '2016-08-01T00:00:00+02:00'
                    )
                ),
            ],
        ];
    }
}
