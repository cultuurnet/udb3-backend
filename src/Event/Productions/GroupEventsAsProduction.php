<?php

declare(strict_types=1);

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

    public function getItemId()
    {
        return $this->getProductionId()->toNative();
    }

    public function getPermission()
    {
        return Permission::PRODUCTIES_AANMAKEN();
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
