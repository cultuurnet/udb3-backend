<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository;

use CultuurNet\UDB3\Label\ValueObjects\RelationType;

interface ReadRepositoryInterface
{
    /**
     * @return \Generator|LabelRelation[]
     */
    public function getLabelRelations(string $labelName);

    /**
     * @return string[]
     */
    public function getLabelRelationsForType(string $labelName, RelationType $relationType): array;
    public function getLabelRelationsForTypes(array $labelNames, RelationType $relationType): array;


    /**
     * @return LabelRelation[]
     */
    public function getLabelRelationsForItem(string $relationId): array;
}
