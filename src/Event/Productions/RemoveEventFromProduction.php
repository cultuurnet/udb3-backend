<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class RemoveEventFromProduction implements AuthorizableCommand
{
    private string $eventId;

    private ProductionId $productionId;

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

    public function getItemId(): string
    {
        return $this->getProductionId()->toNative();
    }

    public function getPermission(): Permission
    {
        return Permission::productiesAanmaken();
    }
}
