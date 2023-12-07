<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Address\ParsedAddress;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EventJsonToTurtleConverterTest extends TestCase
{
    private DocumentRepository $documentRepository;

    /** @var AddressParser&MockObject */
    private $addressParser;

    /** @var LoggerInterface&MockObject */
    private $logger;

    private EventJsonToTurtleConverter $eventJsonToTurtleConverter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentRepository = new InMemoryDocumentRepository();

        $this->addressParser = $this->createMock(AddressParser::class);
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

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->eventJsonToTurtleConverter = new EventJsonToTurtleConverter(
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/events/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/places/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/organizers/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.taxonomy.uitdatabank.be/terms/' . $item),
            $this->documentRepository,
            (new EventDenormalizer())->handlesDummyOrganizers(),
            $this->addressParser,
            $this->logger
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

        $this->expectError();
        $this->expectErrorMessage('Undefined index: name');

        $this->eventJsonToTurtleConverter->convert($eventId);
    }

    /**
     * @test
     */
    public function it_logs_missing_created(): void
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to project event d4b46fba-6433-4f86-bcb5-edeef6689fea without created date to RDF.');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event ' . $eventId . ' has no created date.');

        $this->eventJsonToTurtleConverter->convert($eventId);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-description.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-translations.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-calendar-permanent-and-opening-hours.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-calendar-periodic.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-calendar-periodic-and-opening-hours.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-calendar-single.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-calendar-multiple.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-status-approved.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-status-deleted.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-status-ready-for-validation.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-status-rejected.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-publication-date.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-dummy-location.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-dummy-location-and-multiple-calendar.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-dummy-location-and-single-calendar.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/online-event-with-online-url-and-single-calendar.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/online-event-with-single-calendar.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/online-event-with-online-url-and-multiple-calendar.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/online-event-with-online-url-and-permanent-calendar.ttl'), $turtle);
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/online-event-with-permanent-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_mixed_event_with_permanent_calendar(): void
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
                '@id' => 'https://mock.io.uitdatabank.be/place/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'attendanceMode' => 'mixed',
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/mixed-event-with-permanent-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_mixed_event_with_single_calendar(): void
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
                '@id' => 'https://mock.io.uitdatabank.be/place/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'attendanceMode' => 'mixed',
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/mixed-event-with-single-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_mixed_event_with_online_url_and_permanent_calendar(): void
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
                '@id' => 'https://mock.io.uitdatabank.be/place/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'attendanceMode' => 'mixed',
            'onlineUrl' => 'https://www.publiq.be/livestream',
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/mixed-event-with-online-url-and-permanent-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_mixed_event_with_online_url_and_single_calendar(): void
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
                '@id' => 'https://mock.io.uitdatabank.be/place/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'attendanceMode' => 'mixed',
            'onlineUrl' => 'https://www.publiq.be/livestream',
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/mixed-event-with-online-url-and-single-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_mixed_event_with_online_url_and_multiple_calendar(): void
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
                '@id' => 'https://mock.io.uitdatabank.be/place/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'attendanceMode' => 'mixed',
            'onlineUrl' => 'https://www.publiq.be/livestream',
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/mixed-event-with-online-url-and-multiple-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_organizer(): void
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
            'organizer' => [
                '@id' => 'https://mock.io.uitdatabank.be/organizers/331a966d-d8ff-4e3c-a6f4-83b901f6c3af',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-organizer.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_organizer(): void
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
            'organizer' => [
                'name' => 'Dummy Organizer',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-dummy-organizer.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_organizer_with_translated_name(): void
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
            'organizer' => [
                'name' => [
                    'fr' => 'Organisateur Factice',
                    'nl' => 'Dummy Organizer',
                ],
            ],
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-dummy-organizer.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_organizer_with_contact_point(): void
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
                'name' => 'Dummy Location',
            ],
            'organizer' => [
                'name' => 'Dummy Organizer',
                'phone' => [
                    '016 666 666',
                ],
                'url' => [
                    'http://www.dummy-organizer.be',
                ],
            ],
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-dummy-organizer-with-contact-point.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_contact_point(): void
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
            'contactPoint' => [
                'url' => [
                    'https://www.publiq.be',
                    'https://www.cultuurnet.be',
                ],
                'email' => [
                    'info@publiq.be',
                    'info@cultuurnet.be',
                ],
                'phone' => [
                    '016 10 20 30',
                    '016 10 20 40',
                ],
            ],
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-contact-point.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_booking_info(): void
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
            'bookingInfo' => [
                'url' => 'https://www.publiq.be',
                'urlLabel' => [
                    'nl' => 'Reserveer nu',
                ],
                'email' => 'info@publiq.be',
                'phone' => '016 10 20 30',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-booking-info.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_labels(): void
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
            'labels' => [
                'public_label_1',
                'public_label_2',
            ],
            'hiddenLabels' => [
                'hidden_label_1',
                'hidden_label_2',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-labels.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_price_info(): void
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
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                        'fr' => 'Tarif de base',
                        'en' => 'Base tariff',
                        'de' => 'Basisrate',
                    ],
                    'price' => 59.99,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Reductie',
                    ],
                    'price' => 40,
                    'priceCurrency' => 'EUR',
                ],
            ],
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $turtle = $this->eventJsonToTurtleConverter->convert($eventId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/event-with-price-info.ttl'), $turtle);
    }
}
