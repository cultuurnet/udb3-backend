<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use CultuurNet\UDB3\Search\Sapi3SearchService;

final class LookupDuplicatePlaceWithSapi3 implements LookupDuplicatePlace
{
    private Sapi3SearchService $sapi3SearchService;
    private UniqueAddressIdentifierFactory $addressIdentifierFactory;
    private string $currentUserId;

    private bool $useGlobalAddressIdentifier;

    private DuplicatePlaceRepository $duplicatePlaceRepository;

    public function __construct(
        Sapi3SearchService $sapi3SearchService,
        UniqueAddressIdentifierFactory $addressIdentifierFactory,
        string $currentUserId,
        bool $useGlobalAddressIdentifier,
        DuplicatePlaceRepository $duplicatePlaceRepository
    ) {
        $this->sapi3SearchService = $sapi3SearchService;
        $this->addressIdentifierFactory = $addressIdentifierFactory;
        $this->currentUserId = $currentUserId;
        $this->useGlobalAddressIdentifier = $useGlobalAddressIdentifier;
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
    }

    /*
    When there is only one place propose that place
    When there are multiple places propose the one with duplicatedBy
    When there are multiple places but no canonical try to find a canonical
    When all else fails, return a query
    */
    public function getDuplicatePlaceUri(Place $place): ?string
    {
        $query = $this->getQuery($place);

        $resultsWithDuplicates = $this->sapi3SearchService->search(
            $query
        );

        if ($resultsWithDuplicates->getTotalItems() === 0) {
            return null;
        }

        if ($resultsWithDuplicates->getTotalItems() === 1) {
            return $resultsWithDuplicates->getItems()[0]->getUrl()->toString();
        }

        // We have more than 1 result, lets do the call again with isDuplicate=false to see if without duplicates,
        // we only get 1 place back
        $originalQuery = $query;
        $query .= '&isDuplicate=false';

        $resultsWithoutDuplicates = $this->sapi3SearchService->search(
            $query
        );

        if ($resultsWithoutDuplicates->getTotalItems() === 1) {
            return $resultsWithoutDuplicates->getItems()[0]->getUrl()->toString();
        }

        /**
         * @see https://jira.publiq.be/browse/III-7059
         */
        if ($resultsWithoutDuplicates->getTotalItems() === 0) {
            foreach ($resultsWithDuplicates->getItems() as $item) {
                $id = $item->getId();
                $canonicalId = $this->duplicatePlaceRepository->getCanonicalOfPlace($id);
                if ($canonicalId !== null) {
                    return str_replace($id, $canonicalId, $item->getUrl()->toString());
                }
            }
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
            $this->addressIdentifierFactory->createForUser(
                $place->getTitle()->getTranslation($place->getMainLanguage())->toString(),
                $place->getAddress()->getTranslation($place->getMainLanguage()),
                $this->currentUserId
            );
    }
}
