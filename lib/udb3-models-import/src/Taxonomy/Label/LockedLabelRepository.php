<?php

namespace CultuurNet\UDB3\Model\Import\Taxonomy\Label;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;

interface LockedLabelRepository
{
    /**
     * @param $itemId
     * @return Labels
     */
    public function getLockedLabelsForItem($itemId);
}
