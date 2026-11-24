<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

interface CalendarSummaryPrefetcher
{
    /**
     * Announces the events that will be exported next, so their summaries can be fetched together.
     *
     * @param string[] $eventIds
     */
    public function prefetch(array $eventIds): void;
}
