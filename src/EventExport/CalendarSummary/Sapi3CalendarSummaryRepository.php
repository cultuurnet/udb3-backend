<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\Search\CalendarSummaryService;
use RuntimeException;

final readonly class Sapi3CalendarSummaryRepository implements CalendarSummaryRepositoryInterface
{
    public function __construct(private CalendarSummaryService $calendarSummaryService)
    {
    }

    /**
     * @throws SummaryUnavailableException
     */
    public function get(string $eventId, ContentType $type, Format $format): string
    {
        try {
            return $this->calendarSummaryService->get(
                OfferType::event(),
                $eventId,
                [
                    'language' => 'nl',
                    'style' => $type->sameAs(ContentType::html()) ? 'html' : 'text',
                    'size' => $format->toString(),
                    'hidePast' => 'false',
                    'timezone' => 'Europe/Brussels',
                ]
            );
        } catch (DocumentDoesNotExist|RuntimeException $exception) {
            throw new SummaryUnavailableException(
                'No calendar summary available for event ' . $eventId . ': ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }
}
