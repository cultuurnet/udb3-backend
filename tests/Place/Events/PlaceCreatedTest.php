<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;
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

        $this->publicationDate = DateTimeFactory::fromISO8601('2016-08-01T00:00:00+0200');

        $this->placeCreated = new PlaceCreated(
            'id',
            new Language('es'),
            'title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            $this->address,
            new PermanentCalendar(new OpeningHours()),
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
            new TypeUpdated(
                'id',
                new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType())
            ),
            new AddressUpdated('id', $this->address),
            new CalendarUpdated('id', new Calendar(CalendarType::permanent())),
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
            new PermanentCalendar(new OpeningHours()),
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
            'without publication date' => [
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
                        'id' => '0.50.4.0.0',
                        'label' => 'Concert',
                        'domain' => 'eventtype',
                    ],
                    'publication_date' => null,
                ],
                new PlaceCreated(
                    'test 456',
                    new Language('es'),
                    'title',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new Address(
                        new Street('De straat'),
                        new PostalCode('9620'),
                        new Locality('Zottegem'),
                        new CountryCode('BE')
                    ),
                    new PermanentCalendar(new OpeningHours())
                ),
            ],
            'with publication date' => [
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
                        'id' => '0.50.4.0.0',
                        'label' => 'Concert',
                        'domain' => 'eventtype',
                    ],
                    'publication_date' => '2016-08-01T00:00:00+02:00',
                ],
                new PlaceCreated(
                    'test 456',
                    new Language('es'),
                    'title',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new Address(
                        new Street('De straat'),
                        new PostalCode('9620'),
                        new Locality('Zottegem'),
                        new CountryCode('BE')
                    ),
                    new PermanentCalendar(new OpeningHours()),
                    DateTimeFactory::fromAtom('2016-08-01T00:00:00+02:00')
                ),
            ],
        ];
    }
}
