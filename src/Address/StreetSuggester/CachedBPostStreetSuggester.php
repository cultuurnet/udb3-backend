<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\StreetSuggester;

final class CachedBPostStreetSuggester implements StreetSuggester
{
    private StreetSuggester $streetSuggester;

    public function __construct(StreetSuggester $streetSuggester)
    {
        $this->streetSuggester = $streetSuggester;
    }
    public function suggest(string $postalCode, string $locality, string $streetQuery): array
    {
        //TODO: Decide if we want to cache it.
        return $this->streetSuggester->suggest($postalCode, $locality, $streetQuery);
    }
}
