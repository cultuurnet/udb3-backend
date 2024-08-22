<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository;

use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\ColumnNames;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use JsonSerializable;

class LabelRelation implements JsonSerializable
{
    private string $labelName;

    private RelationType $relationType;

    private string $relationId;

    private bool $imported;

    public function __construct(
        string $labelName,
        RelationType $relationType,
        string $relationId,
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

    public function getRelationId(): string
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
            ColumnNames::LABEL_NAME => $this->labelName,
            ColumnNames::RELATION_TYPE => $this->relationType->toString(),
            ColumnNames::RELATION_ID => $this->relationId,
            ColumnNames::IMPORTED => $this->imported,
        ];
    }

    public static function fromRelationalData(array $relation): LabelRelation
    {
        return new self(
            $relation[ColumnNames::LABEL_NAME],
            new RelationType($relation[ColumnNames::RELATION_TYPE]),
            $relation[ColumnNames::RELATION_ID],
            (bool) $relation[ColumnNames::IMPORTED]
        );
    }
}
