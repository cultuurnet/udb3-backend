<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class RemoveEventsFromProduction implements AuthorizableCommand
{
    /**
     * @var string[]
     */
    private array $eventIds;

    private ProductionId $productionId;

    /**
     * @param string[] $eventIds
     */
    public function __construct(
        array $eventIds,
        ProductionId $productionId
    ) {
        $this->eventIds = $eventIds;
        $this->productionId = $productionId;
    }

    /**
     * @return string[]
     */
    public function getEventIds(): array
    {
        return $this->eventIds;
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
