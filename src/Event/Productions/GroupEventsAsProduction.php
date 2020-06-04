<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

class GroupEventsAsProduction implements AuthorizableCommandInterface
{
    /**
     * @var Production
     */
    private $production;

    public function __construct(array $eventIds, string $name)
    {
        $this->production = Production::createEmpty($name)->addEvents($eventIds);
    }

    public function getItemId()
    {
        return $this->production->getProductionId()->toNative();
    }

    public function getPermission()
    {
        Permission::PRODUCTIES_AANMAKEN();
    }

    public function getProduction(): Production
    {
        return $this->production;
    }
}
