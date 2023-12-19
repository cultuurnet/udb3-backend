<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Search\SearchServiceInterface;

class LookupDuplicatePlaceWithSapi3 implements LookupDuplicatePlace
{
    private SearchServiceInterface $sapi3SearchService;
    private string $currentUserId;

    public function __construct(
        SearchServiceInterface $sapi3SearchService,
        string $currentUserId
    ) {
        $this->sapi3SearchService = $sapi3SearchService;
        $this->currentUserId = $currentUserId;
    }

    public function getDuplicatePlaceId(Place $place): ?string
    {
        $parts = $this->getParts($place, $this->currentUserId);

        $parts = array_map(fn ($part) => str_replace(' ', '_', trim($part)), $parts);
        $uniqueAddressIdentifier = mb_strtolower(implode('_', array_filter($parts)));

        $results = $this->sapi3SearchService->search('unique_address_identifier:' . $uniqueAddressIdentifier, 1);
        return $results->getTotalItems() >= 1 ? $results->getItems()[0]->getId() : null;
    }

    private function getParts(Place $place, string $currentUserId): array
    {
        $address = $place->getAddress()->getTranslation($place->getMainLanguage());

        return [
            $place->getTitle()->getTranslation($place->getMainLanguage())->toString(),
            $address->getStreet()->toString(),
            $address->getPostalCode()->toString(),
            $address->getLocality()->toString(),
            $address->getCountryCode()->toString(),
            $currentUserId,
        ];
    }
}
