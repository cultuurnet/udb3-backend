<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\JSONLD;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JSONLDEventFormatterTest extends TestCase
{
    private CalendarSummaryRepositoryInterface&MockObject $calendarSummaryRepository;

    public function setUp(): void
    {
        $this->calendarSummaryRepository = $this->createMock(CalendarSummaryRepositoryInterface::class);
        $this->calendarSummaryRepository->method('get')->willReturn('Vrijdag');
    }

    private function getJSONEventFromFile(string $fileName): string
    {
        return SampleFiles::read(__DIR__ . '/../../samples/' . $fileName);
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
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","terms":[{"label":"Geschiedenis","domain":"theme","id":"1.11.0.0.0"},{"label":"Cursus of workshop","domain":"eventtype","id":"0.3.1.0.0"}]}',
            $event
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
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58"}',
            $event
        );
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
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        /* @codingStandardsIgnoreStart */
        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","terms":[{"label":"Cursus of workshop","domain":"eventtype","id":"0.3.1.0.0"}]}',
            $event
        );
        /* @codingStandardsIgnoreEnd */
    }

    /**
     * @test
     */
    public function it_can_export_status(): void
    {
        $includedProperties = [
            'id',
            'status',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_status.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","status":{"type":"Available"}}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_can_export_booking_availability(): void
    {
        $includedProperties = [
            'id',
            'bookingAvailability',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_booking_availability.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","bookingAvailability":{"type":"Unavailable"}}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_can_export_calendar_summary(): void
    {
        $includedProperties = [
            'id',
            'calendarSummary',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_booking_availability.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"http:\/\/culudb-silex.dev:8080\/event\/d1f0e71d-a9a8-4069-81fb-530134502c58","calendarSummary":"Vrijdag"}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_can_export_videos(): void
    {
        $includedProperties = [
            'id',
            'videos',
        ];
        $eventWithTerms = $this->getJSONEventFromFile('event_with_multiple_videos.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithTerms);

        $this->assertEquals(
            '{"@id":"https:\/\/udb-silex-acc.uitdatabank.be\/event\/0c70b8f3-66a0-4532-959f-2e13b4624f04","videos":[{"id":"6d787098-3082-4a0f-a510-1df4597ae02f","url":"https:\/\/www.youtube.com\/watch?v=cEItmb_a20D","embedUrl":"https:\/\/www.youtube.com\/embed\/cEItmb_a20D","language":"nl","copyrightHolder":"Copyright afgehandeld door YouTube"},{"id":"192a07d9-049b-4c2a-bc94-e46b7a557529","url":"https:\/\/www.youtube.com\/watch?v=sXYtmb_q19C","embedUrl":"https:\/\/www.youtube.com\/embed\/sXYtmb_q19C","language":"fr","copyrightHolder":"publiq"}]}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_exports_attendance(): void
    {
        $includedProperties = [
            'id',
            'attendance',
        ];
        $eventWithAttendanceMode = $this->getJSONEventFromFile('event_with_attendance_mode.json');
        $formatter = new JSONLDEventFormatter($includedProperties, $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($eventWithAttendanceMode);

        $this->assertEquals(
            '{"@id":"https:\/\/udb-silex-acc.uitdatabank.be\/event\/0c70b8f3-66a0-4532-959f-2e13b4624f04","attendanceMode":"mixed","onlineUrl":"https:\/\/www.publiq.be\/livestream"}',
            $event
        );
    }

    /**
     * @test
     */
    public function it_describes_the_departure_places(): void
    {
        $formatter = new JSONLDEventFormatter(
            ['id', 'departurePlaces'],
            $this->calendarSummaryRepository,
            $this->givenPlaces([
                'place-1' => ['name' => 'Sportcentrum', 'postalCode' => '3000', 'locality' => 'Leuven'],
                'place-2' => ['name' => 'Jeugdhuis', 'postalCode' => '2000', 'locality' => 'Antwerpen'],
            ])
        );

        $event = $formatter->formatEvent(
            $this->encodeEvent(
                [
                    'departurePlaces' => [
                        'https://io.uitdatabank.be/places/place-1',
                        'https://io.uitdatabank.be/places/place-2',
                    ],
                ]
            )
        );

        $this->assertEquals(
            [
                '@id' => 'https://io.uitdatabank.be/events/event-1',
                'departurePlaces' => [
                    [
                        '@id' => 'https://io.uitdatabank.be/places/place-1',
                        'name' => 'Sportcentrum',
                        'postalCode' => '3000',
                        'addressLocality' => 'Leuven',
                    ],
                    [
                        '@id' => 'https://io.uitdatabank.be/places/place-2',
                        'name' => 'Jeugdhuis',
                        'postalCode' => '2000',
                        'addressLocality' => 'Antwerpen',
                    ],
                ],
            ],
            Json::decodeAssociatively($event)
        );
    }

    /**
     * @test
     */
    public function it_leaves_out_a_departure_place_that_no_longer_exists(): void
    {
        $formatter = new JSONLDEventFormatter(
            ['id', 'departurePlaces'],
            $this->calendarSummaryRepository,
            $this->givenPlaces([
                'place-2' => ['name' => 'Jeugdhuis', 'postalCode' => '2000', 'locality' => 'Antwerpen'],
            ])
        );

        $event = $formatter->formatEvent(
            $this->encodeEvent(
                [
                    'departurePlaces' => [
                        'https://io.uitdatabank.be/places/place-1',
                        'https://io.uitdatabank.be/places/place-2',
                    ],
                ]
            )
        );

        $this->assertEquals(
            [
                '@id' => 'https://io.uitdatabank.be/events/event-1',
                'departurePlaces' => [
                    [
                        '@id' => 'https://io.uitdatabank.be/places/place-2',
                        'name' => 'Jeugdhuis',
                        'postalCode' => '2000',
                        'addressLocality' => 'Antwerpen',
                    ],
                ],
            ],
            Json::decodeAssociatively($event)
        );
    }

    /**
     * @test
     */
    public function it_keeps_the_departure_place_urls_when_no_place_repository_is_available(): void
    {
        $formatter = new JSONLDEventFormatter(['id', 'departurePlaces'], $this->calendarSummaryRepository);

        $event = $formatter->formatEvent(
            $this->encodeEvent(['departurePlaces' => ['https://io.uitdatabank.be/places/place-1']])
        );

        $this->assertEquals(
            [
                '@id' => 'https://io.uitdatabank.be/events/event-1',
                'departurePlaces' => ['https://io.uitdatabank.be/places/place-1'],
            ],
            Json::decodeAssociatively($event)
        );
    }

    /**
     * @test
     * @dataProvider eventsAndOvernightStay
     */
    public function it_exports_whether_the_event_has_an_overnight_stay(
        array $eventProperties,
        array $expectedEvent
    ): void {
        $formatter = new JSONLDEventFormatter(['id', 'hasOvernightStay'], $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($this->encodeEvent($eventProperties));

        $this->assertEquals(
            ['@id' => 'https://io.uitdatabank.be/events/event-1'] + $expectedEvent,
            Json::decodeAssociatively($event)
        );
    }

    public function eventsAndOvernightStay(): array
    {
        $camp = [['id' => '0.57.0.0.0', 'label' => 'Kamp of vakantie', 'domain' => 'eventtype']];
        $concert = [['id' => '0.50.4.0.0', 'label' => 'Concert', 'domain' => 'eventtype']];

        return [
            'a camp with an overnight stay' => [
                'eventProperties' => [
                    'terms' => $camp,
                    'subEvent' => [['id' => 0, 'hasOvernightStay' => true]],
                ],
                'expectedEvent' => ['hasOvernightStay' => true],
            ],
            'a camp without an overnight stay' => [
                'eventProperties' => ['terms' => $camp, 'subEvent' => [['id' => 0]]],
                'expectedEvent' => ['hasOvernightStay' => false],
            ],
            'a concert leaves the property out' => [
                'eventProperties' => [
                    'terms' => $concert,
                    'subEvent' => [['id' => 0, 'hasOvernightStay' => true]],
                ],
                'expectedEvent' => [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider passedThroughProperties
     */
    public function it_passes_through_the_audience_properties_unchanged(string $property, mixed $value): void
    {
        $formatter = new JSONLDEventFormatter(['id', $property], $this->calendarSummaryRepository);

        $event = $formatter->formatEvent($this->encodeEvent([$property => $value]));

        $this->assertEquals(
            ['@id' => 'https://io.uitdatabank.be/events/event-1', $property => $value],
            Json::decodeAssociatively($event)
        );
    }

    public function passedThroughProperties(): array
    {
        return [
            'typicalAgeRange' => ['property' => 'typicalAgeRange', 'value' => '6-12'],
            'birthdateRange' => [
                'property' => 'birthdateRange',
                'value' => ['from' => '2010-01-01', 'to' => '2010-12-31'],
            ],
            'childrenOnly' => ['property' => 'childrenOnly', 'value' => true],
            'faqs' => [
                'property' => 'faqs',
                'value' => [['nl' => ['question' => 'Hoe geraak ik er?', 'answer' => 'Met de bus.']]],
            ],
        ];
    }

    private function givenPlaces(array $places): InMemoryDocumentRepository
    {
        $placeRepository = new InMemoryDocumentRepository();

        foreach ($places as $placeId => $place) {
            $placeRepository->save(
                new JsonDocument(
                    $placeId,
                    Json::encode(
                        [
                            '@id' => $placeId,
                            'name' => ['nl' => $place['name']],
                            'address' => [
                                'nl' => [
                                    'postalCode' => $place['postalCode'],
                                    'addressLocality' => $place['locality'],
                                ],
                            ],
                        ]
                    )
                )
            );
        }

        return $placeRepository;
    }

    private function encodeEvent(array $properties): string
    {
        return Json::encode(['@id' => 'https://io.uitdatabank.be/events/event-1'] + $properties);
    }
}
