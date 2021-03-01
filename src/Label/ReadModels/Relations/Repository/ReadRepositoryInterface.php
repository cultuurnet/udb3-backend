<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use ValueObjects\StringLiteral\StringLiteral;

interface ReadRepositoryInterface
{
    /**
     * @return \Generator|LabelRelation[]
     */
    public function getLabelRelations(LabelName $labelName);

    /**
     * @return LabelRelation[]
     */
    public function getLabelRelationsForItem(StringLiteral $relationId);
}
