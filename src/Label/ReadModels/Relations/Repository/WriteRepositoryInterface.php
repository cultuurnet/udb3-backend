<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository;

use CultuurNet\UDB3\Label\ValueObjects\RelationType;

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

    public function deleteImportedByRelationId(string $relationId): void;
}
