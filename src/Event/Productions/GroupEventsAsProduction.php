<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

class GroupEventsAsProduction implements AuthorizableCommand
{
    /**
     * @var string[]
     */
    private array $eventIds;

    private string $name;

    private ProductionId $productionId;

    public function __construct(ProductionId $productionId, array $eventIds, string $name)
    {
        $this->eventIds = $eventIds;
        $this->name = $name;
        $this->productionId = $productionId;
    }

    public static function withProductionName(array $eventIds, string $name): self
    {
        return new self(
            ProductionId::generate(),
            $eventIds,
            $name
        );
    }

    public function getItemId(): string
    {
        return $this->getProductionId()->toNative();
    }

    public function getPermission(): Permission
    {
        return Permission::productiesAanmaken();
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
