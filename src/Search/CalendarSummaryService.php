<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use RuntimeException;

interface CalendarSummaryService
{
    /**
     * @param array<string, string> $parameters language, style, size, hidePast and timezone
     * @throws DocumentDoesNotExist when the offer has no calendar summary
     * @throws RuntimeException when the calendar summary could not be fetched
     */
    public function get(OfferType $offerType, string $offerId, array $parameters): string;
}
