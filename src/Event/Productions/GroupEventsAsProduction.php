<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

class GroupEventsAsProduction implements AuthorizableCommandInterface
{
    /**
     * @var string[]
     */
    private $eventIds;

    /**
     * @var string
     */
    private $name;

    /**
     * @var ProductionId
     */
    private $productionId;

    public function __construct(array $eventIds, string $name)
    {
        $this->eventIds = $eventIds;
        $this->name = $name;
        $this->productionId = ProductionId::generate();
    }

    public function getItemId()
    {
        return $this->getProductionId()->toNative();
    }

    public function getPermission()
    {
        Permission::PRODUCTIES_AANMAKEN();
    }

    /**
     * @return string[]
     */
    public function getEventIds(): array
    {
        return $this->eventIds;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProductionId(): ProductionId
    {
        return $this->productionId;
    }
}
