<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\CalendarSummaryV3\Offer\Offer;

final class CalendarSummaryRepository implements CalendarSummaryRepositoryInterface
{
    public function get(string $offer, ContentType $type, Format $format): string
    {
        if ($type->sameAs(ContentType::html())){
            $calendarFormatter = new CalendarHTMLFormatter();
        } else{
            $calendarFormatter = new CalendarPlainTextFormatter();
        }
        return $calendarFormatter->format(Offer::fromJsonLd($offer), $format->toString());
    }
}