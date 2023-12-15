<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Search\SearchServiceInterface;

class LookupDuplicatePlace
{
    private bool $isDuplicate;
    private ?string $placeId;

    public function __construct(SearchServiceInterface $sapi3SearchService, Place $place, Address $address)
    {
        //we trim both sides of each part, and remove the empty parts
        $hash = mb_strtolower(implode('_', array_filter(array_map('trim', $this->getParts($place, $address)))));

        $results = $sapi3SearchService->search('unique_address_identifier:' . $hash, 1);

        $this->isDuplicate = $results->getTotalItems() >= 1;
        $this->placeId = $this->isDuplicate ? $results->getItems()[0]->getId() : null;
    }

    public function isDuplicate(): bool
    {
        return $this->isDuplicate;
    }

    public function getPlaceId(): ?string
    {
        return $this->placeId;
    }

    private function getParts(Place $place, Address $address): array
    {
        return [
            $place->getTitle()->getTranslation($place->getMainLanguage())->toString(),
            $address->getStreetAddress()->toString(),
            $address->getPostalCode()->toString(),
            $address->getLocality()->toString(),
            $address->getCountryCode()->toString(),
            //@todo add creator
        ];
    }
}
