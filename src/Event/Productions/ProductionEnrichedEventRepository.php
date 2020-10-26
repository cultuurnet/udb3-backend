<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class ProductionEnrichedEventRepository extends DocumentRepositoryDecorator
{
    /**
     * @var ProductionRepository
     */
    private $productionRepository;

    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    public function __construct(
        DocumentRepositoryInterface $repository,
        ProductionRepository $productionRepository,
        IriGeneratorInterface $iriGenerator
    ) {
        parent::__construct($repository);
        $this->productionRepository = $productionRepository;
        $this->iriGenerator = $iriGenerator;
    }

    public function get(string $id, bool $includeMetadata = false): ?JsonDocument
    {
        $document = parent::get($id);

        if (is_null($document)) {
            return null;
        }

        $jsonObject = $document->getBody();

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
