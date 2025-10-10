<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\StreetSuggester;

interface StreetSuggester
{
    /**
     * @return string[]
     */
    public function suggest(
        string $postalCode,
        string $locality,
        string $streetQuery,
        int $limit = 5
    ): array;
}
