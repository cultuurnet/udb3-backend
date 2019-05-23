<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport;

use ArrayIterator;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventExport\Notification\NotificationMailerInterface;
use CultuurNet\UDB3\Event\EventNotFoundException;
use CultuurNet\UDB3\Event\EventServiceInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Search\Results;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Traversable;
use ValueObjects\Number\Integer;
use ValueObjects\Web\EmailAddress;
use ValueObjects\Web\Url;

class EventExportServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var EventExportService
     */
    protected $eventExportService;

    /**
     * @var EventServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventService;

    /**
     * @var SearchServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchService;

    /**
     * @var UuidGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $uuidGenerator;

    /**
     * @var IriGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $iriGenerator;

    /**
     * @var NotificationMailerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailer;

    /**
     * @var vfsStreamDirectory
     */
    protected $publicDirectory;

    /**
     * @var array
     */
    protected $searchResults;

    /**
     * @var IriOfferIdentifier[]
     */
    private $offerIdentifiers;

    /**
     * @var array
     */
    protected $searchResultsDetails;

    /**
     * @var ResultsGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultsGenerator;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->publicDirectory = vfsStream::setup('exampleDir');
        $this->eventService = $this->createMock(EventServiceInterface::class);

        $this->searchService = $this->createMock(SearchServiceInterface::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);
        $this->mailer = $this->createMock(NotificationMailerInterface::class);
        $this->resultsGenerator = $this->createMock(ResultsGeneratorInterface::class);

        $this->eventExportService = new EventExportService(
            $this->eventService,
            $this->searchService,
            $this->uuidGenerator,
            $this->publicDirectory->url(),
            $this->iriGenerator,
            $this->mailer,
            $this->resultsGenerator
        );

        $amount = 19;
        $range = range(1, $amount);
        $this->searchResults = array_map(
            function ($i) {
                return new IriOfferIdentifier(
                    Url::fromNative('http://example.com/event/' . $i),
                    (string) $i,
                    OfferType::EVENT()
                );
            },
            $range
        );

        $this->searchResultsDetails = array_map(
            function (\JsonSerializable $item) {
                return $item->jsonSerialize() + ['foo' => 'bar'];
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
                    OfferIdentifierCollection::fromArray(
                        array_slice($this->searchResults, 0, 1)
                    ),
                    new Integer($amount)
                ),
                new Results(
                    OfferIdentifierCollection::fromArray(
                        array_slice($this->searchResults, 0, 10)
                    ),
                    new Integer($amount)
                ),
                new Results(
                    OfferIdentifierCollection::fromArray(
                        array_slice($this->searchResults, 10)
                    ),
                    new Integer($amount)
                )
            );
    }

    /**
     * @param array $unavailableEventIds
     */
    private function setUpEventService(array $unavailableEventIds = [])
    {
        $this->eventService->expects($this->any())
            ->method('getEvent')
            ->willReturnCallback(
                function ($eventId) use ($unavailableEventIds) {
                    if (in_array($eventId, $unavailableEventIds)) {
                        throw new EventNotFoundException(
                            "Event with cdbid {$eventId} could not be found via Entry API."
                        );
                    }

                    return [
                        '@id' => $eventId,
                        '@type' => 'Event',
                        'foo' => 'bar',
                    ];
                }
            );

        $offerIdentifiers = array_filter(
            $this->searchResults,
            function ($offerIdentifier) use ($unavailableEventIds) {
                return !in_array($offerIdentifier->getId(), $unavailableEventIds);
            }
        );

        $this->resultsGenerator->expects($this->any())
            ->method('search')
            ->willReturn(new ArrayIterator($this->searchResults));
    }

    /**
     * @param string $fileNameExtension
     *
     * @return FileFormatInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getFileFormat($fileNameExtension)
    {
        /** @var FileFormatInterface|\PHPUnit_Framework_MockObject_MockObject $fileFormat */
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
                function ($tmpPath, Traversable $events) {
                    $contents = json_encode(iterator_to_array($events));
                    file_put_contents($tmpPath, $contents);
                }
            );

        return $fileFormat;
    }

    private function forceUuidGeneratorToReturn($uuid)
    {
        $this->uuidGenerator->expects($this->any())
            ->method('generate')
            ->willReturn($uuid);
    }

    /**
     * @test
     */
    public function it_exports_events_to_a_file()
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
            json_encode($this->searchResultsDetails),
            $file->getContent()
        );
    }

    /**
     * @test
     */
    public function it_logs_the_url_of_the_exported_file()
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
    public function it_sends_an_email_with_a_link_to_the_export_if_address_is_provided()
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

    /**
     * @param array $results
     * @param array $without
     * @return array
     */
    private function searchResultsWithout($results, $without)
    {
        $newResults = [];
        foreach ($results as $offerId => $result) {
            if (in_array($result['@id'], $without)) {
                continue;
            }

            $newResults[$offerId] = $result;
        }

        return $newResults;
    }

    /**
     * @test
     */
    public function it_ignores_items_that_can_not_be_found_by_the_event_service()
    {
        $unavailableEventIds = [
            'http://example.com/event/3',
            'http://example.com/event/6',
            'http://example.com/event/17'
        ];
        $expectedDetails = $this->searchResultsWithout(
            $this->searchResultsDetails,
            $unavailableEventIds
        );

        foreach ($unavailableEventIds as $unavailableEventId) {
            $this->assertArrayNotHasKey($unavailableEventId, $expectedDetails);
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

        /** @var vfsStreamFile $file */
        $file = $this->publicDirectory->getChild($expectedExportFileName);

        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedDetails),
            $file->getContent()
        );
    }

    /**
     * @test
     * @dataProvider exportParametersDataProvider
     */
    public function it_logs_items_that_can_not_be_found_by_the_event_service(
        $fileFormat,
        $query,
        $selection
    ) {
        $unavailableEventIds = [
            'http://example.com/event/4',
            'http://example.com/event/7',
            'http://example.com/event/16'
        ];

        $this->setUpEventService($unavailableEventIds);

        $exportUuid = 'abc';
        $this->forceUuidGeneratorToReturn($exportUuid);

        $logger = $this->createMock(LoggerInterface::class);
        $expectedLogContextCallback = function ($context) {
            return $context['exception'] instanceof EventNotFoundException;
        };

        $logger
            ->expects($this->exactly(3))
            ->method('error')
            ->withConsecutive(
                [
                    $this->equalTo('Event with cdbid http://example.com/event/4 could not be found via Entry API.'),
                    $this->callback($expectedLogContextCallback),
                ],
                [
                    $this->equalTo('Event with cdbid http://example.com/event/7 could not be found via Entry API.'),
                    $this->callback($expectedLogContextCallback),
                ],
                [
                    $this->equalTo('Event with cdbid http://example.com/event/16 could not be found via Entry API.'),
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

    public function exportParametersDataProvider()
    {
        return [
            [
                "fileFormat" => $this->getFileFormat('txt'),
                "query" => new EventExportQuery('city:Leuven'),
                "selection" => [
                    'http://example.com/event/4',
                    'http://example.com/event/7',
                    'http://example.com/event/16'
                ]
            ],
            [
                "fileFormat" => $this->getFileFormat('txt'),
                "query" => new EventExportQuery('city:Leuven'),
                "selection" => null
            ]
        ];
    }
}
