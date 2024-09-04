<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

class ClustersDiffResult
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
