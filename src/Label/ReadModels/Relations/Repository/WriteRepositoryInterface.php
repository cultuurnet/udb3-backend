<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository;

use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\StringLiteral;

interface WriteRepositoryInterface
{
    public function save(
        string $labelName,
        RelationType $relationType,
        string $relationId,
        bool $imported
    ): void;

    public function deleteByLabelNameAndRelationId(
        string $labelName,
        string $relationId
    ): void;

    /**
     * This method will only delete the imported labels based on relation id.
     */
    public function deleteImportedByRelationId(StringLiteral $relationId): void;
}
