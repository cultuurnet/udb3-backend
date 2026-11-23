<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\Search\CalendarSummaryService;
use RuntimeException;
use Throwable;

final class CalendarSummaryServiceRepository implements CalendarSummaryRepositoryInterface, CalendarSummaryPrefetcher
{
    /** @var array<string, true> */
    private array $prefetchedEventIds = [];

    /** @var array<string, array<string, string>> summaries by size-style and event id */
    private array $summaries = [];

    public function __construct(private readonly CalendarSummaryService $calendarSummaryService)
    {
    }

    public function prefetch(array $eventIds): void
    {
        $this->prefetchedEventIds = array_fill_keys($eventIds, true);
        $this->summaries = [];
    }

    /**
     * @throws SummaryUnavailableException
     */
    public function get(string $eventId, ContentType $type, Format $format): string
    {
        $parameters = [
            'language' => 'nl',
            'style' => $type->sameAs(ContentType::html()) ? 'html' : 'text',
            'size' => $format->toString(),
            'hidePast' => 'false',
            'timezone' => 'Europe/Brussels',
        ];

        if (!isset($this->prefetchedEventIds[$eventId])) {
            return $this->getOne($eventId, $parameters);
        }

        // Every size and style is fetched for all prefetched events when the first of them needs it.
        $key = $parameters['size'] . '-' . $parameters['style'];
        if (!isset($this->summaries[$key])) {
            // A failed fetch is not repeated for every other event of the batch.
            $this->summaries[$key] = [];
            try {
                $this->summaries[$key] = $this->calendarSummaryService->getMany(
                    OfferType::event(),
                    array_keys($this->prefetchedEventIds),
                    $parameters
                );
            } catch (RuntimeException $exception) {
                throw $this->unavailable($eventId, $exception);
            }
        }

        if (!isset($this->summaries[$key][$eventId])) {
            throw new SummaryUnavailableException('No calendar summary available for event ' . $eventId);
        }

        return $this->summaries[$key][$eventId];
    }

    /**
     * @param array<string, string> $parameters
     * @throws SummaryUnavailableException
     */
    private function getOne(string $eventId, array $parameters): string
    {
        try {
            return $this->calendarSummaryService->get(OfferType::event(), $eventId, $parameters);
        } catch (DocumentDoesNotExist|RuntimeException $exception) {
            throw $this->unavailable($eventId, $exception);
        }
    }

    private function unavailable(string $eventId, Throwable $exception): SummaryUnavailableException
    {
        return new SummaryUnavailableException(
            'No calendar summary available for event ' . $eventId . ': ' . $exception->getMessage(),
            0,
            $exception
        );
    }
}
