<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository;

use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use JsonSerializable;
use CultuurNet\UDB3\StringLiteral;

class LabelRelation implements JsonSerializable
{
    private string $labelName;

    private RelationType $relationType;

    private StringLiteral $relationId;

    private bool $imported;

    public function __construct(
        string $labelName,
        RelationType $relationType,
        StringLiteral $relationId,
        bool $imported
    ) {
        $this->labelName = $labelName;
        $this->relationType = $relationType;
        $this->relationId = $relationId;
        $this->imported = $imported;
    }

    public function getLabelName(): string
    {
        return $this->labelName;
    }

    public function getRelationType(): RelationType
    {
        return $this->relationType;
    }

    public function getRelationId(): StringLiteral
    {
        return $this->relationId;
    }

    public function isImported(): bool
    {
        return $this->imported;
    }

    public function jsonSerialize(): array
    {
        return [
            SchemaConfigurator::LABEL_NAME => $this->labelName,
            SchemaConfigurator::RELATION_TYPE => $this->relationType->toString(),
            SchemaConfigurator::RELATION_ID => $this->relationId->toNative(),
            SchemaConfigurator::IMPORTED => $this->imported,
        ];
    }

    public static function fromRelationalData(array $relation): LabelRelation
    {
        return new self(
            $relation[SchemaConfigurator::LABEL_NAME],
            new RelationType($relation[SchemaConfigurator::RELATION_TYPE]),
            new StringLiteral($relation[SchemaConfigurator::RELATION_ID]),
            (bool) $relation[SchemaConfigurator::IMPORTED]
        );
    }
}
