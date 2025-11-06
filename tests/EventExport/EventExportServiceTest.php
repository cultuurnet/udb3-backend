<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventExport\Exception\MaximumNumberOfExportItemsExceeded;
use CultuurNet\UDB3\EventExport\Notification\NotificationMailerInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifierFactory;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifiers;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Results;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Traversable;

final class EventExportServiceTest extends TestCase
{
    public const AMOUNT = 19;

    private EventExportService $eventExportService;

    private DocumentRepository&MockObject $eventRepository;

    private ItemIdentifierFactory $itemIdentifierFactory;

    private SearchServiceInterface&MockObject $searchService;

    private UuidGeneratorInterface&MockObject $uuidGenerator;

    private IriGeneratorInterface&MockObject $iriGenerator;

    private NotificationMailerInterface&MockObject $mailer;

    private vfsStreamDirectory $publicDirectory;

    private array $searchResults;

    private array $searchResultsDetails;

    private ResultsGeneratorInterface&MockObject $resultsGenerator;

    public function setUp(): void
    {
        $this->publicDirectory = vfsStream::setup('exampleDir');
        $this->eventRepository = $this->createMock(DocumentRepository::class);
        $this->itemIdentifierFactory = new ItemIdentifierFactory(
            'http://example\.com/(?<itemType>[event]+)/(?<itemId>[0-9]+)'
        );
        $this->searchService = $this->createMock(SearchServiceInterface::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);
        $this->mailer = $this->createMock(NotificationMailerInterface::class);
        $this->resultsGenerator = $this->createMock(ResultsGeneratorInterface::class);

        $this->eventExportService = new EventExportService(
            $this->eventRepository,
            $this->itemIdentifierFactory,
            $this->searchService,
            $this->uuidGenerator,
            $this->publicDirectory->url(),
            $this->iriGenerator,
            $this->mailer,
            $this->resultsGenerator,
            self::AMOUNT
        );

        $range = range(1, self::AMOUNT);
        $this->searchResults = array_map(
            function ($i) {
                return new ItemIdentifier(
                    new Url('http://example.com/event/' . $i),
                    (string) $i,
                    ItemType::event()
                );
            },
            $range
        );

        $this->searchResultsDetails = array_map(
            function (ItemIdentifier $item) {
                return [
                    '@id' => $item->getUrl()->toString(),
                    '@type' => $item->getItemType()->toString(),
                    'foo' => 'bar',
                ];
            },
            $this->searchResults
        );
        $this->searchResultsDetails = array_combine(
            $range,
            $this->searchResultsDetails
        );

        $this->searchService
            ->expects($this->any())
            ->method('search')
            ->withConsecutive(
                [$this->anything(), 1, 0],
                [$this->anything(), 10, 0],
                [$this->anything(), 10, 10]
            )
            ->willReturnOnConsecutiveCalls(
                new Results(
                    new ItemIdentifiers(
                        ...array_slice($this->searchResults, 0, 1)
                    ),
                    self::AMOUNT
                ),
                new Results(
                    new ItemIdentifiers(
                        ...array_slice($this->searchResults, 0, 10)
                    ),
                    self::AMOUNT
                ),
                new Results(
                    new ItemIdentifiers(
                        ...array_slice($this->searchResults, 10)
                    ),
                    self::AMOUNT
                )
            );
    }

    private function setUpEventService(array $unavailableEventIds = []): void
    {
        $this->eventRepository->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(
                function ($eventId) use ($unavailableEventIds) {
                    if (in_array($eventId, $unavailableEventIds)) {
                        throw new DocumentDoesNotExist(
                            "Event with cdbid $eventId could not be found via Entry API."
                        );
                    }

                    return new JsonDocument(
                        $eventId,
                        Json::encode([
                            '@id' => 'http://example.com/event/' . $eventId,
                            '@type' => 'event',
                            'foo' => 'bar',
                        ])
                    );
                }
            );

        $this->resultsGenerator->expects($this->any())
            ->method('search')
            ->willReturnCallback(
                function (): Traversable {
                    yield from $this->searchResults;
                }
            );
    }

    /**
     * @return FileFormatInterface&MockObject
     */
    private function getFileFormat(string $fileNameExtension)
    {
        /** @var FileFormatInterface&MockObject $fileFormat */
        $fileFormat = $this->createMock(FileFormatInterface::class);

        $fileFormat->expects($this->any())
            ->method('getFileNameExtension')
            ->willReturn($fileNameExtension);

        $fileWriter = $this->createMock(FileWriterInterface::class);
        $fileFormat->expects($this->any())
            ->method('getWriter')
            ->willReturn(
                $fileWriter
            );

        $fileWriter->expects($this->once())
            ->method('write')
            ->willReturnCallback(
                function ($tmpPath, Traversable $events): void {
                    $contents = iterator_to_array($events);
                    $contents = array_map(function ($content) {
                        return Json::decode($content);
                    }, $contents);
                    $contents = Json::encode($contents);
                    file_put_contents($tmpPath, $contents);
                }
            );

        return $fileFormat;
    }

    private function forceUuidGeneratorToReturn(string $uuid): void
    {
        $this->uuidGenerator->expects($this->any())
            ->method('generate')
            ->willReturn($uuid);
    }

    /**
     * @test
     */
    public function it_exports_events_to_a_file(): void
    {
        $this->setUpEventService();

        $exportUuid = 'abc';
        $this->forceUuidGeneratorToReturn($exportUuid);

        $exportExtension = 'txt';

        $fileFormat = $this->getFileFormat($exportExtension);

        $expectedExportFileName = 'abc.txt';

        $query = new EventExportQuery('city:Leuven');
        $logger = $this->createMock(LoggerInterface::class);

        $this->mailer->expects($this->never())
            ->method('sendNotificationMail');

        $this->eventExportService->exportEvents(
            $fileFormat,
            $query,
            null,
            $logger
        );

        $this->assertTrue(
            $this->publicDirectory->hasChild($expectedExportFileName)
        );

        /** @var vfsStreamFile $file */
        $file = $this->publicDirectory->getChild($expectedExportFileName);

        $this->assertJsonStringEqualsJsonString(
            Json::encode($this->searchResultsDetails),
            $file->getContent()
        );
    }

    /**
     * @test
     */
    public function it_logs_the_url_of_the_exported_file(): void
    {
        $this->setUpEventService();

        $exportUuid = 'abc';
        $this->forceUuidGeneratorToReturn($exportUuid);

        $exportExtension = 'txt';

        $fileFormat = $this->getFileFormat($exportExtension);

        $expectedExportFileName = 'abc.txt';

        $query = new EventExportQuery('city:Leuven');
        $logger = $this->createMock(LoggerInterface::class);

        $exportIriBase = 'http://example.com/export/';

        $createIri = function ($item) use ($exportIriBase) {
            return $exportIriBase . $item;
        };

        $expectedExportUrl = $createIri($expectedExportFileName);

        $this->iriGenerator->expects($this->once())
            ->method('iri')
            ->with($expectedExportFileName)
            ->willReturnCallback($createIri);

        $logger->expects($this->once())
            ->method('info')
            ->with(
                'job_info',
                [
                    'location' => $expectedExportUrl,
                ]
            );

        $this->eventExportService->exportEvents(
            $fileFormat,
            $query,
            null,
            $logger
        );
    }

    /**
     * @test
     */
    public function it_sends_an_email_with_a_link_to_the_export_if_address_is_provided(): void
    {
        $this->setUpEventService();

        $exportUuid = 'abc';
        $this->forceUuidGeneratorToReturn($exportUuid);

        $exportExtension = 'txt';

        $fileFormat = $this->getFileFormat($exportExtension);

        $expectedExportFileName = 'abc.txt';

        $query = new EventExportQuery('city:Leuven');

        $to = new EmailAddress('foo@example.com');

        $exportIriBase = 'http://example.com/export/';

        $createIri = function ($item) use ($exportIriBase) {
            return $exportIriBase . $item;
        };

        $this->iriGenerator->expects($this->once())
            ->method('iri')
            ->with($expectedExportFileName)
            ->willReturnCallback($createIri);

        $expectedUrl = $createIri($expectedExportFileName);

        $this->mailer->expects($this->once())
            ->method('sendNotificationMail')
            ->with(
                $to,
                new EventExportResult(
                    $expectedUrl
                )
            );

        $this->eventExportService->exportEvents(
            $fileFormat,
            $query,
            $to
        );
    }

    private function searchResultsWithout(array $results, array $without): array
    {
        $newResults = [];
        foreach ($results as $offerId => $result) {
            if (in_array($offerId, $without)) {
                continue;
            }

            $newResults[$offerId] = $result;
        }

        return $newResults;
    }

    /**
     * @test
     */
    public function it_ignores_items_that_can_not_be_found_by_the_event_service(): void
    {
        $unavailableEventIds = [3, 6, 17];

        $expectedDetails = $this->searchResultsWithout(
            $this->searchResultsDetails,
            $unavailableEventIds
        );

        foreach ($unavailableEventIds as $unavailableEventId) {
            $this->assertNotContains($unavailableEventId, $expectedDetails);
        }

        $this->setUpEventService($unavailableEventIds);

        $query = new EventExportQuery('city:Leuven');

        $exportUuid = 'abc';
        $this->forceUuidGeneratorToReturn($exportUuid);

        $exportExtension = 'txt';
        $fileFormat = $this->getFileFormat($exportExtension);

        $expectedExportFileName = 'abc.txt';

        $this->eventExportService->exportEvents(
            $fileFormat,
            $query
        );

        /**
         * @var vfsStreamFile $file
         */
        $file = $this->publicDirectory->getChild($expectedExportFileName);

        $this->assertJsonStringEqualsJsonString(
            Json::encode($expectedDetails),
            $file->getContent()
        );
    }

    /**
     * @test
     * @dataProvider exportParametersDataProvider
     */
    public function it_logs_items_that_can_not_be_found_by_the_event_service(
        FileFormatInterface $fileFormat,
        EventExportQuery $query,
        array $selection
    ): void {
        $unavailableEventIds = [4, 7, 16];

        $this->setUpEventService($unavailableEventIds);

        $exportUuid = 'abc';
        $this->forceUuidGeneratorToReturn($exportUuid);

        $logger = $this->createMock(LoggerInterface::class);
        $expectedLogContextCallback = function ($context) {
            return $context['exception'] instanceof DocumentDoesNotExist;
        };

        $logger
            ->expects($this->exactly(3))
            ->method('error')
            ->withConsecutive(
                [
                    $this->equalTo('Event with cdbid 4 could not be found via Entry API.'),
                    $this->callback($expectedLogContextCallback),
                ],
                [
                    $this->equalTo('Event with cdbid 7 could not be found via Entry API.'),
                    $this->callback($expectedLogContextCallback),
                ],
                [
                    $this->equalTo('Event with cdbid 16 could not be found via Entry API.'),
                    $this->callback($expectedLogContextCallback),
                ]
            );

        $this->eventExportService->exportEvents(
            $fileFormat,
            $query,
            null,
            $logger,
            $selection
        );
    }

    /**
     * @test
     */
    public function it_throws_exception_if_number_of_items_for_query_is_greater_than_allowed(): void
    {
        $this->expectException(MaximumNumberOfExportItemsExceeded::class);

        $this->eventExportService = new EventExportService(
            $this->eventRepository,
            $this->itemIdentifierFactory,
            $this->searchService,
            $this->uuidGenerator,
            $this->publicDirectory->url(),
            $this->iriGenerator,
            $this->mailer,
            $this->resultsGenerator,
            self::AMOUNT - 1
        );

        $this->eventExportService->exportEvents(
            $this->createMock(FileFormatInterface::class),
            new EventExportQuery('city:Leuven'),
            null,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * @test
     */
    public function it_throws_exception_if_number_of_selection_is_greater_than_allowed(): void
    {
        $query = new EventExportQuery('city:Leuven');
        $logger = $this->createMock(LoggerInterface::class);

        /** @var FileFormatInterface&MockObject $fileFormat */
        $fileFormat = $this->createMock(FileFormatInterface::class);

        $selection = [];

        for ($i = 0; $i < self::AMOUNT + 1; $i++) {
            $selection[] = 'dummyItem';
        }
        $this->expectException(MaximumNumberOfExportItemsExceeded::class);
        $this->eventExportService->exportEvents(
            $fileFormat,
            $query,
            null,
            $logger,
            $selection
        );
    }

    public function exportParametersDataProvider(): array
    {
        return [
            [
                'fileFormat' => $this->getFileFormat('txt'),
                'query' => new EventExportQuery('city:Leuven'),
                'selection' => [
                    'http://example.com/event/4',
                    'http://example.com/event/7',
                    'http://example.com/event/16',
                ],
            ],
        ];
    }
}
