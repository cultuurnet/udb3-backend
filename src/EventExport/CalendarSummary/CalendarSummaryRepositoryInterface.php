<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

interface CalendarSummaryRepositoryInterface
{
    public function get(string $eventId, ContentType $type, Format $format): string;
}
