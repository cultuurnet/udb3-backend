<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
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

        // We have more than 1 result, lets do the call again with isDuplicate=false to see if without duplicates,
        // we only get 1 place back
        $query .= '&isDuplicate=false';

        $results = $this->sapi3SearchService->search(
            $query
        );

        if ($results->getTotalItems() === 0) {
            // This should be absolutely impossible to occur, but you never know.
            // There is no clean solution in this case, we just give a fatal error to the user
            throw ApiProblem::internalServerError('Duplicate places detected, but isDuplicate=false returns no duplicates.');
        }

        if ($results->getTotalItems() === 1) {
            return $results->getItems()[0]->getUrl()->toString();
        }

        // Add isDuplicate so the response will never contain places we identified as duplicates
        throw new MultipleDuplicatePlacesFound($query);
    }
}
