<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Place\Canonical\CanonicalService;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use CultuurNet\UDB3\Search\Sapi3SearchService;
use Psr\Log\LoggerInterface;

final class LookupDuplicatePlaceWithSapi3 implements LookupDuplicatePlace
{
    private Sapi3SearchService $sapi3SearchService;
    private UniqueAddressIdentifierFactory $addressIdentifierFactory;
    private string $currentUserId;

    private bool $useGlobalAddressIdentifier;

    private DuplicatePlaceRepository $duplicatePlaceRepository;

    private CanonicalService $canonicalService;

    private IriGeneratorInterface $placeIriGenerator;

    private LoggerInterface $logger;

    public function __construct(
        Sapi3SearchService $sapi3SearchService,
        UniqueAddressIdentifierFactory $addressIdentifierFactory,
        string $currentUserId,
        bool $useGlobalAddressIdentifier,
        DuplicatePlaceRepository $duplicatePlaceRepository,
        CanonicalService $canonicalService,
        IriGeneratorInterface $placeIriGenerator,
        LoggerInterface $logger,
    ) {
        $this->sapi3SearchService = $sapi3SearchService;
        $this->addressIdentifierFactory = $addressIdentifierFactory;
        $this->currentUserId = $currentUserId;
        $this->useGlobalAddressIdentifier = $useGlobalAddressIdentifier;
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->canonicalService = $canonicalService;
        $this->placeIriGenerator = $placeIriGenerator;
        $this->logger = $logger;
    }

    /*
     * When there is no place returned by the query allow the creation
     * When there is one place returned by the query, disallow creation and return the found place
     * When there is more than one place found, disallow creation and use the duplicate table to find the canonical
     * When canonical lookup fails, we use the CanonicalService to try to find a suitable id and log it
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

        foreach ($resultsWithDuplicates->getItems() as $item) {
            $id = $item->getId();
            $canonicalId = $this->duplicatePlaceRepository->getCanonicalOfPlace($id);
            if ($canonicalId !== null) {
                return $this->placeIriGenerator->iri($canonicalId);
            }
        }

        $this->logger->error(
            'Problem with finding a canonical place: ',
            ['query' => $query]
        );

        $placeIds = array_map(fn ($item) => $item->getId(), $resultsWithDuplicates->getItems());

        $placeId = $this->canonicalService->getCanonicalFromArrayWithoutThrowing($placeIds);

        return $this->placeIriGenerator->iri($placeId);
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
