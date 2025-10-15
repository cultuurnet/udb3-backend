<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Search\Sapi3SearchService;

final class LookupDuplicatePlaceWithSapi3 implements LookupDuplicatePlace
{
    private Sapi3SearchService $sapi3SearchService;
    private UniqueAddressIdentifierFactory $addressIdentifierFactory;
    private string $currentUserId;

    private bool $useGlobalAddressIdentifier;

    public function __construct(
        Sapi3SearchService $sapi3SearchService,
        UniqueAddressIdentifierFactory $addressIdentifierFactory,
        string $currentUserId,
        bool $useGlobalAddressIdentifier
    ) {
        $this->sapi3SearchService = $sapi3SearchService;
        $this->addressIdentifierFactory = $addressIdentifierFactory;
        $this->currentUserId = $currentUserId;
        $this->useGlobalAddressIdentifier = $useGlobalAddressIdentifier;
    }

    /*
    When there is only one place propose that place
    When there are multiple places propose the one with duplicatedBy
    When there are multiple but no canonical returns the search query to get all places
    */
    public function getDuplicatePlaceUri(Place $place): ?string
    {
        $query = $this->getQuery($place);

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
        $originalQuery = $query;
        $query .= '&isDuplicate=false';

        $results = $this->sapi3SearchService->search(
            $query
        );

        if ($results->getTotalItems() === 0) {
            throw new MultipleDuplicatePlacesFound($originalQuery);
        }

        if ($results->getTotalItems() === 1) {
            return $results->getItems()[0]->getUrl()->toString();
        }

        // Add isDuplicate so the response will never contain places we identified as duplicates
        throw new MultipleDuplicatePlacesFound($query);
    }

    private function getQuery(Place $place): string
    {
        if ($this->useGlobalAddressIdentifier) {
            return '(workflowStatus:DRAFT OR workflowStatus:READY_FOR_VALIDATION OR workflowStatus:APPROVED) AND global_address_identifier:' .
                $this->addressIdentifierFactory->create(
                    $place->getTitle()->getTranslation($place->getMainLanguage())->toString(),
                    $place->getAddress()->getTranslation($place->getMainLanguage())
                );
        }

        return '(workflowStatus:DRAFT OR workflowStatus:READY_FOR_VALIDATION OR workflowStatus:APPROVED) AND unique_address_identifier:' .
            $this->addressIdentifierFactory->legacyCreate(
                $place->getTitle()->getTranslation($place->getMainLanguage())->toString(),
                $place->getAddress()->getTranslation($place->getMainLanguage()),
                $this->currentUserId
            );
    }
}
