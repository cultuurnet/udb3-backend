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
        $jsonObject->production = null;

        try {
            $production = $this->productionRepository->findProductionForEventId($id);
        } catch (EntityNotFoundException $e) {
            return $document->withBody($jsonObject);
        }

        return $document->withBody($jsonObject);
    }

}
