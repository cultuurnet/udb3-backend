<?php

declare(strict_types=1);

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
            SchemaConfigurator::RELATION_TYPE => $this->relationType->toString(),
            SchemaConfigurator::RELATION_ID => $this->relationId->toNative(),
            SchemaConfigurator::IMPORTED => $this->imported,
        ];
    }

    /**
     * @return LabelRelation
     */
    public static function fromRelationalData(array $relation)
    {
        return new self(
            new LabelName($relation[SchemaConfigurator::LABEL_NAME]),
            new RelationType($relation[SchemaConfigurator::RELATION_TYPE]),
            new StringLiteral($relation[SchemaConfigurator::RELATION_ID]),
            (bool) $relation[SchemaConfigurator::IMPORTED]
        );
    }
}
