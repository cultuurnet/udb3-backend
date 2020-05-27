<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\CalendarFactory;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractor;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\SluggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CdbXMLImporterTest extends TestCase
{

    /**
     * @var CdbXMLImporter
     */
    protected $importer;

    /**
     * @var OrganizerServiceInterface|MockObject
     */
    protected $organizerManager;

    /**
     * @var PlaceServiceInterface|MockObject
     */
    protected $placeManager;

    /**
     * @var SluggerInterface|MockObject
     */
    protected $slugger;

    public function setUp()
    {
        $this->importer = new CdbXMLImporter(
            new CdbXMLItemBaseImporter(
                new PriceDescriptionParser(
                    new NumberFormatRepository(),
                    new CurrencyRepository()
                ),
                [
                    'nl' => 'Basistarief',
                    'fr' => 'Tarif de base',
                    'en' => 'Base tarif',
                    'de' => 'Basisrate',
                ]
            ),
            new EventCdbIdExtractor(),
            new CalendarFactory(),
            new CdbXmlContactInfoImporter()
        );
        $this->organizerManager = $this->createMock(OrganizerServiceInterface::class);
        $this->placeManager = $this->createMock(PlaceServiceInterface::class);
        $this->slugger = $this->createMock(SluggerInterface::class);
        date_default_timezone_set('Europe/Brussels');
    }

    /**
     * @param $fileName
     * @param string $version
     * @return \CultureFeed_Cdb_Item_Event
     */
    private function createEventFromCdbXml($fileName, $version = '3.2')
    {
        $cdbXml = file_get_contents(
            __DIR__ . '/' . $fileName
        );

        return EventItemFactory::createEventFromCdbXml(
            "http://www.cultuurdatabank.com/XMLSchema/CdbXSD/{$version}/FINAL",
            $cdbXml
        );
    }

    /**
     * @param string $fileName
     * @param string $version
     * @return \stdClass
     */
    private function createJsonEventFromCdbXml($fileName, $version = '3.2')
    {
        $event = $this->createEventFromCdbXml($fileName, $version);

        $jsonEvent = $this->importer->documentWithCdbXML(
            new \stdClass(),
            $event,
            $this->placeManager,
            $this->organizerManager,
            $this->slugger
        );

        return $jsonEvent;
    }

    /**
     * @param integer|null $ageFrom
     * @param integer|null $ageTo
     * @return \stdClass
     */
    private function createJsonEventFromCdbXmlWithAgeRange($ageFrom = null, $ageTo = null)
    {
        $event = $this->createEventFromCdbXml(
            '../../samples/event_with_age_from.cdbxml.xml'
        );

        $event->setAgeFrom($ageFrom);
        $event->setAgeTo($ageTo);

        $jsonEvent = $this->importer->documentWithCdbXML(
            new \stdClass(),
            $event,
            $this->placeManager,
            $this->organizerManager,
            $this->slugger
        );

        return $jsonEvent;
    }

    /**
     * @return \stdClass
     */
    private function createJsonEventFromCdbXmlWithoutAgeFrom()
    {
        return $this->createJsonEventFromCdbXml(
            '../../samples/event_without_age_from.cdbxml.xml'
        );
    }

    /**
     * @param string $fileName
     * @return \stdClass
     */
    private function createJsonEventFromCalendarSample($fileName)
    {
        $cdbXml = file_get_contents(
            __DIR__ . '/../../samples/calendar/' . $fileName
        );

        $event = EventItemFactory::createEventFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $cdbXml
        );

        $jsonEvent = $this->importer->documentWithCdbXML(
            new \stdClass(),
            $event,
            $this->placeManager,
            $this->organizerManager,
            $this->slugger
        );

        return $jsonEvent;
    }

    /**
     * @test
     */
    public function it_imports_the_publication_info()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_without_email_and_phone_number.cdbxml.xml');

        $this->assertEquals('kgielens@kanker.be', $jsonEvent->creator);
        $this->assertEquals('2014-08-12T14:37:58+02:00', $jsonEvent->created);
        $this->assertEquals('2014-10-21T16:47:23+02:00', $jsonEvent->modified);
        $this->assertEquals('Invoerders Algemeen ', $jsonEvent->publisher);
    }

    /**
     * @test
     */
    public function it_adds_an_email_property_when_cdbxml_has_no_organizer_but_has_contact_with_email()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_email_and_phone_number.cdbxml.xml');

        $this->assertEquals('kgielens@stichtingtegenkanker.be', $jsonEvent->organizer['email'][0]);
    }

    /**
     * @test
     */
    public function it_adds_a_phone_property_when_cdbxml_has_no_organizer_but_has_contact_with_phone_number()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_email_and_phone_number.cdbxml.xml');

        $this->assertEquals('0475 82 21 36', $jsonEvent->organizer['phone'][0]);
    }

    /**
     * @test
     */
    public function it_does_not_add_an_email_property_when_cdbxml_has_no_organizer_or_contact_with_email()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_without_email_and_phone_number.cdbxml.xml');

        $this->assertFalse(array_key_exists('email', $jsonEvent->organizer));
    }

    /**
     * @test
     */
    public function it_does_not_add_a_phone_property_when_cdbxml_has_no_organizer_or_contact_with_phone_number()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_without_email_and_phone_number.cdbxml.xml');

        $this->assertFalse(array_key_exists('phone', $jsonEvent->organizer));
    }

    /**
     * @test
     */
    public function it_adds_the_cdbxml_externalid_attribute_to_the_same_as_property_when_not_CDB()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_non_cdb_externalid.cdbxml.xml');

        $this->assertObjectHasAttribute('sameAs', $jsonEvent);
        $this->assertContains('CC_De_Grote_Post:degrotepost_Evenement_453', $jsonEvent->sameAs);
    }

    /**
     * @test
     */
    public function it_does_not_add_the_cdbxml_externalid_attribute_to_the_same_as_property_when_CDB()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_cdb_externalid.cdbxml.xml');

        $this->assertObjectHasAttribute('sameAs', $jsonEvent);
        $this->assertNotContains('CDB:95b30501-6a70-4cb3-a5c9-4a2eb7003214', $jsonEvent->sameAs);
    }

    /**
     * @test
     */
    public function it_adds_a_reference_to_uit_in_vlaanderen_to_the_same_as_property()
    {
        $slug = 'i_am_a_slug';
        $eventId = '7914ed2d-9f28-4946-b9bd-ae8f7a4aea11';

        $this->slugger
            ->expects($this->once())
            ->method('slug')
            ->willReturn($slug);

        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_cdb_externalid.cdbxml.xml');

        $originalReference = 'http://www.uitinvlaanderen.be/agenda/e/' . $slug . '/' . $eventId;

        $this->assertObjectHasAttribute('sameAs', $jsonEvent);
        $this->assertContains($originalReference, $jsonEvent->sameAs);
    }

    /**
     * @test
     */
    public function it_adds_availability_info()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_non_cdb_externalid.cdbxml.xml');

        $this->assertObjectHasAttribute('availableFrom', $jsonEvent);
        $this->assertEquals('2014-07-25T05:18:22+02:00', $jsonEvent->availableFrom);

        $this->assertObjectHasAttribute('availableTo', $jsonEvent);
        $this->assertEquals('2015-03-29T00:00:00+01:00', $jsonEvent->availableTo);

        $anotherJsonEvent = $this->createJsonEventFromCdbXml('event_with_cdb_externalid.cdbxml.xml');

        $this->assertObjectHasAttribute('availableFrom', $anotherJsonEvent);
        $this->assertEquals('2014-10-22T00:00:00+02:00', $anotherJsonEvent->availableFrom);

        $this->assertObjectHasAttribute('availableTo', $anotherJsonEvent);
        $this->assertEquals('2015-03-19T00:00:00+01:00', $anotherJsonEvent->availableTo);
    }

    /**
     * @test
     */
    public function it_adds_a_phone_property_to_contact_point()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_email_and_phone_number.cdbxml.xml');

        $this->assertObjectHasAttribute('contactPoint', $jsonEvent);
        $this->assertEquals(array('0475 82 21 36'), $jsonEvent->contactPoint['phone']);
    }

    /**
     * @test
     */
    public function it_does_not_add_an_empty_phone_property_to_contact_point()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_just_an_email.cdbxml.xml');

        $this->assertObjectHasAttribute('contactPoint', $jsonEvent);
        $this->assertArrayNotHasKey('phone', $jsonEvent->contactPoint);
    }

    /**
     * @test
     */
    public function it_adds_an_email_property_to_contact_point()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_just_an_email.cdbxml.xml');

        $this->assertObjectHasAttribute('contactPoint', $jsonEvent);
        $this->assertEquals(
            array('kgielens@stichtingtegenkanker.be'),
            $jsonEvent->contactPoint['email']
        );
    }

    /**
     * @test
     */
    public function it_does_not_add_an_empty_email_property_to_contact_point()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_just_a_phone_number.cdbxml.xml');

        $this->assertObjectHasAttribute('contactPoint', $jsonEvent);
        $this->assertArrayNotHasKey('mail', $jsonEvent->contactPoint);
    }

    /**
     * @test
     */
    public function it_adds_contact_info_urls_to_seeAlso_property()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_email_and_phone_number.cdbxml.xml');

        $this->assertObjectHasAttribute('seeAlso', $jsonEvent);
        $this->assertContains('http://www.rekanto.be', $jsonEvent->seeAlso);
    }

    /**
     * @test
     */
    public function it_adds_a_reservation_url_to_bookingInfo_property()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_reservation_url.cdbxml.xml');

        $this->assertObjectHasAttribute('bookingInfo', $jsonEvent);
        $this->assertEquals('http://brugge.iticketsro.com/ccmechelen/', $jsonEvent->bookingInfo['url']);

        // Reservation url should not have been added to seeAlso.
        $this->assertObjectHasAttribute('seeAlso', $jsonEvent);
        $this->assertNotContains('http://brugge.iticketsro.com/ccmechelen/', $jsonEvent->seeAlso);
    }

    /**
     * @test
     */
    public function it_does_not_add_a_non_reservation_url_to_bookingInfo_property()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_email_and_phone_number.cdbxml.xml');

        $this->assertObjectHasAttribute('bookingInfo', $jsonEvent);
        $this->assertArrayNotHasKey('url', $jsonEvent->bookingInfo);
    }

    /**
     * @test
     */
    public function it_does_not_add_reservation_info_to_contact_point()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_all_kinds_of_contact_info.cdbxml.xml');
        $expectedContactPoint = array(
            'email' => array('john@doe.be'),
            'phone' => array('1234 82 21 36'),
            'url' => array('http://www.rekanto.be'),
        );

        $this->assertObjectHasAttribute('contactPoint', $jsonEvent);
        $this->assertEquals($expectedContactPoint, $jsonEvent->contactPoint);
    }

    /**
     * @test
     */
    public function it_has_a_correct_datetime_when_cdbxml_contains_negative_unix_timestamp()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_negative_timestamp.cdbxml.xml');

        $this->assertObjectHasAttribute('bookingInfo', $jsonEvent);
        $this->assertEquals('1968-12-31T23:00:00+00:00', $jsonEvent->bookingInfo['availabilityStarts']);
        $this->assertEquals('1968-12-31T23:00:00+00:00', $jsonEvent->bookingInfo['availabilityEnds']);
    }

    /**
     * @test
     */
    public function it_does_not_include_duplicate_labels()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_duplicate_labels.cdbxml.xml');

        $this->assertEquals(['enkel'], $jsonEvent->labels);
    }

    /**
     * @test
     */
    public function it_should_import_invisible_keywords_as_hidden_labels()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml(
            'event_with_invisible_keyword.cdbxml.xml'
        );

        $this->assertEquals(['toon mij', 'toon mij ook'], $jsonEvent->labels);
        $this->assertEquals(['verberg mij'], $jsonEvent->hiddenLabels);
    }

    /**
     * @test
     */
    public function it_does_import_an_event_with_semicolons_in_keywords_tag()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml(
            'event_with_semicolon_in_keywords_tag.cdbxml.xml'
        );

        $this->assertEquals(['leren Frans', 'cursus Frans'], $jsonEvent->labels);
    }

    /**
     * @test
     */
    public function it_does_import_an_event_with_semicolons_in_keyword_tag()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml(
            'event_with_semicolon_in_keyword_tag.cdbxml.xml'
        );

        $this->assertEquals(
            ['Franse kennis','leren Frans', 'cursus Frans'],
            $jsonEvent->labels
        );
    }

    /**
     * @test
     */
    public function it_does_not_import_a_new_event_with_semicolons_in_keyword_tag()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml(
            'event_with_semicolon_in_keyword_tag_but_too_new.cdbxml.xml'
        );

        $this->assertEquals(['Franse kennis'], $jsonEvent->labels);
    }

    /**
     * @test
     */
    public function it_should_copy_over_a_known_workflow_status()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_all_kinds_of_contact_info.cdbxml.xml');

        $this->assertEquals('APPROVED', $jsonEvent->workflowStatus);
    }

    /**
     * @test
     */
    public function it_uses_a_properly_formatted_price_description()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_properly_formatted_price_description.cdbxml.xml');

        $this->assertEquals(
            [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                        'fr' => 'Tarif de base',
                        'en' => 'Base tarif',
                        'de' => 'Basisrate',
                    ],
                    'price' => 12.5,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'name' => [
                        'nl' => 'Met kinderen',
                        'fr' => 'Avec des enfants',
                    ],
                    'category' => 'tariff',
                    'price' => 20,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'name' => [
                        'nl' => 'Senioren',
                        'fr' => 'Aînés',
                    ],
                    'category' => 'tariff',
                    'price' => 30,
                    'priceCurrency' => 'EUR',
                ],
            ],
            $jsonEvent->priceInfo
        );
    }

    /**
     * @test
     */
    public function it_ignores_base_price_in_price_description()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_properly_formatted_price_description_but_different_pricevalue.cdbxml.xml');

        $this->assertEquals(
            [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                        'fr' => 'Tarif de base',
                        'en' => 'Base tarif',
                        'de' => 'Basisrate',
                    ],
                    'price' => 12,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => ['nl' => 'Met kinderen'],
                    'price' => 20,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => ['nl' => 'Senioren'],
                    'price' => 30,
                    'priceCurrency' => 'EUR',
                ],
            ],
            $jsonEvent->priceInfo
        );
    }

    /**
     * @test
     */
    public function it_ignores_price_and_price_description_when_price_is_below_zero()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_negative_base_price.cdbxml.xml');

        $this->assertObjectNotHasAttribute('priceInfo', $jsonEvent);
    }

    /**
     * @test
     */
    public function it_ignores_price_info_when_price_value_is_invalid()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_invalid_base_price.cdbxml.xml');

        $this->assertObjectNotHasAttribute('priceInfo', $jsonEvent);
    }

    /**
     * @test
     */
    public function it_correctly_parses_price_info_when_price_is_zero()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_zero_base_price.cdbxml.xml');

        $this->assertEquals(
            [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                        'fr' => 'Tarif de base',
                        'en' => 'Base tarif',
                        'de' => 'Basisrate',
                    ],
                    'price' => 0.0,
                    'priceCurrency' => 'EUR',
                ],
            ],
            $jsonEvent->priceInfo
        );
    }

    /**
     * @test
     */
    public function it_falls_back_to_price_value_without_proper_description()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_without_properly_formatted_price_description.cdbxml.xml');

        $this->assertEquals(
            [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                        'fr' => 'Tarif de base',
                        'en' => 'Base tarif',
                        'de' => 'Basisrate',
                    ],
                    'price' => 12.5,
                    'priceCurrency' => 'EUR',
                ],
            ],
            $jsonEvent->priceInfo
        );
    }

    /**
     * @test
     */
    public function it_handles_uncommon_numeric_price_names()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_numeric_price_names_in_price_description.cdbxml.xml');

        $this->assertEquals(
            [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                        'fr' => 'Tarif de base',
                        'en' => 'Base tarif',
                        'de' => 'Basisrate',
                    ],
                    'price' => 15,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => ['nl' => '15'],
                    'price' => 15,
                    'priceCurrency' => 'EUR',
                ],
            ],
            $jsonEvent->priceInfo
        );
    }

    /**
     * @test
     */
    public function it_should_import_a_calendar_with_timestamp_without_timing()
    {
        $jsonEvent = $this->createJsonEventFromCalendarSample('event_with_timestamp_without_timing.xml');

        $this->assertEquals('single', $jsonEvent->calendarType);
        $this->assertEquals('2016-12-31T00:00:00+01:00', $jsonEvent->startDate);
        $this->assertEquals('2016-12-31T00:00:00+01:00', $jsonEvent->endDate);
    }

    /**
     * @test
     */
    public function it_should_import_a_calendar_with_timestamp_and_start_date()
    {
        $jsonEvent = $this->createJsonEventFromCalendarSample('event_with_timestamp_and_start_time.xml');

        $this->assertEquals('single', $jsonEvent->calendarType);
        $this->assertEquals('2017-04-27T20:15:00+02:00', $jsonEvent->startDate);
        $this->assertEquals('2017-04-27T20:15:00+02:00', $jsonEvent->endDate);
    }

    /**
     * @test
     */
    public function it_should_import_a_calendar_with_timestamp_and_start_and_end_date()
    {
        $jsonEvent = $this->createJsonEventFromCalendarSample('event_with_timestamp_and_start_and_end_time.xml');

        $this->assertEquals('single', $jsonEvent->calendarType);
        $this->assertEquals('2017-02-26T11:00:00+01:00', $jsonEvent->startDate);
        $this->assertEquals('2017-02-26T12:30:00+01:00', $jsonEvent->endDate);
    }

    /**
     * @test
     */
    public function it_should_import_a_calendar_with_multiple_timestamps_and_start_and_end_times()
    {
        $jsonEvent = $this->createJsonEventFromCalendarSample('event_with_multiple_timestamps_and_start_and_end_times.xml');

        $this->assertEquals('multiple', $jsonEvent->calendarType);
        $this->assertEquals('2017-02-06T13:00:00+01:00', $jsonEvent->startDate);
        $this->assertEquals('2017-03-20T16:45:00+01:00', $jsonEvent->endDate);
        $this->assertEquals(
            [
                [
                    '@type' => 'Event',
                    'startDate' => '2017-02-06T13:00:00+01:00',
                    'endDate' => '2017-02-06T16:45:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2017-02-20T13:00:00+01:00',
                    'endDate' => '2017-02-20T16:45:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2017-03-06T13:00:00+01:00',
                    'endDate' => '2017-03-06T16:45:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2017-03-20T13:00:00+01:00',
                    'endDate' => '2017-03-20T16:45:00+01:00',
                ],
            ],
            $jsonEvent->subEvent
        );
    }

    /**
     * @test
     */
    public function it_should_import_a_calendar_with_multiple_timestamps_and_different_start_and_end_times()
    {
        $jsonEvent = $this->createJsonEventFromCalendarSample('event_with_multiple_timestamps_and_different_start_and_end_times.xml');

        $this->assertEquals('multiple', $jsonEvent->calendarType);
        $this->assertEquals('2016-01-30T13:00:00+01:00', $jsonEvent->startDate);
        $this->assertEquals('2017-11-30T17:00:00+01:00', $jsonEvent->endDate);
        $this->assertEquals(
            [
                [
                    '@type' => 'Event',
                    'startDate' => '2016-01-30T13:00:00+01:00',
                    'endDate' => '2016-01-30T13:00:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2016-11-30T13:00:00+01:00',
                    'endDate' => '2016-11-30T17:00:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2016-12-03T00:00:00+01:00',
                    'endDate' => '2016-12-03T00:00:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2016-12-09T00:00:00+01:00',
                    'endDate' => '2016-12-09T00:00:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2016-12-30T13:00:00+01:00',
                    'endDate' => '2016-12-30T13:00:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2017-11-30T13:00:00+01:00',
                    'endDate' => '2017-11-30T17:00:00+01:00',
                ],
            ],
            $jsonEvent->subEvent
        );
    }

    /**
     * @test
     */
    public function it_should_import_a_calendar_with_multiple_timestamps_and_start_times()
    {
        $jsonEvent = $this->createJsonEventFromCalendarSample('event_with_multiple_timestamps_and_start_times.xml');

        $this->assertEquals('multiple', $jsonEvent->calendarType);
        $this->assertEquals('2017-02-06T13:00:00+01:00', $jsonEvent->startDate);
        $this->assertEquals('2017-03-20T13:00:00+01:00', $jsonEvent->endDate);
        $this->assertEquals(
            [
                [
                    '@type' => 'Event',
                    'startDate' => '2017-02-06T13:00:00+01:00',
                    'endDate' => '2017-02-06T13:00:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2017-02-20T13:00:00+01:00',
                    'endDate' => '2017-02-20T13:00:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2017-03-06T13:00:00+01:00',
                    'endDate' => '2017-03-06T13:00:00+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2017-03-20T13:00:00+01:00',
                    'endDate' => '2017-03-20T13:00:00+01:00',
                ],
            ],
            $jsonEvent->subEvent
        );
    }

    /**
     * @test
     */
    public function it_should_import_a_periodic_calendar()
    {
        $jsonEvent = $this->createJsonEventFromCalendarSample('event_with_periodic_calendar.xml');

        $this->assertEquals('periodic', $jsonEvent->calendarType);
        $this->assertEquals('2016-12-09T00:00:00+01:00', $jsonEvent->startDate);
        $this->assertEquals('2016-12-11T00:00:00+01:00', $jsonEvent->endDate);
    }

    /**
     * @test
     */
    public function it_should_import_a_periodic_calendar_with_week_schema()
    {
        $jsonEvent = $this->createJsonEventFromCalendarSample('event_with_periodic_calendar_and_week_schema.xml');

        $this->assertEquals('periodic', $jsonEvent->calendarType);
        $this->assertEquals('2017-06-13T00:00:00+02:00', $jsonEvent->startDate);
        $this->assertEquals('2018-01-08T00:00:00+01:00', $jsonEvent->endDate);
        $this->assertEquals(
            [
                [
                    'dayOfWeek' => [
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                        'saturday',
                    ],
                    'opens' => '10:00',
                    'closes' => '18:00',
                ],
                [
                    'dayOfWeek' => [
                        'sunday',
                    ],
                    'opens' => '08:00',
                    'closes' => '12:00',
                ],
            ],
            $jsonEvent->openingHours
        );
    }

    /**
     * @test
     */
    public function it_should_import_a_periodic_calendar_with_week_schema_and_missing_closing_times()
    {
        $jsonEvent = $this->createJsonEventFromCalendarSample('event_with_periodic_calendar_and_week_schema_and_missing_closing_times.xml');

        $this->assertEquals('periodic', $jsonEvent->calendarType);
        $this->assertEquals('2017-02-09T00:00:00+01:00', $jsonEvent->startDate);
        $this->assertEquals('2017-02-19T00:00:00+01:00', $jsonEvent->endDate);
        $this->assertEquals(
            [
                [
                    'dayOfWeek' => [
                        'monday',
                        'thursday',
                        'friday',
                        'saturday',
                    ],
                    'opens' => '20:30',
                    'closes' => '20:30',
                ],
                [
                    'dayOfWeek' => [
                        'sunday',
                    ],
                    'opens' => '16:00',
                    'closes' => '16:00',
                ],
            ],
            $jsonEvent->openingHours
        );
    }

    /**
     * @test
     */
    public function it_should_import_a_permanent_calendar()
    {
        $jsonEvent = $this->createJsonEventFromCalendarSample('event_with_permanent_calendar.xml');

        $this->assertEquals('permanent', $jsonEvent->calendarType);
    }

    /**
     * @test
     */
    public function it_should_import_a_permanent_calendar_with_opening_hours()
    {
        $jsonEvent = $this->createJsonEventFromCalendarSample('event_with_permanent_calendar_and_opening_hours.xml');

        $this->assertEquals('permanent', $jsonEvent->calendarType);
        $this->assertEquals(
            [
                [
                    'dayOfWeek' => [
                        'wednesday',
                        'saturday',
                    ],
                    'opens' => '09:30',
                    'closes' => '11:30',
                ],
                [
                    'dayOfWeek' => [
                        'thursday',
                    ],
                    'opens' => '09:00',
                    'closes' => '17:00',
                ],
            ],
            $jsonEvent->openingHours
        );
    }

    public function it_splits_contactinfo_into_contactpoint_and_bookinginfo()
    {
        $jsonEvent = $this->createJsonEventFromCdbXml('event_with_all_kinds_of_contact_info_2.cdbxml.xml');

        $this->assertEquals(
            [
                'phone' => ['0473233773'],
                'email' => ['bibliotheek@hasselt.be'],
                'url' => ['http://google.be'],
            ],
            $jsonEvent->contactPoint
        );

        $this->assertEquals(
            [
                'phone' => '987654321',
                'email' => 'tickets@test.com',
                'url' => 'http://www.test.be',
                'urlLabel' => 'Reserveer plaatsen',
            ],
            $jsonEvent->bookingInfo
        );
    }

    /**
     * @test
     */
    public function it_should_import_an_event_without_age_range_when_age_from_and_to_are_not_set()
    {
        $jsonEvent = $this->createJsonEventFromCdbXmlWithAgeRange(null, null);

        $this->assertObjectNotHasAttribute('typicalAgeRange', $jsonEvent);
    }

    /**
     * @test
     */
    public function it_should_import_an_event_with_a_lower_boundary_when_only_age_from_is_set()
    {
        $jsonEvent = $this->createJsonEventFromCdbXmlWithAgeRange(3, null);

        $this->assertEquals('3-', $jsonEvent->typicalAgeRange);
    }

    /**
     * @test
     */
    public function it_should_import_an_event_with_an_upper_boundary_when__only_age_to_is_set()
    {
        $jsonEvent = $this->createJsonEventFromCdbXmlWithAgeRange(null, 65);

        $this->assertEquals('-65', $jsonEvent->typicalAgeRange);
    }

    /**
     * @test
     */
    public function it_should_import_an_event_with_an_upper_and_lower_boundary_when_age_to_or_from_are_set_to_zero()
    {
        $jsonEvent = $this->createJsonEventFromCdbXmlWithAgeRange(0, 0);

        $this->assertEquals('0-0', $jsonEvent->typicalAgeRange);
    }

    /**
     * @test
     */
    public function it_does_not_import_missing_age_from()
    {
        $jsonEvent = $this->createJsonEventFromCdbXmlWithoutAgeFrom();

        $this->assertFalse(isset($jsonEvent->typicalAgeRange));
    }

    /**
     * Provides cdbxml with descriptions and the expected UDB3 description.
     */
    public function descriptionsProvider()
    {
        return array(
            'merge short description and long description when short description is not repeated in long description for events' => array(
                'event_with_short_and_long_description.cdbxml.xml',
                'description.txt',
            ),
            'use long description when there is no short description in UDB2' => array(
                'event_without_short_description.cdbxml.xml',
                'description_from_only_long_description.txt',
            ),
            'remove repetition of short description in long description for events when complete short description is equal to the first part of long description' => array(
                'event_with_short_description_included_in_long_description.cdbxml.xml',
                'description.txt',
            ),
            'remove repetition of short description in long description for events when complete short description is equal to the first part of long description and keep HTML of long description' => array(
                'event_vertelavond_jan_gabriels.cdbxml.xml',
                'description_vertelavond_jan_gabriels.txt',
                '3.3',
            ),
            'take ellipsis into consideration when merging short and long description' => array(
                'event_with_short_description_and_ellipsis_included_in_long_description.cdbxml.xml',
                'description.txt',
            ),
            'newlines, leading & trailing whitespace are removed from longdescription' => array(
                'event_brussels_buzzing.cdbxml.xml',
                'description_brussels_buzzing.txt',
                '3.3',
            ),
            'newlines, leading & trailing whitespace are removed from shortdescription' => array(
                'event_54695180-3ff5-4db0-a020-d54b5bdc08e9.cdbxml.xml',
                'description_54695180-3ff5-4db0-a020-d54b5bdc08e9.txt',
                '3.3',
            ),
            'short description is used when long description is absent' => array(
                'event_0001da4c-abef-4450-b37a-5a4bfb9d35f4.cdbxml.xml',
                'description_0001da4c-abef-4450-b37a-5a4bfb9d35f4.txt',
                '3.3',
            ),
        );
    }

    /**
     * @test
     * @group issue-III-165
     * @group issue-III-1715
     * @dataProvider descriptionsProvider
     *
     * @param string $cdbxmlFile
     * @param string $expectedDescriptionFile
     * @param string $schemaVersion
     */
    public function it_combines_long_and_short_description_to_one_description(
        $cdbxmlFile,
        $expectedDescriptionFile,
        $schemaVersion = '3.2'
    ) {
        $jsonEvent = $this->createJsonEventFromCdbXml($cdbxmlFile, $schemaVersion);

        $this->assertEquals(
            file_get_contents(__DIR__ . '/' . $expectedDescriptionFile),
            $jsonEvent->description['nl']
        );
    }

    /**
     * @test
     * @group issue-III-1706
     * @dataProvider audienceProvider
     *
     * @param string $cdbxmlFile
     * @param array $expectedAudience
     */
    public function it_should_import_audience($cdbxmlFile, $expectedAudience)
    {
        $jsonEvent = $this->createJsonEventFromCdbXml($cdbxmlFile, '3.3');
        $this->assertEquals($expectedAudience, $jsonEvent->audience);
    }

    public function audienceProvider()
    {
        return [
            "import event without property 'private' as audienceType 'everyone'" => [
                'event_without_private_attribute.xml',
                ['audienceType' => 'everyone'],
            ],
            "import event with value 'private=false' as audienceType 'everyone'" => [
                'event_with_private_attribute_false.xml',
                ['audienceType' => 'everyone'],
            ],
            "import event with value 'private=true' as audienceType 'members'" => [
                'event_with_private_attribute_true.xml',
                ['audienceType' => 'members'],
            ],
            "import event with value 'private=true' AND category_id '2.1.3.0.0' as audienceType 'education'" => [
                'event_with_private_attribute_true_and_education_category.xml',
                ['audienceType' => 'education'],
            ],
        ];
    }
}
