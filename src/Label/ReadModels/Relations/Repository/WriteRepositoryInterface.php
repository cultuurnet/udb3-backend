<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\StringLiteral;

interface WriteRepositoryInterface
{
    public function save(
        string $labelName,
        RelationType $relationType,
        StringLiteral $relationId,
        bool $imported
    ): void;

    public function deleteByLabelNameAndRelationId(
        LabelName $labelName,
        StringLiteral $relationId
    ): void;

    public function deleteByRelationId(StringLiteral $relationId): void;

    /**
     * This method will only delete the imported labels based on relation id.
     */
    public function deleteImportedByRelationId(StringLiteral $relationId): void;
}
