<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class ProductionEnrichedEventRepository extends DocumentRepositoryDecorator
{
    private ProductionRepository $productionRepository;

    private IriGeneratorInterface $iriGenerator;

    public function __construct(
        DocumentRepository $repository,
        ProductionRepository $productionRepository,
        IriGeneratorInterface $iriGenerator
    ) {
        parent::__construct($repository);
        $this->productionRepository = $productionRepository;
        $this->iriGenerator = $iriGenerator;
    }

    public function fetch(string $id, bool $includeMetadata = false): JsonDocument
    {
        return $this->enrich(
            parent::fetch($id, $includeMetadata)
        );
    }

    public function save(JsonDocument $document): void
    {
        parent::save(
            $document->applyAssoc(
                function (array $json) {
                    unset($json['production']);
                    return $json;
                }
            )
        );
    }

    private function enrich(JsonDocument $document): JsonDocument
    {
        $jsonObject = $document->getBody();
        $id = $document->getId();

        try {
            $production = $this->productionRepository->findProductionForEventId($id);

            $jsonObject->production = (object) [
                'id' => $production->getProductionId()->toNative(),
                'title' => $production->getName(),
            ];

            $otherEvents = [];
            foreach ($production->getEventIds() as $eventId) {
                if ($eventId !== $id) {
                    $otherEvents[] = $this->iriGenerator->iri($eventId);
                }
            }
            $jsonObject->production->otherEvents = $otherEvents;
        } catch (EntityNotFoundException $e) {
            $jsonObject->production = null;
        }

        return $document->withBody($jsonObject);
    }
}
