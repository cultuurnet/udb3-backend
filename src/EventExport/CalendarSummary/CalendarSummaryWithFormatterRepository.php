<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\CalendarSummaryV3\Offer\Offer;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

final class CalendarSummaryWithFormatterRepository implements CalendarSummaryRepositoryInterface
{
    private DocumentRepository $repository;

    public function __construct(DocumentRepository $repository)
    {
        $this->repository = $repository;
    }

    public function get(string $offerId, ContentType $type, Format $format): string
    {
        $offerDocument = $this->repository->fetch($offerId);
        if ($type->sameAs(ContentType::html())) {
            $calendarFormatter = new CalendarHTMLFormatter();
        } else {
            $calendarFormatter = new CalendarPlainTextFormatter();
        }
        return $calendarFormatter->format(Offer::fromJsonLd($offerDocument->getRawBody()), $format->toString());
    }
}
