<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\DuplicatePlace\Dto;

class ClusterChangeResult
{
    private int $percentageNewClusters;
    private int $percentageClustersToRemove;

    public function __construct(int $percentageNewClusters, int $percentageClustersToRemove)
    {
        $this->percentageNewClusters = $percentageNewClusters;
        $this->percentageClustersToRemove = $percentageClustersToRemove;
    }

    public function getPercentageNewClusters(): int
    {
        return $this->percentageNewClusters;
    }

    public function getPercentageClustersToRemove(): int
    {
        return $this->percentageClustersToRemove;
    }

    public static function fromArray(array $array): self
    {
        return new self(
            (int)round((float)$array['percentage_not_in_duplicate']),
            (int)round((float)$array['percentage_not_in_import'])
        );
    }
}
