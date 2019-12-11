<?php

namespace CultuurNet\UDB3\EventExport;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventExport\Exception\MaximumNumberOfExportItemsExceeded;
use CultuurNet\UDB3\EventExport\Notification\NotificationMailerInterface;
use CultuurNet\UDB3\Event\EventNotFoundException;
use CultuurNet\UDB3\Event\EventServiceInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Generator;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ValueObjects\Web\EmailAddress;

class EventExportService implements EventExportServiceInterface
{
    /**
     * @var EventServiceInterface
     */
    protected $eventService;

    /**
     * @var SearchServiceInterface
     */
    protected $searchService;

    /**
     * @var UuidGeneratorInterface
     */
    protected $uuidGenerator;

    /**
     * Publicly accessible directory where exports will be stored.
     *
     * @var string
     */
    protected $publicDirectory;

    /**
     * @var NotificationMailerInterface
     */
    protected $mailer;

    /**
     * @var IriGeneratorInterface
     */
    protected $iriGenerator;

    /**
     * @var ResultsGeneratorInterface
     */
    protected $resultsGenerator;

    /**
     * @var int
     */
    private $maxAmountOfItems;

    public function __construct(
        EventServiceInterface $eventService,
        SearchServiceInterface $searchService,
        UuidGeneratorInterface $uuidGenerator,
        string $publicDirectory,
        IriGeneratorInterface $iriGenerator,
        NotificationMailerInterface $mailer,
        ResultsGeneratorInterface $resultsGenerator,
        int $maxAmountOfItems
    ) {
        $this->eventService = $eventService;
        $this->searchService = $searchService;
        $this->uuidGenerator = $uuidGenerator;
        $this->publicDirectory = $publicDirectory;
        $this->iriGenerator = $iriGenerator;
        $this->mailer = $mailer;
        $this->resultsGenerator = $resultsGenerator;
        $this->maxAmountOfItems = $maxAmountOfItems;
    }

    public function exportEvents(
        FileFormatInterface $fileFormat,
        EventExportQuery $query,
        EmailAddress $address = null,
        LoggerInterface $logger = null,
        ?array $selection = null
    ) {
        if (!$logger instanceof LoggerInterface) {
            $logger = new NullLogger();
        }

        if (is_array($selection) && !empty($selection)) {
            $totalItemCount = count($selection);

            if ($totalItemCount > $this->maxAmountOfItems) {
                $logger->error(
                    'not_exported',
                    [
                        'query' => (string) $query,
                        'error' => "total amount of items ({$totalItemCount}) exceeded {$this->maxAmountOfItems}",
                    ]
                );

                throw new MaximumNumberOfExportItemsExceeded();
            }

            $events = $this->getEventsAsJSONLD($selection, $logger);
        } else {
            // do a pre query to test if the query is valid and check the item count
            try {
                $preQueryResult = $this->searchService->search(
                    (string) $query,
                    1,
                    0
                );
                $totalItemCount = $preQueryResult->getTotalItems()->toNative();
            } catch (ClientErrorResponseException $e) {
                $logger->error(
                    'not_exported',
                    array(
                        'query' => (string) $query,
                        'error' => $e->getMessage(),
                        'exception_class' => get_class($e),
                    )
                );

                throw $e;
            }

            if ($totalItemCount > $this->maxAmountOfItems) {
                $logger->error(
                    'not_exported',
                    [
                        'query' => (string) $query,
                        'error' => "total amount of items ({$totalItemCount}) exceeded {$this->maxAmountOfItems}",
                    ]
                );

                throw new MaximumNumberOfExportItemsExceeded();
            }

            $logger->debug(
                'total items: {totalItems}',
                [
                    'totalItems' => $totalItemCount,
                    'query' => (string) $query,
                ]
            );

            if ($totalItemCount < 1) {
                $logger->error(
                    'not_exported',
                    [
                        'query' => (string) $query,
                        'error' => "query did not return any results",
                    ]
                );

                return false;
            }

            $events = $this->search($query, $logger);
        }

        try {
            $tmpDir = sys_get_temp_dir();
            $tmpFileName = $this->uuidGenerator->generate();
            $tmpPath = "{$tmpDir}/{$tmpFileName}";

            $fileWriter = $fileFormat->getWriter();
            $fileWriter->write($tmpPath, $events);

            $finalPath = $this->getFinalFilePath($fileFormat, $tmpPath);

            $moved = copy($tmpPath, $finalPath);
            unlink($tmpPath);

            if (!$moved) {
                throw new \RuntimeException(
                    'Unable to move export file to public directory ' . $this->publicDirectory
                );
            }

            $finalUrl = $this->iriGenerator->iri(
                basename($finalPath)
            );

            $logger->info(
                'job_info',
                [
                    'location' => $finalUrl,
                ]
            );

            if ($address) {
                $this->notifyByMail($address, $finalUrl);
            }

            return $finalUrl;
        } catch (\Exception $e) {
            if (isset($tmpPath) && $tmpPath && file_exists($tmpPath)) {
                unlink($tmpPath);
            }

            throw $e;
        }
    }

    private function getEventsAsJSONLD(array $eventIris, LoggerInterface $logger): Generator
    {
        foreach ($eventIris as $eventIri) {
            $event = $this->getEventAsJSONLD($eventIri, $logger);

            if ($event) {
                yield $eventIri => $event;
            }
        }
    }

    private function getEventAsJSONLD(string $iri, LoggerInterface $logger): ?string
    {
        try {
            /* @var string|null $event */
            $event = $this->eventService->getEvent($iri);
        } catch (EventNotFoundException $e) {
            $logger->error(
                $e->getMessage(),
                [
                    'eventId' => $iri,
                    'exception' => $e,
                ]
            );

            $event = null;
        }

        return $event;
    }

    private function search(EventExportQuery $query, LoggerInterface $logger): Generator
    {
        $events = $this->resultsGenerator->search((string) $query);

        $count = 0;
        foreach ($events as $eventIdentifier) {
            $event = $this->getEventAsJSONLD((string) $eventIdentifier->getIri(), $logger);

            if ($event) {
                $count++;
                yield $eventIdentifier->getId() => $event;
            }
        }

        $logger->debug("yielded ${count} actual events.");
    }

    private function getFinalFilePath(
        FileFormatInterface $fileFormat,
        string $tmpPath
    ): string {
        $fileUniqueId = basename($tmpPath);
        $extension = $fileFormat->getFileNameExtension();
        $finalFileName = $fileUniqueId . '.' . $extension;
        return $this->publicDirectory . '/' . $finalFileName;
    }

    /**
     * @param EmailAddress $address
     * @param string $url
     */
    private function notifyByMail(EmailAddress $address, $url): void
    {
        $this->mailer->sendNotificationMail(
            $address,
            new EventExportResult($url)
        );
    }
}
