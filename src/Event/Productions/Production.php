<?php

namespace CultuurNet\UDB3\Event\Productions;

use InvalidArgumentException;

final class Production
{
    /**
     * @var ProductionId
     */
    private $productionId;

    /**
     * @var string
     */
    private $name;

    public function __construct(
        ProductionId $productionId,
        string $name
    ) {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('Production name cannot be empty');
        }

        $this->productionId = $productionId;
        $this->name = trim($name);
    }

    public static function create(string $name): self
    {
        return new self(
            ProductionId::generate(),
            $name
        );
    }

    public function getProductionId(): ProductionId
    {
        return $this->productionId;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
