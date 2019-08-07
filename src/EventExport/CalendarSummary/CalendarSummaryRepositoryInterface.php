<?php

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

interface CalendarSummaryRepositoryInterface
{
    /**
     * @param string      $offerId
     * @param ContentType $type
     * @param Format      $format
     *
     * @return string|null
     *
     * @throws SummaryUnavailableException
     */
    public function get($offerId, ContentType $type, Format $format);
}
