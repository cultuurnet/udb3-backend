<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Search\Sapi3SearchService;

class LookupDuplicatePlaceWithSapi3 implements LookupDuplicatePlace
{
    private Sapi3SearchService $sapi3SearchService;
    private UniqueAddressIdentifierFactory $addressIdentifierFactory;
    private string $currentUserId;

    public function __construct(
        Sapi3SearchService $sapi3SearchService,
        UniqueAddressIdentifierFactory $addressIdentifierFactory,
        string $currentUserId
    ) {
        $this->sapi3SearchService = $sapi3SearchService;
        $this->addressIdentifierFactory = $addressIdentifierFactory;
        $this->currentUserId = $currentUserId;
    }

    /*
    When there is only one place propose that place
    When there are multiple places propose the one with duplicatedBy
    When there are multiple but no canonical returns the search query to get all places
    */
    public function getDuplicatePlaceUri(Place $place): ?string
    {
        $query = 'unique_address_identifier:' .
            $this->addressIdentifierFactory->create(
                $place->getTitle()->getTranslation($place->getMainLanguage())->toString(),
                $place->getAddress()->getTranslation($place->getMainLanguage()),
                $this->currentUserId
            );

        $results = $this->sapi3SearchService->search(
            $query
        );

        if ($results->getTotalItems() === 0) {
            return null;
        }

        if ($results->getTotalItems() === 1) {
            return $results->getItems()[0]->getUrl()->toString();
        }

        throw new MultipleDuplicatePlacesFound($query);
    }
}
