<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

class RenameProduction implements AuthorizableCommand
{
    private ProductionId $productionId;

    private string $name;

    public function __construct(ProductionId $productionId, string $name)
    {
        $this->productionId = $productionId;
        $this->name = $name;
    }

    public function getProductionId(): ProductionId
    {
        return $this->productionId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getItemId(): string
    {
        return $this->productionId->toNative();
    }

    public function getPermission(): Permission
    {
        return Permission::productiesAanmaken();
    }
}
