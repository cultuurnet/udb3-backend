<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\CardSystem;

use CultuurNet\UDB3\UiTPAS\ValueObject\Id;

final class CardSystem
{
    private Id $id;

    private string $name;

    private bool $enabled;

    /**
     * @var DistributionKey[]
     */
    private array $distributionKeys = [];

    public function __construct(
        Id $id,
        string $name,
        bool $enabled = true
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->enabled = $enabled;
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

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return DistributionKey[]
     */
    public function getDistributionKeys(): array
    {
        return $this->distributionKeys;
    }
}
