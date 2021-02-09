<?php

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

interface CalendarSummaryRepositoryInterface
{
    public function get(string $offerId, ContentType $type, Format $format): string;
}
