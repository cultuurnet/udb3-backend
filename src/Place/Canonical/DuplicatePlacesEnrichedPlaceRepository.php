<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class DuplicatePlacesEnrichedPlaceRepository extends DocumentRepositoryDecorator
{
    private DuplicatePlaceRepository $duplicatePlaceRepository;

    private IriGeneratorInterface $iriGenerator;

    public function __construct(
        DuplicatePlaceRepository $duplicatePlaceRepository,
        IriGeneratorInterface $iriGenerator,
        DocumentRepository $documentRepository
    ) {
        parent::__construct($documentRepository);

        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->iriGenerator = $iriGenerator;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        return parent::fetch($id, $includeMetadata)->applyAssoc(
            fn (array $placeLd) => $this->updateDuplicates($id, $placeLd)
        );
    }

    private function updateDuplicates(string $placeId, array $placeLd): array
    {
        $canonical = $this->duplicatePlaceRepository->getCanonicalOfPlace($placeId);
        $duplicates = $this->duplicatePlaceRepository->getDuplicatesOfPlace($placeId);

        unset($placeLd['duplicateOf'], $placeLd['duplicatedBy']);

        if ($canonical && $duplicates === null) {
            $placeLd['duplicateOf'] = $this->iriGenerator->iri($canonical);
        }

        if ($duplicates && $canonical === null) {
            foreach ($duplicates as $duplicate) {
                $placeLd['duplicatedBy'][] = $this->iriGenerator->iri($duplicate);
            }
        }

        return $placeLd;
    }
}
