<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\CalendarSummary\ContentType;
use CultuurNet\UDB3\EventExport\CalendarSummary\Format;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\TaalicoonDescription;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event\EventAdvantage;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfo;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HTMLEventFormatterTest extends TestCase
{
    protected HTMLEventFormatter $eventFormatter;

    public function setUp(): void
    {
        $this->eventFormatter = new HTMLEventFormatter();

        if (!class_exists('IntlDateFormatter')) {
            $this->markTestSkipped(
                'IntlDateFormatter is missing, please install the PHP intl extension in order to run this test.'
            );
        }
    }

    protected function getJSONEventFromFile(string $fileName): string
    {
        return SampleFiles::read(__DIR__ . '/../../samples/' . $fileName);
    }

    protected function getFormattedEventFromJSONFile(string $fileName): array
    {
        $event = $this->getJSONEventFromFile($fileName);
        $decodedEvent = Json::decode($event);
        $urlParts = explode('/', $decodedEvent->{'@id'});
        $eventId = end($urlParts);
        return $this->eventFormatter->formatEvent($eventId, $event);
    }

    protected function assertEventFormatting(array $expected, array $actual): void
    {
        if (isset($actual['description'])) {
            $this->assertLessThanOrEqual(
                300,
                mb_strlen($actual['description'])
            );
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_distills_event_info_to_what_is_needed_for_html_export(): void
    {
        $freeEvent = $this->getFormattedEventFromJSONFile('event_with_terms.json');
        $expectedFormattedFreeEvent = [
            'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
            'image' => 'http://media.uitdatabank.be/20141211/558bb7cf-5ff8-40b4-872b-5f5b46bb16c2.jpg',
            'description' => 'De islam is niet meer weg te denken uit onze maatschappij. ' . 'Aan de hand van boeiende anekdotes doet Urbain Vermeulen de ontstaansgeschiedenis ' . 'van de godsdienst uit de doeken...',
            'address' => [
                'name' => 'Cultuurcentrum De Kruisboog',
                'street' => 'Sint-Jorisplein 20 ',
                'postcode' => '3300',
                'municipality' => 'Tienen',
                'country' => 'BE',
                'concatenated' => 'Sint-Jorisplein 20  3300 Tienen BE',
                'isDummyAddress' => false,
            ],
            'type' => 'Cursus of workshop',
            'price' => 'Gratis',
            'brands' => [],
            'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ma 30/03/15 van 13:30 tot 16:30 ',
        ];
        $this->assertEventFormatting($expectedFormattedFreeEvent, $freeEvent);

        $pricedEvent = $this->getFormattedEventFromJSONFile('event_with_price.json');
        $expectedFormattedPricedEvent = $expectedFormattedFreeEvent;
        $expectedFormattedPricedEvent['price'] = '10,5';
        $this->assertEventFormatting($expectedFormattedPricedEvent, $pricedEvent);
    }

    /**
     * @test
     */
    public function it_marks_the_address_as_dummy_if_the_location_is_a_dummy_for_bookable_education_events(): void
    {
        $freeEvent = $this->getFormattedEventFromJSONFile('event_with_dummy_location.json');
        $expectedFormattedFreeEvent = [
            'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
            'image' => 'http://media.uitdatabank.be/20141211/558bb7cf-5ff8-40b4-872b-5f5b46bb16c2.jpg',
            'description' => 'De islam is niet meer weg te denken uit onze maatschappij. ' . 'Aan de hand van boeiende anekdotes doet Urbain Vermeulen de ontstaansgeschiedenis ' . 'van de godsdienst uit de doeken...',
            'address' => [
                'name' => 'Cultuurcentrum De Kruisboog',
                'street' => 'Sint-Jorisplein 20 ',
                'postcode' => '3300',
                'municipality' => 'Tienen',
                'country' => 'BE',
                'concatenated' => 'Sint-Jorisplein 20  3300 Tienen BE',
                'isDummyAddress' => true,
            ],
            'type' => 'Cursus of workshop',
            'price' => 'Gratis',
            'brands' => [],
            'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ma 30/03/15 van 13:30 tot 16:30 ',
        ];
        $this->assertEventFormatting($expectedFormattedFreeEvent, $freeEvent);
    }

    /**
     * @test
     */
    public function it_marks_the_address_as_dummy_for_nil_location(): void
    {
        $freeEvent = $this->getFormattedEventFromJSONFile('event_with_nil_location.json');
        $expectedFormattedFreeEvent = [
            'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
            'image' => 'http://media.uitdatabank.be/20141211/558bb7cf-5ff8-40b4-872b-5f5b46bb16c2.jpg',
            'description' => 'De islam is niet meer weg te denken uit onze maatschappij. ' . 'Aan de hand van boeiende anekdotes doet Urbain Vermeulen de ontstaansgeschiedenis ' . 'van de godsdienst uit de doeken...',
            'address' => [
                'name' => 'Online',
                'street' => '___ ',
                'postcode' => '0000',
                'municipality' => '___',
                'country' => 'BE',
                'concatenated' => '___  0000 ___ BE',
                'isDummyAddress' => true,
            ],
            'type' => 'Cursus of workshop',
            'price' => 'Gratis',
            'brands' => [],
            'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ma 30/03/15 van 13:30 tot 16:30 ',
        ];
        $this->assertEventFormatting($expectedFormattedFreeEvent, $freeEvent);
    }

    /**
     * @test
     */
    public function it_handles_event_with_location_with_missing_id(): void
    {
        $freeEvent = $this->getFormattedEventFromJSONFile('event_with_location_id_missing.json');
        $expectedFormattedFreeEvent = [
            'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
            'image' => 'http://media.uitdatabank.be/20141211/558bb7cf-5ff8-40b4-872b-5f5b46bb16c2.jpg',
            'description' => 'De islam is niet meer weg te denken uit onze maatschappij. ' . 'Aan de hand van boeiende anekdotes doet Urbain Vermeulen de ontstaansgeschiedenis ' . 'van de godsdienst uit de doeken...',
            'address' => [
                'name' => 'Cultuurcentrum De Kruisboog',
                'street' => 'Sint-Jorisplein 20 ',
                'postcode' => '3300',
                'municipality' => 'Tienen',
                'country' => 'BE',
                'concatenated' => 'Sint-Jorisplein 20  3300 Tienen BE',
                'isDummyAddress' => false,
            ],
            'type' => 'Cursus of workshop',
            'price' => 'Gratis',
            'brands' => [],
            'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ma 30/03/15 van 13:30 tot 16:30 ',
        ];
        $this->assertEventFormatting($expectedFormattedFreeEvent, $freeEvent);
    }

    /**
     * @test
     */
    public function it_handles_event_with_location_with_id_null(): void
    {
        $freeEvent = $this->getFormattedEventFromJSONFile('event_with_location_id_null.json');
        $expectedFormattedFreeEvent = [
            'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
            'image' => 'http://media.uitdatabank.be/20141211/558bb7cf-5ff8-40b4-872b-5f5b46bb16c2.jpg',
            'description' => 'De islam is niet meer weg te denken uit onze maatschappij. ' . 'Aan de hand van boeiende anekdotes doet Urbain Vermeulen de ontstaansgeschiedenis ' . 'van de godsdienst uit de doeken...',
            'address' => [
                'name' => 'Cultuurcentrum De Kruisboog',
                'street' => 'Sint-Jorisplein 20 ',
                'postcode' => '3300',
                'municipality' => 'Tienen',
                'country' => 'BE',
                'concatenated' => 'Sint-Jorisplein 20  3300 Tienen BE',
                'isDummyAddress' => false,
            ],
            'type' => 'Cursus of workshop',
            'price' => 'Gratis',
            'brands' => [],
            'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ma 30/03/15 van 13:30 tot 16:30 ',
        ];
        $this->assertEventFormatting($expectedFormattedFreeEvent, $freeEvent);
    }

    /**
     * @test
     */
    public function it_indicates_if_price_is_unknown(): void
    {
        $eventWithoutBookingInfo = $this->getFormattedEventFromJSONFile('event_without_priceinfo.json');
        $expectedFormattedEvent = [
            'image' => 'http://media.uitdatabank.be/20141211/558bb7cf-5ff8-40b4-872b-5f5b46bb16c2.jpg',
            'type' => 'Cursus of workshop',
            'title' => 'Lessenreeks MURGA',
            'description' => "Wij zijn Murga çava, een vrolijke groep van 20 percussionisten,\n" . "jong en oud, uit Herent en omgeving. Bij ons is iedereen welkom!\n" . "Muzikale voorkennis is geen vereiste. Behalve percussie staan we\n" . 'ook open voor blazers, dansers of ander talent...',
            'address' => [
                'name' => 'GC De Wildeman',
                'street' => 'Schoolstraat 15',
                'postcode' => '3020',
                'municipality' => 'Herent',
                'country' => 'BE',
                'concatenated' => 'Schoolstraat 15 3020 Herent BE',
                'isDummyAddress' => false,
            ],
            'price' => 'Niet ingevoerd',
            'brands' => [],
            'dates' => 'van 01/09/14 tot 29/06/15',
        ];
        $this->assertEventFormatting($expectedFormattedEvent, $eventWithoutBookingInfo);
    }

    /**
     * @test
     */
    public function it_gracefully_handles_events_without_description(): void
    {
        $eventWithoutDescription = $this->getFormattedEventFromJSONFile('event_without_description.json');
        $expectedFormattedEvent = [
            'type' => 'Cursus of workshop',
            'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
            'address' => [
                'name' => 'Cultuurcentrum De Kruisboog',
                'street' => 'Sint-Jorisplein 20 ',
                'postcode' => '3300',
                'municipality' => 'Tienen',
                'country' => 'BE',
                'concatenated' => 'Sint-Jorisplein 20  3300 Tienen BE',
                'isDummyAddress' => false,
            ],
            'price' => 'Gratis',
            'brands' => [],
            'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ma 30/03/15 van 13:30 tot 16:30 ',
        ];
        $this->assertEventFormatting($expectedFormattedEvent, $eventWithoutDescription);
    }

    /**
     * @test
     */
    public function it_gracefully_handles_events_without_image(): void
    {
        $eventWithoutImage = $this->getFormattedEventFromJSONFile('event_without_image.json');
        $expectedFormattedEvent = [
            'type' => 'Cursus of workshop',
            'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
            'description' => 'De islam is niet meer weg te denken uit onze maatschappij. ' . 'Aan de hand van boeiende anekdotes doet Urbain Vermeulen de ontstaansgeschiedenis ' . 'van de godsdienst uit de doeken...',
            'address' => [
                'name' => 'Cultuurcentrum De Kruisboog',
                'street' => 'Sint-Jorisplein 20 ',
                'postcode' => '3300',
                'municipality' => 'Tienen',
                'country' => 'BE',
                'concatenated' => 'Sint-Jorisplein 20  3300 Tienen BE',
                'isDummyAddress' => false,
            ],
            'price' => 'Niet ingevoerd',
            'brands' => [],
            'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ma 30/03/15 van 13:30 tot 16:30 ',
        ];
        $this->assertEventFormatting($expectedFormattedEvent, $eventWithoutImage);
    }

    public function locationVariationsDataProvider(): array
    {
        $expectedFormattedEvent = [
            'type' => 'Cursus of workshop',
            'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
            'description' => 'De islam is niet meer weg te denken uit onze maatschappij. ' . 'Aan de hand van boeiende anekdotes doet Urbain Vermeulen de ontstaansgeschiedenis ' . 'van de godsdienst uit de doeken...',
            'price' => 'Gratis',
            'brands' => [],
            'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ma 30/03/15 van 13:30 tot 16:30 ',
        ];

        return [
            'without location' => [
                'event_without_location.json',
                $expectedFormattedEvent,
            ],
            'without location address' => [
                'event_without_location_address.json',
                $expectedFormattedEvent + [
                    'address' => [
                        'name' => 'Cultuurcentrum De Kruisboog',
                        'isDummyAddress' => false,
                    ],
                ],
            ],
            'without location name' => [
                'event_without_location_name.json',
                $expectedFormattedEvent + [
                    'address' => [
                        'street' => 'Sint-Jorisplein 20 ',
                        'postcode' => '3300',
                        'municipality' => 'Tienen',
                        'country' => 'BE',
                        'concatenated' => 'Sint-Jorisplein 20  3300 Tienen BE',
                        'isDummyAddress' => false,
                    ],
                ],
            ],
            'with coordinates' => [
                'event_with_location_coordinates.json',
                $expectedFormattedEvent + [
                    'address' => [
                        'latitude' => '50.804739',
                        'longitude' => '4.936491',
                        'isDummyAddress' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider locationVariationsDataProvider
     */
    public function it_gracefully_handles_events_without_or_with_partial_location(
        string $sample,
        array  $expectedFormattedEvent
    ): void {
        $eventWithoutImage = $this->getFormattedEventFromJSONFile($sample);
        $this->assertEventFormatting($expectedFormattedEvent, $eventWithoutImage);
    }

    /**
     * @test
     */
    public function it_gracefully_handles_events_without_eventtype(): void
    {
        $eventWithoutEventType = $this->getFormattedEventFromJSONFile('event_without_eventtype.json');
        $expectedFormattedEvent = [
            'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
            'description' => 'De islam is niet meer weg te denken uit onze maatschappij. ' . 'Aan de hand van boeiende anekdotes doet Urbain Vermeulen de ontstaansgeschiedenis ' . 'van de godsdienst uit de doeken...',
            'address' => [
                'name' => 'Cultuurcentrum De Kruisboog',
                'street' => 'Sint-Jorisplein 20 ',
                'postcode' => '3300',
                'municipality' => 'Tienen',
                'country' => 'BE',
                'concatenated' => 'Sint-Jorisplein 20  3300 Tienen BE',
                'isDummyAddress' => false,
            ],
            'price' => 'Gratis',
            'brands' => [],
            'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ma 30/03/15 van 13:30 tot 16:30 ',
        ];
        $this->assertEventFormatting($expectedFormattedEvent, $eventWithoutEventType);
    }

    /**
     * @test
     */
    public function it_strips_html_and_truncates_the_description(): void
    {
        $eventWithHTMLDescription = $this->getFormattedEventFromJSONFile('event_with_html_description.json');
        $this->assertEquals(
            "Opnieuw twee dagen na elkaar en ook ditmaal brengen ze drie\n" . "artiestenmee die garant staan voor authenticiteit en originaliteit.\n" . "De eerste gastis niemand minder dan Stoomboot, die in het seizoen\n" . "2014 doorbrakmet zijn bejubelde debuutalbum. Verder is ooK fluitist\n" . 'Stefan Bracavalopnieuw van de partij...',
            $eventWithHTMLDescription['description']
        );
    }

    /**
     * @test
     */
    public function it_optionally_enriches_events_with_calendar_period_info(): void
    {
        $id = 'd1f0e71d-a9a8-4069-81fb-530134502c58';
        $expectedSummary = $this->getExpectedCalendarSummary('calendar_summary_periods.html');

        $repository = $this->getCalendarSummaryRepositoryWhichReturns($id, $expectedSummary);
        $this->eventFormatter = new HTMLEventFormatter(null, $repository);

        $event = $this->getFormattedEventFromJSONFile('event_with_terms.json');
        $this->assertFormattedEventDates($event, $expectedSummary);
    }

    /**
     * @test
     */
    public function it_optionally_enriches_events_with_calendar_timestamps_info(): void
    {
        $id = 'd1f0e71d-a9a8-4069-81fb-530134502c58';
        $expectedSummary = $this->getExpectedCalendarSummary('calendar_summary_timestamps.html');

        $repository = $this->getCalendarSummaryRepositoryWhichReturns($id, $expectedSummary);
        $this->eventFormatter = new HTMLEventFormatter(null, $repository);

        $event = $this->getFormattedEventFromJSONFile('event_with_terms.json');
        $this->assertFormattedEventDates($event, $expectedSummary);
    }

    /**
     * @test
     */
    public function it_optionally_enriches_events_with_calendar_permanent_info(): void
    {
        $id = 'd1f0e71d-a9a8-4069-81fb-530134502c58';
        $expectedSummary = $this->getExpectedCalendarSummary('calendar_summary_permanent.html');

        $repository = $this->getCalendarSummaryRepositoryWhichReturns($id, $expectedSummary);
        $this->eventFormatter = new HTMLEventFormatter(null, $repository);

        $event = $this->getFormattedEventFromJSONFile('event_with_terms.json');
        $this->assertFormattedEventDates($event, $expectedSummary);
    }

    /**
     * @return CalendarSummaryRepositoryInterface&MockObject
     */
    private function getCalendarSummaryRepositoryWhichReturns(string $id, string $calendarSummary)
    {
        /* @var CalendarSummaryRepositoryInterface&MockObject $repository */
        $repository = $this->createMock(CalendarSummaryRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('get')
            ->with($id, ContentType::html(), Format::sm())
            ->willReturn($calendarSummary);
        return $repository;
    }

    private function getExpectedCalendarSummary(string $fileName): string
    {
        $expected = SampleFiles::read(__DIR__ . '/../../samples/' . $fileName);
        return trim($expected);
    }

    private function assertFormattedEventDates(array $event, string $expected): void
    {
        $this->assertArrayHasKey('dates', $event);
        $this->assertEquals($expected, $event['dates']);
    }

    /**
     * @test
     * @dataProvider uitpasInfoProvider
     */
    public function it_optionally_enriches_events_with_uitpas_info(
        array $priceData,
        array $advantagesData,
        array $promotionsData
    ): void {
        $eventWithoutImage = $this->getJSONEventFromFile('event_without_image.json');

        /* @var EventInfoServiceInterface&MockObject $uitpas */
        $uitpas = $this->createMock(EventInfoServiceInterface::class);

        $prices = $priceData['original'];
        $expectedPrices = $priceData['formatted'];

        $advantages = $advantagesData['original'];
        $expectedAdvantages = $advantagesData['formatted'];

        $promotions = $promotionsData['original'];
        $expectedPromotions = $promotionsData['formatted'];

        $eventInfo = new EventInfo($prices, $advantages, $promotions);

        $uitpas->expects($this->once())
            ->method('getEventInfo')
            ->with('d1f0e71d-a9a8-4069-81fb-530134502c58')
            ->willReturn($eventInfo);

        $eventFormatter = new HTMLEventFormatter($uitpas);

        $formattedEvent = $eventFormatter->formatEvent(
            'd1f0e71d-a9a8-4069-81fb-530134502c58',
            $eventWithoutImage
        );

        $expectedFormattedEvent = [
            'uitpas' => [
                'prices' => $expectedPrices,
                'advantages' => $expectedAdvantages,
                'promotions' => $expectedPromotions,
            ],
            'type' => 'Cursus of workshop',
            'title' => 'Koran, kaliefen en kruistochten - De fundamenten van de islam',
            'description' => 'De islam is niet meer weg te denken uit onze maatschappij. ' . 'Aan de hand van boeiende anekdotes doet Urbain Vermeulen de ontstaansgeschiedenis ' . 'van de godsdienst uit de doeken...',
            'address' => [
                'name' => 'Cultuurcentrum De Kruisboog',
                'street' => 'Sint-Jorisplein 20 ',
                'postcode' => '3300',
                'municipality' => 'Tienen',
                'country' => 'BE',
                'concatenated' => 'Sint-Jorisplein 20  3300 Tienen BE',
                'isDummyAddress' => false,
            ],
            'price' => 'Niet ingevoerd',
            'brands' => [],
            'dates' => 'ma 02/03/15 van 13:30 tot 16:30  ma 09/03/15 van 13:30 tot 16:30  ' . 'ma 16/03/15 van 13:30 tot 16:30  ma 23/03/15 van 13:30 tot 16:30  ma 30/03/15 van 13:30 tot 16:30 ',
        ];

        $this->assertEquals(
            $expectedFormattedEvent,
            $formattedEvent
        );
    }

    public function uitpasInfoProvider(): array
    {
        // Prices and their expected formatting, and advantages and their expected formatting.
        return [
            [
                [
                    'original' => [
                        [
                            'price' => '1.5',
                            'label' => 'Kansentarief voor UiTPAS Regio Aalst',
                        ],
                    ],
                    'formatted' => [
                        [
                            'price' => '1,5',
                            'label' => 'Kansentarief voor UiTPAS Regio Aalst',
                        ],
                    ],
                ],
                [
                    'original' => [
                        EventAdvantage::kansenTarief(),
                    ],
                    'formatted' => [
                        'Korting voor kansentarief',
                    ],
                ],
                [
                    'original' => ['12 punten: Een voordeel van 12 punten.'],
                    'formatted' => ['12 punten: Een voordeel van 12 punten.'],
                ],
            ],
            [
                [
                    'original' => [
                        [
                            'price' => '3.0',
                            'label' => 'Kansentarief voor kaarthouders uit een andere regio',
                        ],
                    ],
                    'formatted' => [
                        [
                            'price' => '3',
                            'label' => 'Kansentarief voor kaarthouders uit een andere regio',
                        ],
                    ],
                ],
                [
                    'original' => [
                        EventAdvantage::pointCollecting(),
                        EventAdvantage::kansenTarief(),
                    ],
                    'formatted' => [
                        'Spaar punten',
                        'Korting voor kansentarief',
                    ],
                ],
                [
                    'original' => ['12 punten: Een voordeel van 12 punten.'],
                    'formatted' => ['12 punten: Een voordeel van 12 punten.'],
                ],
            ],
            [
                [
                    'original' => [
                        [
                            'price' => '150.0',
                            'label' => 'Kansentarief voor UiTPAS Regio Aalst',
                        ],
                    ],
                    'formatted' => [
                        [
                            'price' => '150',
                            'label' => 'Kansentarief voor UiTPAS Regio Aalst',
                        ],
                    ],
                ],
                [
                    'original' => [
                        EventAdvantage::kansenTarief(),
                        EventAdvantage::pointCollecting(),
                    ],
                    'formatted' => [
                        'Spaar punten',
                        'Korting voor kansentarief',
                    ],
                ],
                [
                    'original' => ['12 punten: Een voordeel van 12 punten.'],
                    'formatted' => ['12 punten: Een voordeel van 12 punten.'],
                ],
            ],
            [
                [
                    'original' => [
                        [
                            'price' => '30',
                            'label' => 'Kansentarief voor kaarthouders uit een andere regio',
                        ],
                    ],
                    'formatted' => [
                        [
                            'price' => '30',
                            'label' => 'Kansentarief voor kaarthouders uit een andere regio',
                        ],
                    ],
                ],
                [
                    'original' => [],
                    'formatted' => [],
                ],
                [
                    'original' => ['12 punten: Een voordeel van 12 punten.'],
                    'formatted' => ['12 punten: Een voordeel van 12 punten.'],
                ],
            ],
            [
                [
                    'original' => [],
                    'formatted' => [],
                ],
                [
                    'original' => [
                        EventAdvantage::pointCollecting(),
                    ],
                    'formatted' => [
                        'Spaar punten',
                    ],
                ],
                [
                    'original' => ['12 punten: Een voordeel van 12 punten.'],
                    'formatted' => ['12 punten: Een voordeel van 12 punten.'],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_correctly_sets_the_taalicoon_count_and_description(): void
    {
        $eventWithFourTaaliconen = $this->getJSONEventFromFile('event_with_icon_label.json');
        $formattedEvent = $this->eventFormatter->formatEvent(
            'd1f0e71d-a9a8-4069-81fb-530134502c58',
            $eventWithFourTaaliconen
        );
        $this->assertEquals(4, $formattedEvent['taalicoonCount']);
        $this->assertEquals(TaalicoonDescription::vierTaaliconen()->toString(), $formattedEvent['taalicoonDescription']);

        $eventWithAllTaaliconen = $this->getJSONEventFromFile('event_with_all_icon_labels.json');
        $formattedEvent = $this->eventFormatter->formatEvent(
            'd1f0e71d-a9a8-4069-81fb-530134502c58',
            $eventWithAllTaaliconen
        );
        $this->assertArrayNotHasKey('taalicoonCount', $formattedEvent);
        $this->assertArrayNotHasKey('taalicoonDescription', $formattedEvent);
    }

    /**
     * @test
     */
    public function it_shows_activity_branding(): void
    {
        $event = $this->getJSONEventFromFile(
            'event_with_all_icon_labels.json'
        );

        $formattedEvent = $this->eventFormatter->formatEvent(
            'd1f0e71d-a9a8-4069-81fb-530134502c58',
            $event
        );
        $this->assertContains('uitpas', $formattedEvent['brands']);
        $this->assertContains('vlieg', $formattedEvent['brands']);
    }

    /**
     * @test
     */
    public function it_adds_the_starting_age_when_event_has_age_range(): void
    {
        $event = $this->getJSONEventFromFile(
            'event_with_all_icon_labels.json'
        );

        $formattedEvent = $this->eventFormatter->formatEvent(
            'd1f0e71d-a9a8-4069-81fb-530134502c58',
            $event
        );
        $this->assertEquals(5, $formattedEvent['ageFrom']);
    }

    /**
     * @test
     */
    public function it_should_include_the_media_object_of_the_main_image_when_set(): void
    {
        $event = $this->getJSONEventFromFile('event_with_main_image.json');

        $formattedEvent = $this->eventFormatter->formatEvent(
            'd1f0e71d-a9a8-4069-81fb-530134502c58',
            $event
        );

        $expectedMediaObject = (object) [
            '@id' =>  'https://io.uitdatabank.be/media/558bb7cf-5ff8-40b4-872b-5f5b46bb16c2',
            '@type' =>  'schema:ImageObject',
            'contentUrl' =>  'http://media.uitdatabank.be/558bb7cf-5ff8-40b4-872b-5f5b46bb16c2.jpg',
            'thumbnailUrl' =>  'http://media.uitdatabank.be/558bb7cf-5ff8-40b4-872b-5f5b46bb16c2.jpg',
            'description' =>  'De Kortste Nacht',
            'copyrightHolder' =>  'Rode Ridder',
        ];

        $this->assertEquals($expectedMediaObject, $formattedEvent['mediaObject']);
    }
}
