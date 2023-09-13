<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use CultuurNet\UDB3\Address\ParsedAddress;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\RdfTestCase;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class RdfProjectorTest extends RdfTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->rdfProjector = new RdfProjector(
            $this->graphRepository,
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/events/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/places/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.taxonomy.uitdatabank.be/terms/' . $item),
            $this->documentRepository,
            new EventDenormalizer(),
            $this->addressParser,
            $this->logger
        );

        $this->addressParser->expects($this->any())
            ->method('parse')
            ->willReturn(
                new ParsedAddress(
                    'Martelarenlaan',
                    '1',
                    '3000',
                    'Leuven'
                )
            );
    }

    /**
     * @test
     */
    public function it_logs_invalid_json(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to project event d4b46fba-6433-4f86-bcb5-edeef6689fea with invalid JSON to RDF.');

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );
    }

    /**
     * @test
     */
    public function it_converts_a_simple_event(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_a_description(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'description' => [
                'nl' => 'Dit is het laatste concert van Faith no more',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-description.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_translations(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
                'fr' => 'Foi non plus',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'description' => [
                'nl' => 'Dit is het laatste concert van Faith no more',
                'fr' => 'Ceci est le dernier concert de Foi non plus',
                'en' => 'This is the last concert of Faith no more',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-translations.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_a_permanent_event_with_opening_hours(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'opens' => '20:00',
                    'closes' => '23:00',
                    'dayOfWeek' => [
                        'monday',
                        'tuesday',
                    ],
                ],
                [
                    'opens' => '19:00',
                    'closes' => '22:00',
                    'dayOfWeek' => [
                        'wednesday',
                    ],
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-calendar-permanent-and-opening-hours.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_periodic_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'periodic',
            'startDate' => '2020-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-calendar-periodic.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_periodic_calendar_and_opening_hours(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'periodic',
            'startDate' => '2020-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'openingHours' => [
                [
                    'opens' => '20:00',
                    'closes' => '23:00',
                    'dayOfWeek' => [
                        'monday',
                        'tuesday',
                    ],
                ],
                [
                    'opens' => '19:00',
                    'closes' => '22:00',
                    'dayOfWeek' => [
                        'wednesday',
                        'thursday',
                    ],
                ],
                [
                    'opens' => '10:00',
                    'closes' => '12:30',
                    'dayOfWeek' => [
                        'saturday',
                        'sunday',
                    ],
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-calendar-periodic-and-opening-hours.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_single_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-calendar-single.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_multiple_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'multiple',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-07T23:00:00+01:00',
            'subEvent' => [
                [
                    'startDate' => '2023-05-06T20:00:00+01:00',
                    'endDate' => '2023-05-06T23:00:00+01:00',
                ],
                [
                    'startDate' => '2023-05-07T20:00:00+01:00',
                    'endDate' => '2023-05-07T23:00:00+01:00',
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-calendar-multiple.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_status_approved(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'workflowStatus' => WorkflowStatus::APPROVED()->toString(),
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-status-approved.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_status_deleted(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'workflowStatus' => WorkflowStatus::DELETED()->toString(),
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-status-deleted.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_status_ready_for_validation(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'workflowStatus' => WorkflowStatus::READY_FOR_VALIDATION()->toString(),
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-status-ready-for-validation.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_status_rejected(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'workflowStatus' => WorkflowStatus::REJECTED()->toString(),
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-status-rejected.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_publication_date(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'workflowStatus' => WorkflowStatus::APPROVED()->toString(),
            'availableFrom' => '2023-04-23T12:30:15+02:00',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-publication-date.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_location(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                'address' => [
                    'nl' => [
                        'addressCountry' => 'BE',
                        'addressLocality' => 'Leuven',
                        'postalCode' => '3000',
                        'streetAddress' => 'Martelarenplein 1',
                    ],
                ],
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-dummy-location.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_location_and_multiple_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'multiple',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-07T23:00:00+01:00',
            'subEvent' => [
                [
                    'startDate' => '2023-05-06T20:00:00+01:00',
                    'endDate' => '2023-05-06T23:00:00+01:00',
                ],
                [
                    'startDate' => '2023-05-07T20:00:00+01:00',
                    'endDate' => '2023-05-07T23:00:00+01:00',
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                'address' => [
                    'nl' => [
                        'addressCountry' => 'BE',
                        'addressLocality' => 'Leuven',
                        'postalCode' => '3000',
                        'streetAddress' => 'Martelarenplein 1',
                    ],
                ],
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-dummy-location-and-multiple-calendar.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_location_and_single_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                'address' => [
                    'nl' => [
                        'addressCountry' => 'BE',
                        'addressLocality' => 'Leuven',
                        'postalCode' => '3000',
                        'streetAddress' => 'Martelarenplein 1',
                    ],
                ],
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-dummy-location-and-single-calendar.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_online_event_with_online_url_and_single_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/00000000-0000-0000-0000-000000000000',
            ],
            'attendanceMode' => 'online',
            'onlineUrl' => 'https://www.publiq.be/livestream',
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/online-event-with-online-url-and-single-calendar.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_online_event_with_single_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/00000000-0000-0000-0000-000000000000',
            ],
            'attendanceMode' => 'online',
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/online-event-with-single-calendar.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_online_event_with_online_url_and_multiple_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-07T23:00:00+01:00',
            'subEvent' => [
                [
                    'startDate' => '2023-05-06T20:00:00+01:00',
                    'endDate' => '2023-05-06T23:00:00+01:00',
                ],
                [
                    'startDate' => '2023-05-07T20:00:00+01:00',
                    'endDate' => '2023-05-07T23:00:00+01:00',
                ],
            ],
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/00000000-0000-0000-0000-000000000000',
            ],
            'attendanceMode' => 'online',
            'onlineUrl' => 'https://www.publiq.be/livestream',
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/online-event-with-online-url-and-multiple-calendar.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_online_event_with_online_url_and_permanent_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/00000000-0000-0000-0000-000000000000',
            ],
            'attendanceMode' => 'online',
            'onlineUrl' => 'https://www.publiq.be/livestream',
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/online-event-with-online-url-and-permanent-calendar.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_online_event_with_permanent_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '1.8.3.1.0',
                    'domain' => 'theme',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/00000000-0000-0000-0000-000000000000',
            ],
            'attendanceMode' => 'online',
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/online-event-with-permanent-calendar.ttl'));
    }

    public function getRdfDataSetName(): string
    {
        return 'events';
    }
}
