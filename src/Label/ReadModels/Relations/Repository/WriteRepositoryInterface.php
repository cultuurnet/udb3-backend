<?php

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use ValueObjects\StringLiteral\StringLiteral;

interface WriteRepositoryInterface
{
    /**
     * @param LabelName $labelName
     * @param RelationType $relationType
     * @param StringLiteral $relationId
     * @param bool $imported
     * @return void
     */
    public function save(
        LabelName $labelName,
        RelationType $relationType,
        StringLiteral $relationId,
        $imported
    );

    /**
     * @param LabelName $labelName
     * @param StringLiteral $relationId
     */
    public function deleteByLabelNameAndRelationId(
        LabelName $labelName,
        StringLiteral $relationId
    );

    /**
     * @param StringLiteral $relationId
     */
    public function deleteByRelationId(StringLiteral $relationId);

    /**
     * This method will only delete the imported labels based on relation id.
     *
     * @param StringLiteral $relationId
     */
    public function deleteImportedByRelationId(StringLiteral $relationId);
}
