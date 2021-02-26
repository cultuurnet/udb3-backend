<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class RemoveEventFromProduction implements AuthorizableCommandInterface
{
    /**
     * @var string
     */
    private $eventId;

    /**
     * @var ProductionId
     */
    private $productionId;

    public function __construct(
        string $eventId,
        ProductionId $productionId
    ) {
        $this->eventId = $eventId;
        $this->productionId = $productionId;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getProductionId(): ProductionId
    {
        return $this->productionId;
    }

    public function getItemId()
    {
        return $this->getProductionId()->toNative();
    }

    public function getPermission()
    {
        return Permission::PRODUCTIES_AANMAKEN();
    }
}
