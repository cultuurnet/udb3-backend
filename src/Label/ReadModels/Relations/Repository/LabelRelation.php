<?php

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository;

use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use ValueObjects\StringLiteral\StringLiteral;

class LabelRelation implements \JsonSerializable
{
    /**
     * @var LabelName
     */
    private $labelName;

    /**
     * @var RelationType
     */
    private $relationType;

    /**
     * @var StringLiteral
     */
    private $relationId;

    /**
     * @var bool
     */
    private $imported;

    /**
     * Entity constructor.
     * @param LabelName $labelName
     * @param RelationType $relationType
     * @param StringLiteral $relationId
     * @param bool $imported
     */
    public function __construct(
        LabelName $labelName,
        RelationType $relationType,
        StringLiteral $relationId,
        $imported
    ) {
        $this->labelName = $labelName;
        $this->relationType = $relationType;
        $this->relationId = $relationId;
        $this->imported = (bool) $imported;
    }

    /**
     * @return LabelName
     */
    public function getLabelName()
    {
        return $this->labelName;
    }

    /**
     * @return RelationType
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * @return StringLiteral
     */
    public function getRelationId()
    {
        return $this->relationId;
    }

    /**
     * @return bool
     */
    public function isImported()
    {
        return $this->imported;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            SchemaConfigurator::LABEL_NAME => $this->labelName->toNative(),
            SchemaConfigurator::RELATION_TYPE => $this->relationType->toNative(),
            SchemaConfigurator::RELATION_ID => $this->relationId->toNative(),
            SchemaConfigurator::IMPORTED => $this->imported,
        ];
    }

    /**
     * @param array $relation
     * @return LabelRelation
     */
    public static function fromRelationalData(array $relation)
    {
        return new static(
            new LabelName($relation[SchemaConfigurator::LABEL_NAME]),
            RelationType::fromNative($relation[SchemaConfigurator::RELATION_TYPE]),
            new StringLiteral($relation[SchemaConfigurator::RELATION_ID]),
            (bool) $relation[SchemaConfigurator::IMPORTED]
        );
    }
}
