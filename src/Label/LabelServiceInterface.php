<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

interface LabelServiceInterface
{
    /**
     * It is intentional to have two parameters to describe the label.
     * Using CultuurNet\UDB3\Label as input would have caused an namespace issue.
     *
     *
     * @return null|UUID UUID of the newly created aggregate label, or null if no new label
     * UUID of the newly created aggregate label, or null if no new label
     * aggregate was created.
     */
    public function createLabelAggregateIfNew(LegacyLabelName $labelName, bool $visible): ?UUID;
}
