<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\DuplicatePlace\Dto;

class ClusterChangeResult
{
    private int $amountNewClusters;
    private int $amountRemovedClusters;

    public function __construct(int $percentageNewClusters, int $percentageClustersToRemove)
    {
        $this->amountNewClusters = $percentageNewClusters;
        $this->amountRemovedClusters = $percentageClustersToRemove;
    }

    public function getAmountNewClusters(): int
    {
        return $this->amountNewClusters;
    }

    public function getAmountRemovedClusters(): int
    {
        return $this->amountRemovedClusters;
    }

    public static function fromArray(array $array): self
    {
        return new self(
            (int)round((float)$array['not_in_duplicate']),
            (int)round((float)$array['not_in_import'])
        );
    }
}
