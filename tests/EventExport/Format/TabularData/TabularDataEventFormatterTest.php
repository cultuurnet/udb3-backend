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
use PHPUnit\Framework\TestCase;

class TabularDataEventFormatterTest extends TestCase
{
    private function getJSONEventFromFile(string $fileName): string
    {
        return file_get_contents(__DIR__ . '/../../samples/' . $fileName);
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

    public function it_formats_completeness()
    {
        $includedProperties = [
            'id',
            'status',
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
}
