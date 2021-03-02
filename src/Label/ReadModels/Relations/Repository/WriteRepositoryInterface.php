<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use ValueObjects\StringLiteral\StringLiteral;

interface WriteRepositoryInterface
{
    /**
     * @param bool $imported
     * @return void
     */
    public function save(
        LabelName $labelName,
        RelationType $relationType,
        StringLiteral $relationId,
        $imported
    );


    public function deleteByLabelNameAndRelationId(
        LabelName $labelName,
        StringLiteral $relationId
    );


    public function deleteByRelationId(StringLiteral $relationId);

    /**
     * This method will only delete the imported labels based on relation id.
     *
     */
    public function deleteImportedByRelationId(StringLiteral $relationId);
}
