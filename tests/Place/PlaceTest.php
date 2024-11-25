<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Address\Locality as LegacyLocality;
use CultuurNet\UDB3\Address\PostalCode as LegacyPostalCode;
use CultuurNet\UDB3\Address\Street as LegacyStreet;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use CultuurNet\UDB3\Place\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Place\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\CalendarUpdated;
use CultuurNet\UDB3\Place\Events\ContactPointUpdated;
use CultuurNet\UDB3\Place\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\SampleFiles;
use Money\Currency;
use Money\Money;

class PlaceTest extends AggregateRootScenarioTestCase
{
    protected function getAggregateRootClass(): string
    {
        return Place::class;
    }

    private function getCdbXML(string $filename): string
    {
        return SampleFiles::read(__DIR__ . $filename);
    }

    /**
     * @test
     */
    public function it_handles_update_facilities_after_udb2_update(): void
    {
        $placeCreated = $this->createPlaceCreatedEvent();
        $placeId = $placeCreated->getPlaceId();

        $facilities = [
            new Facility('3.27.0.0.0', 'Rolstoeltoegankelijk'),
            new Facility('3.30.0.0.0', 'Rolstoelpodium'),
        ];

        $cdbXml = $this->getCdbXML('/ReadModel/JSONLD/place_with_long_description.cdbxml.xml');
        $cdbXmlNamespace = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

        $this->scenario
            ->given(
                [
                    $placeCreated,
                    new FacilitiesUpdated($placeId, $facilities),
                    new PlaceUpdatedFromUDB2($placeId, $cdbXml, $cdbXmlNamespace),
                ]
            )
            ->when(
                function (Place $place) use ($facilities): void {
                    $place->updateFacilities($facilities);
                }
            )
            ->then(
                [
                    new FacilitiesUpdated($placeId, $facilities),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_empty_update_facilities_after_udb2_update(): void
    {
        $placeCreated = $this->createPlaceCreatedEvent();
        $placeId = $placeCreated->getPlaceId();

        $cdbXml = $this->getCdbXML('/ReadModel/JSONLD/place_with_long_description.cdbxml.xml');
        $cdbXmlNamespace = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

        $this->scenario
            ->given(
                [
                    $placeCreated,
                    new PlaceUpdatedFromUDB2($placeId, $cdbXml, $cdbXmlNamespace),
                ]
            )
            ->when(
                function (Place $place): void {
                    $place->updateFacilities([]);
                }
            )
            ->then(
                [
                    new FacilitiesUpdated($placeId, []),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_contact_point_after_udb2_import(): void
    {
        $placeCreated = $this->createPlaceCreatedEvent();
        $placeId = $placeCreated->getPlaceId();

        $contactPoint = new ContactPoint(
            new TelephoneNumbers(new TelephoneNumber('016/101010')),
            new EmailAddresses(
                new EmailAddress('test@2dotstwice.be'),
                new EmailAddress('admin@2dotstwice.be')
            ),
            new Urls(new Url('http://www.2dotstwice.be'))
        );

        $cdbXml = $this->getCdbXML('/ReadModel/JSONLD/place_with_long_description.cdbxml.xml');
        $cdbXmlNamespace = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

        $this->scenario
            ->given(
                [
                    $placeCreated,
                    new ContactPointUpdated($placeId, $contactPoint),
                    new PlaceUpdatedFromUDB2($placeId, $cdbXml, $cdbXmlNamespace),
                ]
            )
            ->when(
                function (Place $place) use ($contactPoint): void {
                    $place->updateContactPoint($contactPoint);
                }
            )
            ->then(
                [
                    new ContactPointUpdated($placeId, $contactPoint),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_calendar_after_udb2_import(): void
    {
        $placeCreated = $this->createPlaceCreatedEvent();
        $placeId = $placeCreated->getPlaceId();

        $calendar = new Calendar(
            CalendarType::periodic(),
            DateTimeFactory::fromAtom('2020-01-26T11:11:11+01:00'),
            DateTimeFactory::fromAtom('2020-01-27T12:12:12+01:00')
        );

        $cdbXml = $this->getCdbXML('/ReadModel/JSONLD/place_with_long_description.cdbxml.xml');
        $cdbXmlNamespace = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

        $this->scenario
            ->given(
                [
                    $placeCreated,
                    new CalendarUpdated($placeId, $calendar),
                    new PlaceUpdatedFromUDB2($placeId, $cdbXml, $cdbXmlNamespace),
                ]
            )
            ->when(
                function (Place $place) use ($calendar): void {
                    $place->updateCalendar($calendar);
                }
            )
            ->then(
                [
                    new CalendarUpdated($placeId, $calendar),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_price_info_after_udb2_import(): void
    {
        $placeCreated = $this->createPlaceCreatedEvent();
        $placeId = $placeCreated->getPlaceId();

        $priceInfo = new PriceInfo(
            Tariff::createBasePrice(
                new Money(1000, new Currency('EUR'))
            )
        );

        $cdbXml = $this->getCdbXML('/ReadModel/JSONLD/place_with_long_description.cdbxml.xml');
        $cdbXmlNamespace = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

        $this->scenario
            ->given(
                [
                    $placeCreated,
                    new PriceInfoUpdated($placeId, $priceInfo),
                    new PlaceUpdatedFromUDB2($placeId, $cdbXml, $cdbXmlNamespace),
                ]
            )
            ->when(
                function (Place $place) use ($priceInfo): void {
                    $place->updatePriceInfo($priceInfo);
                }
            )
            ->then(
                [
                    new PriceInfoUpdated($placeId, $priceInfo),
                ]
            );
    }

    /**
     * @test
     * @dataProvider updateAddressDataProvider
     *
     */
    public function it_should_update_the_address_in_the_main_language(
        Address $originalAddress,
        Address $updatedAddress
    ): void {
        $language = new Language('nl');

        $this->scenario
            ->withAggregateId('c5c1b435-0f3c-4b75-9f28-94d93be7078b')
            ->given(
                [
                    new PlaceCreated(
                        'c5c1b435-0f3c-4b75-9f28-94d93be7078b',
                        new Language('nl'),
                        'Test place',
                        new EventType('0.1.1', 'Jeugdhuis'),
                        LegacyAddress::fromUdb3ModelAddress($originalAddress),
                        new Calendar(CalendarType::permanent())
                    ),
                ]
            )
            ->when(
                function (Place $place) use ($updatedAddress, $language): void {
                    $place->updateAddress($updatedAddress, $language);
                }
            )
            ->then(
                [
                    new AddressUpdated(
                        'c5c1b435-0f3c-4b75-9f28-94d93be7078b',
                        LegacyAddress::fromUdb3ModelAddress($updatedAddress)
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_not_update_the_address_when_address_is_not_changed(): void
    {
        $placeCreated = $this->createPlaceCreatedEvent();
        $placeId = $placeCreated->getPlaceId();
        $address = $placeCreated->getAddress()->toUdb3ModelAddress();

        $translatedAddress = new Address(
            new Street('One May Street'),
            new PostalCode('3010'),
            new Locality('Kessel-High'),
            new CountryCode('BE')
        );

        $this->scenario
            ->withAggregateId($placeId)
            ->given(
                [
                    $placeCreated,
                ]
            )
            ->when(
                function (Place $place) use ($address, $translatedAddress): void {
                    $place->updateAddress($address, new Language('nl'));
                    $place->updateAddress($translatedAddress, new Language('en'));
                    $place->updateAddress($translatedAddress, new Language('en'));
                }
            )
            ->then([
                new AddressTranslated(
                    $placeId,
                    LegacyAddress::fromUdb3ModelAddress($translatedAddress),
                    new Language('en')
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_update_typical_age_range_after_udb2_update(): void
    {
        $placeCreated = $this->createPlaceCreatedEvent();
        $placeId = $placeCreated->getPlaceId();

        $typicalAgeRange = new AgeRange(new Age(8), new Age(11));
        $otherTypicalAgeRange = new AgeRange(new Age(9), new Age(11));

        $cdbXml = $this->getCdbXML('/ReadModel/JSONLD/place_with_long_description.cdbxml.xml');
        $cdbXmlNamespace = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

        $this->scenario
            ->given(
                [
                    $placeCreated,
                    new TypicalAgeRangeUpdated($placeId, $typicalAgeRange),
                    new PlaceUpdatedFromUDB2($placeId, $cdbXml, $cdbXmlNamespace),
                ]
            )
            ->when(
                function (Place $place) use ($typicalAgeRange, $otherTypicalAgeRange): void {
                    $place->updateTypicalAgeRange($typicalAgeRange);
                    $place->updateTypicalAgeRange($otherTypicalAgeRange);
                }
            )
            ->then(
                [
                    new TypicalAgeRangeUpdated($placeId, $otherTypicalAgeRange),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_delete_typical_age_range_after_udb2_update(): void
    {
        $placeCreated = $this->createPlaceCreatedEvent();
        $placeId = $placeCreated->getPlaceId();

        $typicalAgeRange = new AgeRange(new Age(8), new Age(11));

        $cdbXml = $this->getCdbXML('/ReadModel/JSONLD/place_with_long_description.cdbxml.xml');
        $cdbXmlNamespace = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

        $this->scenario
            ->given(
                [
                    $placeCreated,
                    new TypicalAgeRangeUpdated($placeId, $typicalAgeRange),
                    new PlaceUpdatedFromUDB2($placeId, $cdbXml, $cdbXmlNamespace),
                ]
            )
            ->when(
                function (Place $place): void {
                    $place->deleteTypicalAgeRange();
                }
            )
            ->then(
                [
                    new TypicalAgeRangeDeleted($placeId),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_booking_info_after_udb2_import(): void
    {
        $placeCreated = $this->createPlaceCreatedEvent();
        $placeId = $placeCreated->getPlaceId();

        $bookingInfo = new BookingInfo(
            new WebsiteLink(
                new Url('http://www.publiq.be'),
                new TranslatedWebsiteLabel(
                    new Language('nl'),
                    new WebsiteLabel('Publiq')
                )
            ),
            new TelephoneNumber('02 123 45 67'),
            new EmailAddress('info@publiq.be')
        );

        $cdbXml = $this->getCdbXML('/ReadModel/JSONLD/place_with_long_description.cdbxml.xml');
        $cdbXmlNamespace = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

        $this->scenario
            ->given(
                [
                    $placeCreated,
                    new BookingInfoUpdated($placeId, $bookingInfo),
                    new PlaceUpdatedFromUDB2($placeId, $cdbXml, $cdbXmlNamespace),
                ]
            )
            ->when(
                function (Place $place) use ($bookingInfo): void {
                    $place->updateBookingInfo($bookingInfo);
                }
            )
            ->then(
                [
                    new BookingInfoUpdated($placeId, $bookingInfo),
                ]
            );
    }

    /**
     * @test
     * @dataProvider updateAddressDataProvider
     *
     */
    public function it_should_translate_the_address_in_any_other_language_than_the_main_language(
        Address $originalAddress,
        Address $updatedAddress
    ): void {
        $language = new Language('fr');

        $this->scenario
            ->withAggregateId('c5c1b435-0f3c-4b75-9f28-94d93be7078b')
            ->given(
                [
                    new PlaceCreated(
                        'c5c1b435-0f3c-4b75-9f28-94d93be7078b',
                        new Language('nl'),
                        'Test place',
                        new EventType('0.1.1', 'Jeugdhuis'),
                        LegacyAddress::fromUdb3ModelAddress($originalAddress),
                        new Calendar(CalendarType::permanent())
                    ),
                ]
            )
            ->when(
                function (Place $place) use ($updatedAddress, $language): void {
                    $place->updateAddress($updatedAddress, $language);
                }
            )
            ->then(
                [
                    new AddressTranslated(
                        'c5c1b435-0f3c-4b75-9f28-94d93be7078b',
                        LegacyAddress::fromUdb3ModelAddress($updatedAddress),
                        $language
                    ),
                ]
            );
    }

    public function updateAddressDataProvider(): array
    {
        return [
            [
                'original' => new Address(
                    new Street('Eenmeilaan'),
                    new PostalCode('3010'),
                    new Locality('Kessel-Lo'),
                    new CountryCode('BE')
                ),
                'updated' => new Address(
                    new Street('Eenmeilaan 35'),
                    new PostalCode('3010'),
                    new Locality('Kessel-Lo'),
                    new CountryCode('BE')
                ),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_imports_from_udb2_actors_and_takes_keywords_into_account(): void
    {
        $cdbXml = $this->getCdbXML(
            '/ReadModel/JSONLD/place_with_long_description.cdbxml.xml'
        );

        $this->scenario
            ->when(
                function () use ($cdbXml) {
                    return Place::importFromUDB2Actor(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        $cdbXml,
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    );
                }
            )
            ->then(
                [
                    new PlaceImportedFromUDB2(
                        '318F2ACB-F612-6F75-0037C9C29F44087A',
                        $cdbXml,
                        'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                    ),
                ]
            )
            ->when(
                function (Place $place): void {
                    $place->addLabel(new Label(new LabelName('Toevlalocatie')));
                }
            )
            ->then([]);
    }

    /**
     * @test
     * @dataProvider newPlaceProvider
     */
    public function it_has_an_id(string $expectedId, EventSourcedAggregateRoot $place): void
    {
        $this->assertEquals($expectedId, $place->getAggregateRootId());
    }

    public function newPlaceProvider(): array
    {
        return [
            'actor' => [
                '318F2ACB-F612-6F75-0037C9C29F44087A',
                Place::importFromUDB2Actor(
                    '318F2ACB-F612-6F75-0037C9C29F44087A',
                    $this->getCdbXML(
                        '/ReadModel/JSONLD/place_with_long_description.cdbxml.xml'
                    ),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                ),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_does_not_update_the_same_title_after_place_created(): void
    {
        $this->scenario
            ->withAggregateId('c5c1b435-0f3c-4b75-9f28-94d93be7078b')
            ->given([
                $this->createPlaceCreatedEvent(),
            ])
            ->when(
                function (Place $place): void {
                    $place->updateTitle(
                        new Language('nl'),
                        new Title('Test place')
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_the_same_calendar_after_place_created(): void
    {
        $this->scenario
            ->withAggregateId('c5c1b435-0f3c-4b75-9f28-94d93be7078b')
            ->given([
                $this->createPlaceCreatedEvent(),
            ])
            ->when(
                function (Place $place): void {
                    $place->updateCalendar(
                        new Calendar(CalendarType::permanent())
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_the_same_contact_point_after_place_created(): void
    {
        $this->scenario
            ->withAggregateId('c5c1b435-0f3c-4b75-9f28-94d93be7078b')
            ->given([
                $this->createPlaceCreatedEvent(),
            ])
            ->when(
                function (Place $place): void {
                    $place->updateContactPoint(
                        new ContactPoint()
                    );
                }
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_the_same_booking_info_after_place_created(): void
    {
        $this->scenario
            ->withAggregateId('c5c1b435-0f3c-4b75-9f28-94d93be7078b')
            ->given([
                $this->createPlaceCreatedEvent(),
            ])
            ->when(
                function (Place $place): void {
                    $place->updateBookingInfo(
                        new BookingInfo()
                    );
                }
            )
            ->then([]);
    }

    private function createPlaceCreatedEvent(): PlaceCreated
    {
        $placeId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $address = new LegacyAddress(
            new LegacyStreet('Eenmeilaan'),
            new LegacyPostalCode('3010'),
            new LegacyLocality('Kessel-Lo'),
            new CountryCode('BE')
        );

        return  new PlaceCreated(
            $placeId,
            new Language('nl'),
            'Test place',
            new EventType('0.1.1', 'Jeugdhuis'),
            $address,
            new Calendar(CalendarType::permanent())
        );
    }
}
