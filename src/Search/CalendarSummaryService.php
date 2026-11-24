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

    /**
     * Gets the summaries of several offers at once, always in the Europe/Brussels timezone.
     *
     * @param string[] $offerIds
     * @param array<string, string> $parameters language, style, size and hidePast
     * @return array<string, string> the summaries by offer id, without the offers that were not found
     * @throws RuntimeException when the calendar summaries could not be fetched
     */
    public function getMany(OfferType $offerType, array $offerIds, array $parameters): array;
}
