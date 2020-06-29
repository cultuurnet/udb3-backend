<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryDecorator;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;

class ProductionEnrichedEventRepository extends DocumentRepositoryDecorator
{
    /**
     * @var ProductionRepository
     */
    private $productionRepository;

    public function __construct(
        DocumentRepositoryInterface $repository,
        ProductionRepository $productionRepository
    ) {
        parent::__construct($repository);
        $this->productionRepository = $productionRepository;
    }

    public function get($id)
    {
        $document = parent::get($id);
        $jsonObject = $document->getBody();

        try {
            $production = $this->productionRepository->findProductionForEventId($id);

            $jsonObject->production = (object) [
                'id' => $production->getProductionId()->toNative(),
                'title' => $production->getName()
            ];

            $jsonObject->production->otherEvents = array_values(array_filter(
                $production->getEventIds(),
                function (string $eventId) use ($id) {
                    return $eventId != $id;
                }
            ));

        } catch (EntityNotFoundException $e) {
            $jsonObject->production = null;
        }

        return $document->withBody($jsonObject);
    }

}
