<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\DuplicatePlace\Dto;

class ClusterChangeResult
{
    private int $amountNewClusters;
    private int $amountRemovedClusters;

    public function __construct(int $amountNewClusters, int $amountRemovedClusters)
    {
        $this->amountNewClusters = $amountNewClusters;
        $this->amountRemovedClusters = $amountRemovedClusters;
    }

    public function getAmountNewClusters(): int
    {
        return $this->amountNewClusters;
    }

    public function getAmountRemovedClusters(): int
    {
        return $this->amountRemovedClusters;
    }
}
