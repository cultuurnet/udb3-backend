<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\CalendarSummary\ContentType;
use CultuurNet\UDB3\EventExport\CalendarSummary\Format;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event\EventAdvantage;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfo;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

class TabularDataEventFormatterTest extends TestCase
{
    private function getJSONEventFromFile(string $fileName): string
    {
        return SampleFiles::read(__DIR__ . '/../../samples/' . $fileName);
    }

    /**
     * @test
     */
    public function it_keeps_the_columns_of_a_default_export_in_a_stable_order(): void
    {
        $formatter = new TabularDataEventFormatter([]);

        $this->assertSame(
            [
                'id',
                'titel',
                'auteur',
                'basistarief',
                'prijsinformatie',
                'kansentarief',
                'reservatie url',
                'reservatie tel',
                'reservatie e-mail',
                'omschrijving',
                'organisatie',
                'korte kalendersamenvatting',
                'lange kalendersamenvatting',
                'labels',
                'verborgen labels',
                'leeftijd',
                'uitvoerders',
                'taal van het aanbod',
                'thema',
                'soort aanbod',
                'datum aangemaakt',
                'datum laatste aanpassing',
                'embargodatum',
                'startdatum',
                'einddatum',
                'tijd type',
                'locatie naam',
                'straat',
                'postcode',
                'gemeente',
                'land',
                'afbeelding URL',
                'afbeelding beschrijving',
                'afbeelding copyright',
                'externe ids',
                'contact e-mail',
                'contact tel',
                'contact url',
                'e-mail reservaties',
                'telefoon reservaties',
                'online reservaties',
                'toegang',
                'status',
                'tickets & plaatsen',
                'videos URL',
                'videos copyright',
                'Aanwezigheidsvorm (fysiek / online)',
                'online url',
                'Volledigheid',
                // New columns belong at the end, so that the position of every column that
                // integrators already read stays the same.
                'faq',
                'met overnachting',
                'doelgroep',
            ],
            $formatter->formatHeader()
        );
    }

    /**
     * @test
     */
    public function it_excludes_all_terms_when_none_are_included(): void
    {
        $includedProperties = [
            'id',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_terms.json');
        $formatter = new TabularDataEventFormatter($includedProperties);

        $formattedEvent = $formatter->formatEvent($eventWithTerms);
        $formattedProperties = array_keys($formattedEvent);

        $this->assertEquals($includedProperties, $formattedProperties);
    }

    /**
     * @test
     */
    public function it_excludes_other_terms_when_some_are_included(): void
    {
        $includedProperties = [
            'id',
            'terms.eventtype',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_terms.json');
        $formatter = new TabularDataEventFormatter($includedProperties);

        $formattedEvent = $formatter->formatEvent($eventWithTerms);
        $formattedProperties = array_keys($formattedEvent);

        $this->assertEquals($includedProperties, $formattedProperties);
    }

    /**
     * @test
     */
    public function it_formats_included_terms(): void
    {
        $includedProperties = [
            'id',
            'terms.eventtype',
            'terms.theme',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_terms.json');
        $formatter = new TabularDataEventFormatter($includedProperties);

        $formattedEvent = $formatter->formatEvent($eventWithTerms);
        $expectedFormatting = [
            'id' =>'d1f0e71d-a9a8-4069-81fb-530134502c58',
            'terms.eventtype' => 'Cursus of workshop',
            'terms.theme' => 'Geschiedenis',
        ];

        $this->assertEquals($expectedFormatting, $formattedEvent);
    }

    /**
     * @test
     * @dataProvider organizerDataProvider
     * @bugfix https://jira.uitdatabank.be/browse/III-3921
     */
    public function it_handles_organizer(string $sampleFile): void
    {
        $includedProperties = [
            'organizer',
        ];
        $eventWithTranslatedOrganizer = $this->getJSONEventFromFile($sampleFile);
        $formatter = new TabularDataEventFormatter($includedProperties);

        $formattedEvent = $formatter->formatEvent($eventWithTranslatedOrganizer);
        $expectedFormatting = [
            'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
            'organizer' => 'Davidsfonds Academie',
        ];

        $this->assertEquals($expectedFormatting, $formattedEvent);
    }

    public function organizerDataProvider(): array
    {
        return [
            [
                'event_with_translated_organizer.json',
            ],
            [
                'event_with_translated_organizer_and_main_language.json',
            ],
            [
                'event_with_untranslated_organizer.json',
            ],
            [
                'event_with_organizer_with_different_mainLanguage.json',
            ],
        ];
    }

    /**
     * @test
     * @bugfix https://jira.uitdatabank.be/browse/III-3921
     */
    public function it_handles_missing_typicalAgeRange(): void
    {
        $includedProperties = [
            'typicalAgeRange',
        ];
        $eventWithTranslatedOrganizer = $this->getJSONEventFromFile('event_with_dates.json');
        $formatter = new TabularDataEventFormatter($includedProperties);

        $formattedEvent = $formatter->formatEvent($eventWithTranslatedOrganizer);
        $expectedFormatting = [
            'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
            'typicalAgeRange' => '',
        ];

        $this->assertEquals($expectedFormatting, $formattedEvent);
    }

    /**
     * @test
     * @dataProvider addressDataProvider
     */
    public function it_handles_address(string $sampleFile): void
    {
        $includedProperties = [
            'id',
            'address',
        ];
        $eventWithTerms = $this->getJSONEventFromFile($sampleFile);
        $formatter = new TabularDataEventFormatter($includedProperties);

        $formattedEvent = $formatter->formatEvent($eventWithTerms);
        $expectedFormatting = [
            'id' =>'d1f0e71d-a9a8-4069-81fb-530134502c58',
            'address.streetAddress' => 'Sint-Jorisplein 20 ',
            'address.postalCode' => '3300',
            'address.addressLocality' => 'Tienen',
            'address.addressCountry' => 'BE',
        ];

        $this->assertEquals($expectedFormatting, $formattedEvent);
    }

    public function addressDataProvider(): array
    {
        return [
            [
                'event_with_terms.json',
            ],
            [
                'event_with_translated_address.json',
            ],
            [
                'event_with_translated_address_and_main_language.json',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider eventDateProvider
     */
    public function it_formats_dates(string $eventFile, array $expectedFormattedEvent): void
    {
        $event = $this->getJSONEventFromFile($eventFile);

        $formatter = new TabularDataEventFormatter(
            array_keys($expectedFormattedEvent)
        );

        $formattedEvent = $formatter->formatEvent($event);

        // We do not care about the event 'id' here, which is always included.
        unset($formattedEvent['id']);

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    /**
     * Test data provider for it_formats_dates().
     *
     *   Array of individual arrays, each containing the arguments for the test method.
     */
    public function eventDateProvider(): array
    {
        return [
            [
                'event_with_dates.json',
                [
                    'created' => '2014-12-11 17:30',
                    'startDate' => '2015-03-02 13:30',
                    'endDate' => '2015-03-30 16:30',
                    'modified' => '',
                ],
            ],
            [
                'event_without_end_date.json',
                [
                    'created' => '2014-12-11 17:30',
                    'startDate' => '2015-03-02 13:30',
                    'endDate' => '',
                    'modified' => '',
                ],
            ],
            [
                'event_with_modified_date.json',
                [
                    'created' => '2015-10-13 16:27',
                    'startDate' => '2015-10-29 20:00',
                    'endDate' => '',
                    'modified' => '2015-10-13 16:27',
                ],
            ],
            [
                'event_with_outdated_start_and_end_date_format.json',
                [
                    'created' => '2014-12-11 17:30',
                    'startDate' => '2015-03-02 14:30',
                    'endDate' => '2015-03-30 18:30',
                    'modified' => '',
                ],
            ],
            [
                'event_with_incorrect_start_and_end_date_format.json',
                [
                    'created' => '2014-12-11 17:30',
                    'startDate' => '',
                    'endDate' => '',
                    'modified' => '',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_can_format_an_empty_image(): void
    {
        $event = $this->getJSONEventFromFile('event_without_image.json');
        $formatter = new TabularDataEventFormatter(['image']);
        $formattedEvent = $formatter->formatEvent($event);

        $this->assertTrue(isset($formattedEvent['image.url']));
        $this->assertEmpty($formattedEvent['image.url']);
    }

    /**
     * @test
     * @group issue-III-1506
     */
    public function it_can_format_event_with_a_contact_point(): void
    {
        $includedProperties = [
            'id',
            'contactPoint.email',
            'contactPoint.phone',
            'contactPoint.url',
        ];
        $eventWithContactPoints = $this->getJSONEventFromFile('event_with_a_contact_point.json');
        $formatter = new TabularDataEventFormatter($includedProperties);

        $formattedEvent = $formatter->formatEvent($eventWithContactPoints);

        $expectedFormatting = [
            'id' =>'16744083-859a-4d3d-bd1d-16ea5bd3e2a3',
            'contactPoint.email' => 'nicolas.leroy+test@gmail.com;jane.doe@example.com',
            'contactPoint.phone' => '016 66 69 99;016 99 96 66',
            'contactPoint.url' => 'http://contact.example.com;https://contact.example.com',
        ];

        $this->assertEquals($expectedFormatting, $formattedEvent);
    }

    /**
     * @test
     */
    public function it_formats_available_date(): void
    {
        $includedProperties = [
            'id',
            'available',
        ];
        $eventWithAvailableDate = $this->getJSONEventFromFile('event_with_available_from.json');
        $formatter = new TabularDataEventFormatter($includedProperties);

        $formattedEvent = $formatter->formatEvent($eventWithAvailableDate);
        $expectedFormatting = [
            'id' =>'16744083-859a-4d3d-bd1d-16ea5bd3e2a3',
            'available' => '2015-10-13',
        ];

        $this->assertEquals($expectedFormatting, $formattedEvent);
    }

    /**
     * @test
     */
    public function it_should_strip_line_breaking_white_spaces_that_are_not_set_by_markup(): void
    {
        $includedProperties = [
            'id',
            'description',
        ];
        $eventWithUnwantedLineBreaks = $this->getJSONEventFromFile('event_with_unwanted_line_breaks.json');

        $formatter = new TabularDataEventFormatter($includedProperties);

        /* @codingStandardsIgnoreStart */
        $expectedDescription = 'Wat is de kracht van verzoening? Jan De Cock trekt de wereld rond en ontmoet tientallen slachtoffers van misdaden die we soms moeilijk kunnen vatten en die toch konden ze vergeven.'
        . PHP_EOL . 'Jan De Cock ontmoet slachtoffers van misdaden die het laatste woord niet aan de feiten hebben gelaten, noch aan de wrok.'
        . PHP_EOL . 'In een wereld waar de roep naar gerechtigheid steeds vaker gehoord wordt als een schreeuw voor meer repressie en straf, biedt Jan De Cock weerwerk.'
        . PHP_EOL . 'Hij trekt de wereld rond en ontmoet tientallen slachtoffers van daden die we soms moeilijk kunnen vatten.'
        . PHP_EOL . 'Toch konden ze vergeven: ouders van wie de kinderen door de Noor Breivik werden vermoord, moeders van zonen die met de Twin Towers ten onder gingen, de weduwe van Gerrit Jan Heijn...'
        . PHP_EOL . 'Zondert twijfel een onvergetelijk avond.'
        . PHP_EOL . 'Graag doorklikken naar de website van Markant Melle Merelbeke voor alle informatie betreffende deze lezing. Iedereen welkom!';
        /* @codingStandardsIgnoreEnd */

        $formattedEvent = $formatter->formatEvent($eventWithUnwantedLineBreaks);
        $expectedFormatting = [
            'id' =>'ee7c4030-d69f-4584-b0f2-a700955c7df2',
            'description' => $expectedDescription,
        ];

        $this->assertEquals($expectedFormatting, $formattedEvent);
    }

    /**
     * @test
     * @dataProvider kansentariefEventInfoProvider
     */
    public function it_should_add_a_kansentarief_column_when_kansentarief_is_included(
        EventInfo $eventInfo,
        array $expectedFormatting
    ): void {
        $eventInfoService = $this->createMock(EventInfoServiceInterface::class);
        $eventInfoService
            ->method('getEventInfo')
            ->willReturn($eventInfo);

        $includedProperties = [
            'id',
            'kansentarief',
        ];

        $event = $this->getJSONEventFromFile('event_with_price.json');
        $formatter = new TabularDataEventFormatter($includedProperties, $eventInfoService);
        $formattedEvent = $formatter->formatEvent($event);

        $this->assertEquals($expectedFormatting, $formattedEvent);
    }

    /**
     * @test
     *
     * @group issue-III-1533
     * @group issue-III-1790
     */
    public function it_adds_base_price_and_all_other_pricing_in_when_formatting_priceInfo(): void
    {
        $includedProperties = [
            'id',
            'priceInfo',
        ];

        $event = $this->getJSONEventFromFile('event_with_price.json');
        $formatter = new TabularDataEventFormatter($includedProperties);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
            'priceInfo.base' => '10,50',
            'priceInfo.all' => 'Basistarief: 10,50 €; Senioren: 0,00 €',
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    /**
     * @test
     *
     * @group issue-III-1790
     */
    public function it_ignores_price_info_when_no_priceInfo_is_set(): void
    {
        $includedProperties = [
            'id',
            'priceInfo',
        ];

        $event = $this->getJSONEventFromFile('event_without_priceinfo.json');
        $formatter = new TabularDataEventFormatter($includedProperties);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => '405a0c6a-c48f-4c5f-960c-df337237b9d6',
            'priceInfo.base' => '',
            'priceInfo.all' => '',
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    /**
     * @test
     */
    public function it_should_include_booking_url_tel_and_email_when_booking_info_is_included(): void
    {
        $includedProperties = [
            'id',
            'bookingInfo',
        ];

        $event = $this->getJSONEventFromFile('event_with_booking_info.json');
        $formatter = new TabularDataEventFormatter($includedProperties);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => 'caacf59e-29e7-4787-9197-bf3933e86288',
            'bookingInfo.url' => 'http://www.museumpas.be/smak',
            'bookingInfo.phone' => '09987654321',
            'bookingInfo.email' => 'dirk@du.de',
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    public function kansentariefEventInfoProvider(): array
    {
        return [
            'one card system , single tariff' => [
                'eventInfo' => new EventInfo(
                    [
                        [
                            'price' => '1.5',
                            'cardSystem' => 'UiTPAS Regio Aalst',
                        ],
                    ],
                    [
                        EventAdvantage::kansenTarief(),
                    ],
                    [
                        '12 punten: Een voordeel van 12 punten.',
                    ]
                ),
                'expectedFormatting' => [
                    'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
                    'kansentarief' => 'UiTPAS Regio Aalst: € 1,5',
                ],
            ],
            'one card system , multiple tariffs' => [
                'eventInfo' => new EventInfo(
                    [
                        [
                            'price' => '1.5',
                            'cardSystem' => 'UiTPAS Regio Aalst',
                        ],
                        [
                            'price' => '5',
                            'cardSystem' => 'UiTPAS Regio Aalst',
                        ],
                    ],
                    [
                        EventAdvantage::kansenTarief(),
                    ],
                    [
                        '12 punten: Een voordeel van 12 punten.',
                    ]
                ),
                'expectedFormatting' => [
                    'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
                    'kansentarief' => 'UiTPAS Regio Aalst: € 1,5 / € 5',
                ],
            ],
            'multiple card systems , multiple tariffs' => [
                'eventInfo' => new EventInfo(
                    [
                        [
                            'price' => '1.5',
                            'cardSystem' => 'UiTPAS Regio Aalst',
                        ],
                        [
                            'price' => '5',
                            'cardSystem' => 'UiTPAS Regio Aalst',
                        ],
                        [
                            'price' => '0.50',
                            'cardSystem' => 'UiTPAS Regio Diest',
                        ],
                    ],
                    [
                        EventAdvantage::kansenTarief(),
                    ],
                    [
                        '12 punten: Een voordeel van 12 punten.',
                    ]
                ),
                'expectedFormatting' => [
                    'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
                    'kansentarief' => 'UiTPAS Regio Aalst: € 1,5 / € 5 | UiTPAS Regio Diest: € 0,5',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_include_both_a_long_and_short_summary_when_exporting_calendar_info(): void
    {
        $includedProperties = [
            'id',
            'calendarSummary',
        ];

        $calendarSummaryRepository = $this->createMock(CalendarSummaryRepositoryInterface::class);

        $calendarSummaryRepository->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function (string $eventId, ContentType $contentType, Format $format): string {
                    if ($contentType->sameAs(ContentType::plain()) && $format->sameAs(Format::md())) {
                        return 'SHORT CALENDAR SUMMARY';
                    }
                    if ($contentType->sameAs(ContentType::plain()) && $format->sameAs(Format::lg())) {
                        return 'LONG CALENDAR SUMMARY';
                    }
                    return '';
                }
            );

        $event = $this->getJSONEventFromFile('event_with_dates.json');
        $formatter = new TabularDataEventFormatter($includedProperties, null, $calendarSummaryRepository);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
            'calendarSummary.short' => 'SHORT CALENDAR SUMMARY',
            'calendarSummary.long' => 'LONG CALENDAR SUMMARY',
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    /**
     * @test
     * @dataProvider audienceTypesAndToegang
     */
    public function it_should_export_audience_type_as_toegang(string $event, string $toegang): void
    {
        $includedProperties = ['id', 'audience'];

        $formatter = new TabularDataEventFormatter($includedProperties);
        $formattedEvent = $formatter->formatEvent($event);

        $this->assertEquals($toegang, $formattedEvent['audience']);
    }

    public function audienceTypesAndToegang(): array
    {
        return [
            'voor iedereen' => [
                'offerJson' => Json::encode(
                    [
                    '@id' => '4232b0d3-5de2-483d-a693-1ff852250f5d',
                    'audience' => [
                        'audienceType' => 'everyone',
                    ],
                    ]
                ),
                'toegang' => 'Voor iedereen',
            ],
            'enkel voor leden' => [
                'offerJson' => Json::encode(
                    [
                    '@id' => '4232b0d3-5de2-483d-a693-1ff852250f5d',
                    'audience' => [
                        'audienceType' => 'members',
                    ],
                    ]
                ),
                'toegang' => 'Enkel voor leden',
            ],
            'specifiek voor scholen' => [
                'offerJson' => Json::encode(
                    [
                    '@id' => '4232b0d3-5de2-483d-a693-1ff852250f5d',
                    'audience' => [
                        'audienceType' => 'education',
                    ],
                    ]
                ),
                'toegang' => 'Specifiek voor scholen',
            ],
            'unknown audience type' => [
                'offerJson' => Json::encode(
                    [
                    '@id' => '4232b0d3-5de2-483d-a693-1ff852250f5d',
                    'audience' => [
                        'audienceType' => 'unknown',
                    ],
                    ]
                ),
                'toegang' => 'Voor iedereen',
            ],
            'no audience type' => [
                'offerJson' => Json::encode(
                    [
                    '@id' => '4232b0d3-5de2-483d-a693-1ff852250f5d',
                    ]
                ),
                'toegang' => 'Voor iedereen',
            ],
        ];
    }

    /**
     * @test
     *
     * @group issue-III-1791
     */
    public function it_formats_labels_separately_based_on_visibility(): void
    {
        $includedProperties = [
            'id',
            'labels',
        ];

        $event = $this->getJSONEventFromFile('event_with_visible_and_hidden_labels.json');
        $formatter = new TabularDataEventFormatter($includedProperties);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
            'labels.visible' => 'green;purple',
            'labels.hidden' => 'orange;red',
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    /**
     * @test
     *
     * @group issue-III-1793
     */
    public function it_should_format_image_url_description_and_copyright_when_image_is_included(): void
    {
        $includedProperties = ['id', 'image'];

        $event = $this->getJSONEventFromFile('event_with_main_image.json');
        $formatter = new TabularDataEventFormatter($includedProperties);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
            'image.url' => 'http://media.uitdatabank.be/558bb7cf-5ff8-40b4-872b-5f5b46bb16c2.jpg',
            'image.description' => 'De Kortste Nacht',
            'image.copyrightHolder' => 'Rode Ridder',
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    /**
     * @test
     */
    public function it_should_include_a_long_summary_when_exporting_with_a_calendar_repository(): void
    {
        $includedProperties = [
            'id',
            'calendarSummary',
        ];

        $smallCalendarSummary = '06/12/2013 tot 25/12/2013';
        $largeCalendarSummary = 'Van 6 december 2013 tot 25 december 2013';

        $calendarSummaryRepository = $this->createMock(CalendarSummaryRepositoryInterface::class);
        $calendarSummaryRepository
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['d1f0e71d-a9a8-4069-81fb-530134502c58', ContentType::plain(), Format::md()],
                ['d1f0e71d-a9a8-4069-81fb-530134502c58', ContentType::plain(), Format::lg()]
            )
            ->will(
                $this->onConsecutiveCalls(
                    $smallCalendarSummary,
                    $largeCalendarSummary
                )
            );

        $event = $this->getJSONEventFromFile('event_with_dates.json');
        $formatter = new TabularDataEventFormatter($includedProperties, null, $calendarSummaryRepository);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
            'calendarSummary.short' => '06/12/2013 tot 25/12/2013',
            'calendarSummary.long' => 'Van 6 december 2013 tot 25 december 2013',
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    /**
     * @test
     */
    public function it_formats_status(): void
    {
        $includedProperties = [
            'id',
            'status',
        ];

        $event = $this->getJSONEventFromFile('event_with_status.json');
        $formatter = new TabularDataEventFormatter($includedProperties);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
            'status' => 'Gaat door',
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    /**
     * @test
     */
    public function it_formats_booking_availability(): void
    {
        $includedProperties = [
            'id',
            'bookingAvailability',
        ];

        $event = $this->getJSONEventFromFile('event_with_booking_availability.json');
        $formatter = new TabularDataEventFormatter($includedProperties);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
            'bookingAvailability' => 'Volzet of uitverkocht',
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    private function encodeEvent(array $properties): string
    {
        return Json::encode(['@id' => '4232b0d3-5de2-483d-a693-1ff852250f5d'] + $properties);
    }

    /**
     * @test
     */
    public function it_formats_videos(): void
    {
        $includedProperties = [
            'id',
            'videos',
        ];

        $event = $this->getJSONEventFromFile('event_with_multiple_videos.json');
        $formatter = new TabularDataEventFormatter($includedProperties);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => '0c70b8f3-66a0-4532-959f-2e13b4624f04',
            'videos.url' => 'https://www.youtube.com/watch?v=cEItmb_a20D;https://www.youtube.com/watch?v=sXYtmb_q19C',
            'videos.copyrightHolder' => 'Copyright afgehandeld door YouTube;publiq',
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    /**
     * @test
     */
    public function it_formats_attendance(): void
    {
        $includedProperties = [
            'id',
            'attendance',
        ];

        $event = $this->getJSONEventFromFile('event_with_attendance_mode.json');
        $formatter = new TabularDataEventFormatter($includedProperties);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => '0c70b8f3-66a0-4532-959f-2e13b4624f04',
            'attendance.mode' => 'gemengd (fysiek / online)',
            'attendance.url' => 'https://www.publiq.be/livestream',
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    /**
     * @test
     */
    public function it_formats_completeness(): void
    {
        $includedProperties = [
            'id',
            'completeness',
        ];

        $event = $this->getJSONEventFromFile('event_with_completeness.json');
        $formatter = new TabularDataEventFormatter($includedProperties);
        $formattedEvent = $formatter->formatEvent($event);

        $expectedFormattedEvent = [
            'id' => 'd1f0e71d-a9a8-4069-81fb-530134502c58',
            'completeness' => 80,
        ];

        $this->assertEquals($expectedFormattedEvent, $formattedEvent);
    }

    /**
     * @test
     * @dataProvider eventsAndLeeftijd
     */
    public function it_should_export_the_age_range_or_the_birthdate_range_as_leeftijd(
        string $event,
        string $leeftijd
    ): void {
        $formatter = new TabularDataEventFormatter(['typicalAgeRange']);

        $formattedEvent = $formatter->formatEvent($event);

        $this->assertSame($leeftijd, $formattedEvent['typicalAgeRange']);
    }

    public function eventsAndLeeftijd(): array
    {
        return [
            'a typical age range' => [
                'event' => $this->encodeEvent(['typicalAgeRange' => '6-12']),
                'leeftijd' => '6-12',
            ],
            'a birthdate range' => [
                'event' => $this->encodeEvent(
                    ['birthdateRange' => ['from' => '2010-01-01', 'to' => '2010-12-31']]
                ),
                'leeftijd' => '01/01/2010 - 31/12/2010',
            ],
            'a birthdate range of a single day' => [
                'event' => $this->encodeEvent(
                    ['birthdateRange' => ['from' => '2010-01-01', 'to' => '2010-01-01']]
                ),
                'leeftijd' => '01/01/2010 - 01/01/2010',
            ],
            'a specific age range wins from a birthdate range' => [
                'event' => $this->encodeEvent(
                    [
                        'typicalAgeRange' => '6-12',
                        'birthdateRange' => ['from' => '2010-01-01', 'to' => '2010-12-31'],
                    ]
                ),
                'leeftijd' => '6-12',
            ],
            'an all ages range gives way to a birthdate range' => [
                'event' => $this->encodeEvent(
                    [
                        'typicalAgeRange' => '-',
                        'birthdateRange' => ['from' => '2010-01-01', 'to' => '2010-12-31'],
                    ]
                ),
                'leeftijd' => '01/01/2010 - 31/12/2010',
            ],
            'an all ages event without a birthdate range' => [
                'event' => $this->encodeEvent(['typicalAgeRange' => '-']),
                'leeftijd' => '-',
            ],
            'an incomplete birthdate range' => [
                'event' => $this->encodeEvent(['birthdateRange' => ['from' => '2010-01-01']]),
                'leeftijd' => '',
            ],
            'a birthdate range that is not a real date' => [
                'event' => $this->encodeEvent(
                    ['birthdateRange' => ['from' => '2010-13-45', 'to' => '2010-12-31']]
                ),
                'leeftijd' => '',
            ],
            'neither an age range nor a birthdate range' => [
                'event' => $this->encodeEvent([]),
                'leeftijd' => '',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_exports_the_birthdate_range_in_the_leeftijd_column(): void
    {
        $formatter = new TabularDataEventFormatter(['birthdateRange']);

        $this->assertSame(['id', 'leeftijd'], $formatter->formatHeader());
    }

    /**
     * @test
     */
    public function it_keeps_one_leeftijd_column_when_both_age_properties_are_included(): void
    {
        $formatter = new TabularDataEventFormatter(['typicalAgeRange', 'birthdateRange']);

        $this->assertSame(['id', 'leeftijd'], $formatter->formatHeader());
    }

    /**
     * @test
     */
    public function it_keeps_one_id_column_when_the_id_is_included_as_well(): void
    {
        $formatter = new TabularDataEventFormatter(['id', 'name']);

        $this->assertSame(['id', 'titel'], $formatter->formatHeader());
    }

    /**
     * @test
     * @dataProvider eventsAndDoelgroep
     */
    public function it_should_export_the_target_audience_as_doelgroep(
        string $event,
        string $doelgroep
    ): void {
        $formatter = new TabularDataEventFormatter(['childrenOnly']);

        $formattedEvent = $formatter->formatEvent($event);

        $this->assertSame($doelgroep, $formattedEvent['childrenOnly']);
    }

    public function eventsAndDoelgroep(): array
    {
        $childrenOnly = 'voor kinderen alleen';
        $withGuardian = 'voor kinderen samen met hun familie of een andere begeleider';

        return [
            'an event only for children' => [
                'event' => $this->encodeEvent(['childrenOnly' => true]),
                'doelgroep' => $childrenOnly,
            ],
            'an event only for children keeps saying so whatever its age range' => [
                'event' => $this->encodeEvent(['childrenOnly' => true, 'typicalAgeRange' => '18-99']),
                'doelgroep' => $childrenOnly,
            ],
            'an age range reaching below twelve' => [
                'event' => $this->encodeEvent(['typicalAgeRange' => '6-12']),
                'doelgroep' => $withGuardian,
            ],
            'an age range of the youngest children' => [
                'event' => $this->encodeEvent(['typicalAgeRange' => '0-5']),
                'doelgroep' => $withGuardian,
            ],
            'an age range without a start covers everyone from birth' => [
                'event' => $this->encodeEvent(['typicalAgeRange' => '-12']),
                'doelgroep' => $withGuardian,
            ],
            'an age range starting just below twelve' => [
                'event' => $this->encodeEvent(['typicalAgeRange' => '11-18']),
                'doelgroep' => $withGuardian,
            ],
            'an age range starting exactly at twelve is not for children' => [
                'event' => $this->encodeEvent(['typicalAgeRange' => '12-18']),
                'doelgroep' => '',
            ],
            'an age range for adults' => [
                'event' => $this->encodeEvent(['typicalAgeRange' => '18-99']),
                'doelgroep' => '',
            ],
            'an all ages event' => [
                'event' => $this->encodeEvent(['typicalAgeRange' => '-']),
                'doelgroep' => '',
            ],
            'an all ages event written as 0-' => [
                'event' => $this->encodeEvent(['typicalAgeRange' => '0-']),
                'doelgroep' => '',
            ],
            'childrenOnly false falls back to the age range' => [
                'event' => $this->encodeEvent(['childrenOnly' => false, 'typicalAgeRange' => '6-12']),
                'doelgroep' => $withGuardian,
            ],
            'an age range that is not a range' => [
                'event' => $this->encodeEvent(['typicalAgeRange' => 'zes tot twaalf']),
                'doelgroep' => '',
            ],
            'neither a flag nor an age range' => [
                'event' => $this->encodeEvent([]),
                'doelgroep' => '',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider eventsAndOvernightStay
     */
    public function it_should_export_whether_the_event_has_an_overnight_stay(
        string $event,
        string $metOvernachting
    ): void {
        $formatter = new TabularDataEventFormatter(['hasOvernightStay']);

        $formattedEvent = $formatter->formatEvent($event);

        $this->assertSame($metOvernachting, $formattedEvent['hasOvernightStay']);
    }

    public function eventsAndOvernightStay(): array
    {
        $camp = ['id' => '0.57.0.0.0', 'domain' => 'eventtype', 'label' => 'Kamp of vakantie'];
        $concert = ['id' => '0.50.4.0.0', 'domain' => 'eventtype', 'label' => 'Concert'];

        return [
            'a camp with an overnight stay' => [
                'event' => $this->encodeEvent(
                    ['terms' => [$camp], 'subEvent' => [['hasOvernightStay' => true]]]
                ),
                'metOvernachting' => 'ja',
            ],
            'a camp without an overnight stay' => [
                'event' => $this->encodeEvent(['terms' => [$camp], 'subEvent' => [[], []]]),
                'metOvernachting' => 'nee',
            ],
            'an event type that can never have an overnight stay stays empty' => [
                'event' => $this->encodeEvent(
                    ['terms' => [$concert], 'subEvent' => [['hasOvernightStay' => true]]]
                ),
                'metOvernachting' => '',
            ],
            'an event without an event type stays empty' => [
                'event' => $this->encodeEvent(['subEvent' => [[]]]),
                'metOvernachting' => '',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_reports_the_faq_column_as_wrapping(): void
    {
        $formatter = new TabularDataEventFormatter(['name', 'description', 'faqs']);

        // Column 4, because the export always prepends an id column of its own.
        $this->assertSame([4], $formatter->wrappedColumns());
    }

    /**
     * @test
     */
    public function it_reports_no_wrapping_column_when_the_faqs_are_not_included(): void
    {
        $formatter = new TabularDataEventFormatter(['name', 'description']);

        $this->assertSame([], $formatter->wrappedColumns());
    }

    /**
     * @test
     */
    public function it_reports_the_faq_column_of_a_default_export_as_wrapping(): void
    {
        $formatter = new TabularDataEventFormatter([]);

        $header = $formatter->formatHeader();

        $this->assertSame([array_search('faq', $header, true) + 1], $formatter->wrappedColumns());
    }

    /**
     * @test
     * @dataProvider eventsAndFaq
     */
    public function it_should_export_the_faqs(string $event, string $faq): void
    {
        $formatter = new TabularDataEventFormatter(['id', 'faqs']);

        $formattedEvent = $formatter->formatEvent($event);

        $this->assertSame($faq, $formattedEvent['faqs']);
    }

    public function eventsAndFaq(): array
    {
        return [
            'a single item' => [
                'event' => $this->encodeEvent(
                    ['faqs' => [['nl' => ['question' => 'Hoe geraak ik er?', 'answer' => 'Met de bus.']]]]
                ),
                'faq' => 'Hoe geraak ik er? Met de bus.',
            ],
            'one translation of every item' => [
                'event' => $this->encodeEvent(
                    [
                        'faqs' => [
                            [
                                'nl' => ['question' => 'Hoe geraak ik er?', 'answer' => 'Met de bus.'],
                                'fr' => ['question' => 'Comment venir?', 'answer' => 'En bus.'],
                            ],
                            ['nl' => ['question' => 'Wat kost het?', 'answer' => '10 euro.']],
                        ],
                    ]
                ),
                'faq' => "Hoe geraak ik er? Met de bus.\nWat kost het? 10 euro.",
            ],
            'the main language of the event, even when there is a Dutch translation' => [
                'event' => $this->encodeEvent(
                    [
                        'mainLanguage' => 'fr',
                        'faqs' => [
                            [
                                'nl' => ['question' => 'Hoe geraak ik er?', 'answer' => 'Met de bus.'],
                                'fr' => ['question' => 'Comment venir?', 'answer' => 'En bus.'],
                            ],
                        ],
                    ]
                ),
                'faq' => 'Comment venir? En bus.',
            ],
            'Dutch when the event has no main language' => [
                'event' => $this->encodeEvent(
                    [
                        'faqs' => [
                            [
                                'fr' => ['question' => 'Comment venir?', 'answer' => 'En bus.'],
                                'nl' => ['question' => 'Hoe geraak ik er?', 'answer' => 'Met de bus.'],
                            ],
                        ],
                    ]
                ),
                'faq' => 'Hoe geraak ik er? Met de bus.',
            ],
            'any language when the item has no translation in the main language' => [
                'event' => $this->encodeEvent(
                    [
                        'mainLanguage' => 'nl',
                        'faqs' => [['de' => ['question' => 'Wie komme ich dahin?', 'answer' => 'Mit dem Bus.']]],
                    ]
                ),
                'faq' => 'Wie komme ich dahin? Mit dem Bus.',
            ],
            'markup is stripped' => [
                'event' => $this->encodeEvent(
                    [
                        'faqs' => [
                            [
                                'nl' => [
                                    'question' => 'Hoe geraak ik er?',
                                    'answer' => '<p>Met de <strong>bus</strong>.</p><p>Of te voet.</p>',
                                ],
                            ],
                        ],
                    ]
                ),
                'faq' => 'Hoe geraak ik er? Met de bus. Of te voet.',
            ],
            'a semicolon in a question or an answer needs no escaping' => [
                'event' => $this->encodeEvent(
                    [
                        'faqs' => [
                            ['nl' => ['question' => 'Kost het 10 euro; of meer?', 'answer' => 'Ja; soms.']],
                            ['nl' => ['question' => 'Wanneer?', 'answer' => 'Morgen.']],
                        ],
                    ]
                ),
                'faq' => "Kost het 10 euro; of meer? Ja; soms.\nWanneer? Morgen.",
            ],
            'an answer spanning multiple lines is kept on one line' => [
                'event' => $this->encodeEvent(
                    ['faqs' => [['nl' => ['question' => 'Hoe?', 'answer' => "Met de bus.\n\n  Of te voet."]]]]
                ),
                'faq' => 'Hoe? Met de bus. Of te voet.',
            ],
            'a question or an answer that is not a string is passed over' => [
                'event' => $this->encodeEvent(
                    [
                        'faqs' => [
                            [
                                'nl' => ['question' => 'Wat kost het?', 'answer' => 10],
                                'fr' => ['question' => 'Combien?', 'answer' => '10 euros.'],
                            ],
                            ['nl' => ['question' => ['nl' => 'Hoe?'], 'answer' => 'Met de bus.']],
                            ['nl' => ['question' => 'Wanneer?', 'answer' => 'Morgen.']],
                        ],
                    ]
                ),
                'faq' => "Combien? 10 euros.\nWanneer? Morgen.",
            ],
            'an item without an answer is skipped' => [
                'event' => $this->encodeEvent(
                    [
                        'faqs' => [
                            ['nl' => ['question' => 'Hoe geraak ik er?']],
                            ['nl' => ['question' => 'Wat kost het?', 'answer' => '10 euro.']],
                        ],
                    ]
                ),
                'faq' => 'Wat kost het? 10 euro.',
            ],
            'an empty list of faqs' => [
                'event' => $this->encodeEvent(['faqs' => []]),
                'faq' => '',
            ],
            'no faqs at all' => [
                'event' => $this->encodeEvent([]),
                'faq' => '',
            ],
        ];
    }
}
