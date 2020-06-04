<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;

class ProductionCommandHandler extends Udb3CommandHandler
{
    /**
     * @var ProductionRepository
     */
    private $productionRepository;

    public function __construct(ProductionRepository $productionRepository)
    {
        $this->productionRepository = $productionRepository;
    }

    public function handleGroupEventsAsProduction(GroupEventsAsProduction $groupEventsAsProduction): void
    {
        $this->productionRepository->add($groupEventsAsProduction->getProduction());
    }
}
