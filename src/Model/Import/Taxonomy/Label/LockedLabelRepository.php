<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Taxonomy\Label;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;

interface LockedLabelRepository
{
    public function getLockedLabelsForItem(string $itemId): Labels;

    public function getUnlockedLabelsForItem(string $itemId): Labels;
}
