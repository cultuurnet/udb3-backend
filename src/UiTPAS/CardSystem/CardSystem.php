<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\CardSystem;

use CultuurNet\UDB3\UiTPAS\ValueObject\Id;

final class CardSystem
{
    private Id $id;

    private string $name;

    /**
     * @var DistributionKey[]
     */
    private array $distributionKeys = [];

    public function __construct(
        Id $id,
        string $name
    ) {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @param DistributionKey[] $distributionKeys
     */
    public function withDistributionKeys(array $distributionKeys): self
    {
        $clone = clone $this;
        $clone->distributionKeys = $distributionKeys;
        return $clone;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return DistributionKey[]
     */
    public function getDistributionKeys(): array
    {
        return $this->distributionKeys;
    }
}
