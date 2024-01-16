<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Search\Sapi3SearchService;

class LookupDuplicatePlaceWithSapi3 implements LookupDuplicatePlace
{
    private Sapi3SearchService $sapi3SearchService;
    private UniqueAddressIdentifierFactory $addressIdentifierFactory;
    private DocumentRepository $placeRepository;
    private string $currentUserId;

    public function __construct(
        Sapi3SearchService $sapi3SearchService,
        UniqueAddressIdentifierFactory $addressIdentifierFactory,
        DocumentRepository $placeRepository,
        string $currentUserId
    ) {
        $this->sapi3SearchService = $sapi3SearchService;
        $this->currentUserId = $currentUserId;
        $this->addressIdentifierFactory = $addressIdentifierFactory;
        $this->placeRepository = $placeRepository;
    }

    /*
    When there is only one place propose that place
    When there are multiple places propose the one with duplicatedBy
    When there are multiple but no canonical returns the search query to get all places
    */
    public function getDuplicatePlaceUri(Place $place): ?string
    {
        $results = $this->sapi3SearchService->search(
            'unique_address_identifier:' . $this->addressIdentifierFactory->hash(
                $place->getTitle()->getTranslation($place->getMainLanguage())->toString(),
                $place->getAddress()->getTranslation($place->getMainLanguage()),
                $this->currentUserId
            ),
            1
        );

        if ($results->getTotalItems() === 0) {
            return null;
        }

        if ($results->getTotalItems() === 1) {
            return $results->getItems()[0]->getUrl()->toString();
        }

        foreach ($results->getItems() as $item) {
            try {
                $placeAsJson = $this->placeRepository->fetch($item->getId())->getAssocBody();

                if (!empty($placeAsJson['duplicatedBy'])) {
                    return $placeAsJson['duplicatedBy'];
                }
            } catch (DocumentDoesNotExist $e) {
            }
        }

        if ($this->sapi3SearchService->getLastRequestedUri() === null) {
            // This should never happen, but probably not worth it to crash the Place API request for it
            return $results->getItems()[0]->getUrl()->toString();
        }

        return $this->sapi3SearchService->getLastRequestedUri()->__toString();
    }
}
