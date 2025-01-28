<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\Address\Parser\ParsedAddress;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\RDF\JsonDataCouldNotBeConverted;
use CultuurNet\UDB3\RDF\NodeUri\CRC32HashGenerator;
use CultuurNet\UDB3\RDF\NodeUri\NodeUriGenerator;
use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\RdfResourceFactoryWithoutBlankNodes;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SampleFiles;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EventJsonToTurtleConverterTest extends TestCase
{
    private string $eventId;
    private array $event;

    private DocumentRepository $documentRepository;

    /** @var LoggerInterface&MockObject */
    private $logger;

    private EventJsonToTurtleConverter $eventJsonToTurtleConverter;

    /**
     * @var NormalizerInterface|MockObject
     */
    private $imageNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $this->eventId,
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

        $this->documentRepository = new InMemoryDocumentRepository();

        $addressParser = $this->createMock(AddressParser::class);
        $addressParser->expects($this->any())
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
        $this->imageNormalizer = $this->createMock(NormalizerInterface::class);

        $this->eventJsonToTurtleConverter = new EventJsonToTurtleConverter(
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/events/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/places/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/organizers/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.taxonomy.uitdatabank.be/terms/' . $item),
            $this->documentRepository,
            (new EventDenormalizer())->handlesDummyOrganizers(),
            $addressParser,
            new RdfResourceFactoryWithoutBlankNodes(new NodeUriGenerator(new CRC32HashGenerator())),
            $this->imageNormalizer,
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

        $this->documentRepository->save(new JsonDocument($eventId, Json::encode($event)));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to project event d4b46fba-6433-4f86-bcb5-edeef6689fea with invalid JSON to RDF.');

        set_error_handler(
            static function ($errorNumber, $errorString) {
                restore_error_handler();
                throw new Exception($errorString, $errorNumber);
            },
            E_ALL
        );
        $this->expectException(Exception::class);

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

        $this->documentRepository->save(new JsonDocument($eventId, Json::encode($event)));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to project event d4b46fba-6433-4f86-bcb5-edeef6689fea without created date to RDF.');

        $this->expectException(JsonDataCouldNotBeConverted::class);
        $this->expectExceptionMessage('Event ' . $eventId . ' has no created date.');

        $this->eventJsonToTurtleConverter->convert($eventId);
    }

    /**
     * @test
     */
    public function it_converts_a_simple_event(): void
    {
        $this->givenThereIsAnEvent();

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_a_description(): void
    {
        $this->givenThereIsAnEvent([
            'description' => [
                'nl' => 'Dit is het laatste concert van Faith no more',
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-description.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_translations(): void
    {
        $this->givenThereIsAnEvent([
            'name' => [
                'nl' => 'Faith no more',
                'fr' => 'Foi non plus',
            ],
            'description' => [
                'nl' => 'Dit is het laatste concert van Faith no more',
                'fr' => 'Ceci est le dernier concert de Foi non plus',
                'en' => 'This is the last concert of Faith no more',
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-translations.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_a_permanent_event_with_opening_hours(): void
    {
        $this->givenThereIsAnEvent([
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
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-calendar-permanent-and-opening-hours.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_periodic_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'periodic',
            'startDate' => '2020-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/event-with-calendar-periodic.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-calendar-periodic.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_periodic_calendar_and_opening_hours(): void
    {
        $this->givenThereIsAnEvent([
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
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/event-with-calendar-periodic-and-opening-hours.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-calendar-periodic-and-opening-hours.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_single_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/event-with-calendar-single.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-calendar-single.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_multiple_calendar(): void
    {
        $this->givenThereIsAnEvent([
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
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/event-with-calendar-multiple.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-calendar-multiple.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_throws_on_multiple_calendar_with_missing_sub_events(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'multiple',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-07T23:00:00+01:00',
        ]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to project event d4b46fba-6433-4f86-bcb5-edeef6689fea with invalid JSON to RDF.');

        $this->expectException(JsonDataCouldNotBeConverted::class);
        $this->expectExceptionMessage('Multiple calendar should have at least one subEvent.');

        $this->eventJsonToTurtleConverter->convert($this->eventId);
    }

    /**
     * @test
     * @dataProvider workflowStatusDataProvider
     */
    public function it_converts_an_event_with_workflow_status(WorkflowStatus $workflowStatus, string $file): void
    {
        $this->givenThereIsAnEvent([
            'workflowStatus' => $workflowStatus->toString(),
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/' . $file), $turtle);
    }

    public function workflowStatusDataProvider(): array
    {
        return [
            'draft' => [
                'workflowStatus' => WorkflowStatus::DRAFT(),
                'file' => 'event.ttl',
            ],
            'ready for validation' => [
                'workflowStatus' => WorkflowStatus::READY_FOR_VALIDATION(),
                'file' => 'event-with-status-ready-for-validation.ttl',
            ],
            'approved' => [
                'workflowStatus' => WorkflowStatus::APPROVED(),
                'file' => 'event-with-status-approved.ttl',
            ],
            'rejected' => [
                'workflowStatus' => WorkflowStatus::REJECTED(),
                'file' => 'event-with-status-rejected.ttl',
            ],
            'deleted' => [
                'workflowStatus' => WorkflowStatus::DELETED(),
                'file' => 'event-with-status-deleted.ttl',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_publication_date(): void
    {
        $this->givenThereIsAnEvent([
            'workflowStatus' => WorkflowStatus::APPROVED()->toString(),
            'availableFrom' => '2023-04-23T12:30:15+02:00',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-publication-date.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_location(): void
    {
        $this->givenThereIsAnEvent([
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
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-dummy-location.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_logs_an_event_with_dummy_location_but_missing_address(): void
    {
        $this->givenThereIsAnEvent([
            'location' => [
                'name' => 'Het Depot',
            ],
        ]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to project event d4b46fba-6433-4f86-bcb5-edeef6689fea with invalid JSON to RDF.');

        $this->expectException(JsonDataCouldNotBeConverted::class);

        $this->eventJsonToTurtleConverter->convert($this->eventId);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_location_name(): void
    {
        $this->givenThereIsAnEvent([
            'location' => [
                'name' => [
                    'nl' => 'Het Depot',
                ],
                'address' => [
                    'nl' => [
                        'addressCountry' => 'BE',
                        'addressLocality' => 'Leuven',
                        'postalCode' => '3000',
                        'streetAddress' => 'Martelarenplein 1',
                    ],
                ],
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-dummy-location-name.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_location_null_name(): void
    {
        $this->givenThereIsAnEvent([
            'location' => [
                'name' => [
                    'nl' => null,
                ],
                'address' => [
                    'nl' => [
                        'addressCountry' => 'BE',
                        'addressLocality' => 'Leuven',
                        'postalCode' => '3000',
                        'streetAddress' => 'Martelarenplein 1',
                    ],
                ],
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-dummy-location.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_location_and_multiple_calendar(): void
    {
        $this->givenThereIsAnEvent([
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
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/event-with-dummy-location-and-multiple-calendar.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-dummy-location-and-multiple-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_location_name_and_multiple_calendar(): void
    {
        $this->givenThereIsAnEvent([
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
            'location' => [
                'name' => [
                    'nl' => 'Het Depot',
                ],
                'address' => [
                    'nl' => [
                        'addressCountry' => 'BE',
                        'addressLocality' => 'Leuven',
                        'postalCode' => '3000',
                        'streetAddress' => 'Martelarenplein 1',
                    ],
                ],
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/event-with-dummy-location-name-and-multiple-calendar.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-dummy-location-name-and-multiple-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_location_and_single_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
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
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/event-with-dummy-location-and-single-calendar.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-dummy-location-and-single-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_location_name_and_single_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'location' => [
                'name' => [
                    'nl' => 'Het Depot',
                ],
                'address' => [
                    'nl' => [
                        'addressCountry' => 'BE',
                        'addressLocality' => 'Leuven',
                        'postalCode' => '3000',
                        'streetAddress' => 'Martelarenplein 1',
                    ],
                ],
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/event-with-dummy-location-name-and-single-calendar.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-dummy-location-name-and-single-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_online_event_with_online_url_and_single_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/00000000-0000-0000-0000-000000000000',
            ],
            'attendanceMode' => 'online',
            'onlineUrl' => 'https://www.publiq.be/livestream',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/online-event-with-online-url-and-single-calendar.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/online-event-with-online-url-and-single-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_online_event_with_single_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/00000000-0000-0000-0000-000000000000',
            ],
            'attendanceMode' => 'online',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/online-event-with-single-calendar.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/online-event-with-single-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_online_event_with_online_url_and_multiple_calendar(): void
    {
        $this->givenThereIsAnEvent([
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
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/00000000-0000-0000-0000-000000000000',
            ],
            'attendanceMode' => 'online',
            'onlineUrl' => 'https://www.publiq.be/livestream',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/online-event-with-online-url-and-multiple-calendar.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/online-event-with-online-url-and-multiple-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_online_event_with_online_url_and_permanent_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/00000000-0000-0000-0000-000000000000',
            ],
            'attendanceMode' => 'online',
            'onlineUrl' => 'https://www.publiq.be/livestream',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/online-event-with-online-url-and-permanent-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_online_event_with_permanent_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/00000000-0000-0000-0000-000000000000',
            ],
            'attendanceMode' => 'online',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/online-event-with-permanent-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_mixed_event_with_permanent_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'attendanceMode' => 'mixed',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/mixed-event-with-permanent-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_mixed_event_with_single_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'attendanceMode' => 'mixed',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/mixed-event-with-single-calendar.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/mixed-event-with-single-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_mixed_event_with_online_url_and_permanent_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'attendanceMode' => 'mixed',
            'onlineUrl' => 'https://www.publiq.be/livestream',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/mixed-event-with-online-url-and-permanent-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_mixed_event_with_online_url_and_single_calendar(): void
    {
        $this->givenThereIsAnEvent([
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'attendanceMode' => 'mixed',
            'onlineUrl' => 'https://www.publiq.be/livestream',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/mixed-event-with-online-url-and-single-calendar.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/mixed-event-with-online-url-and-single-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_mixed_event_with_online_url_and_multiple_calendar(): void
    {
        $this->givenThereIsAnEvent([
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
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/place/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'attendanceMode' => 'mixed',
            'onlineUrl' => 'https://www.publiq.be/livestream',
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        file_put_contents(__DIR__ . '/ttl/mixed-event-with-online-url-and-multiple-calendar.ttl', $turtle);
        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/mixed-event-with-online-url-and-multiple-calendar.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_organizer(): void
    {
        $this->givenThereIsAnEvent([
            'organizer' => [
                '@id' => 'https://mock.io.uitdatabank.be/organizers/331a966d-d8ff-4e3c-a6f4-83b901f6c3af',
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-organizer.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_organizer(): void
    {
        $this->givenThereIsAnEvent([
            'organizer' => [
                'name' => 'Dummy Organizer',
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-dummy-organizer.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_organizer_with_translated_name(): void
    {
        $this->givenThereIsAnEvent([
            'organizer' => [
                'name' => [
                    'fr' => 'Organisateur Factice',
                    'nl' => 'Dummy Organizer',
                ],
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-dummy-organizer.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_dummy_organizer_with_contact_point(): void
    {
        $this->givenThereIsAnEvent([
            'organizer' => [
                'name' => 'Dummy Organizer',
                'phone' => [
                    '016 666 666',
                ],
                'url' => [
                    'http://www.dummy-organizer.be',
                ],
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-dummy-organizer-with-contact-point.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_contact_point(): void
    {
        $this->givenThereIsAnEvent([
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
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-contact-point.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_booking_info(): void
    {
        $this->givenThereIsAnEvent([
            'bookingInfo' => [
                'url' => 'https://www.publiq.be',
                'urlLabel' => [
                    'nl' => 'Reserveer nu',
                ],
                'email' => 'info@publiq.be',
                'phone' => '016 10 20 30',
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-booking-info.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_labels(): void
    {
        $this->givenThereIsAnEvent([
            'labels' => [
                'public_label_1',
                'public_label_2',
            ],
            'hiddenLabels' => [
                'hidden_label_1',
                'hidden_label_2',
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-labels.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_price_info(): void
    {
        $this->givenThereIsAnEvent([
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
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-price-info.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_videos(): void
    {
        $this->givenThereIsAnEvent([
            'videos' => [
                [
                    'id' => '6bab1cba-18d0-42e7-b0c9-3b869eb68934',
                    'url' => 'https://youtu.be/fn-4RbxXThE',
                    'embedUrl' => 'https://www.youtube.com/embed/fn-4RbxXThE',
                    'language' => 'nl',
                    'copyrightHolder' => 'Copyright afgehandeld door YouTube',
                ],
                [
                    'id' => '58716d9e-46c8-4145-a0b2-60381ec3bd92',
                    'url' => 'https://youtu.be/fd-5FGTh3se',
                    'embedUrl' => 'https://www.youtube.com/embed/fd-5FGTh3se',
                    'language' => 'nl',
                    'copyrightHolder' => 'Copyright afgehandeld door YouTube',
                ],
            ],
        ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-videos.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_images(): void
    {
        $url = 'https://images-acc.uitdatabank.be/6bab1cba-18d0-42e7-b0c9-3b869eb68934.jpeg';

        $this->givenThereIsAnEvent([
            'mediaObject' => [
                [
                    '@id' => 'https://io-acc.uitdatabank.be/images/6bab1cba-18d0-42e7-b0c9-3b869eb68934',
                    '@type' => 'schema:ImageObject',
                    'contentUrl' => $url,
                    'thumbnailUrl' => $url,
                    'copyrightHolder' => 'publiq vzw',
                    'description' => 'A cute dog',
                    'inLanguage' => 'nl',
                ],
            ],
        ]);

        $this->imageNormalizer->expects($this->once())
            ->method('normalize')
            ->willReturn([
                'contentUrl' => $url,
            ]);

        $turtle = $this->eventJsonToTurtleConverter->convert($this->eventId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/event-with-media-object.ttl'), $turtle);
    }

    private function givenThereIsAnEvent(array $extraProperties = []): void
    {
        $event = array_merge($this->event, $extraProperties);
        $this->documentRepository->save(new JsonDocument($this->eventId, Json::encode($event)));
    }
}
