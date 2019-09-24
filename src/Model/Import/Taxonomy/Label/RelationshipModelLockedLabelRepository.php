<?php

namespace CultuurNet\UDB3\Model\Import\Taxonomy\Label;

use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use ValueObjects\StringLiteral\StringLiteral;

final class RelationshipModelLockedLabelRepository implements LockedLabelRepository
{
    /**
     * @var ReadRepositoryInterface
     */
    private $relationshipRepository;

    public function __construct(ReadRepositoryInterface $relationshipRepository)
    {
        $this->relationshipRepository = $relationshipRepository;
    }

    public function getLockedLabelsForItem(string $itemId): Labels
    {
        // Get all related labels for the item but filter out those that were imported via the JSON CRUD API.
        // The remaining labels should be considered locked for removal via imports.
        $labelRelations = array_filter(
            $this->relationshipRepository->getLabelRelationsForItem(new StringLiteral($itemId)),
            function (LabelRelation $labelRelation) {
                return !$labelRelation->isImported();
            }
        );

        $labels = array_map(
            function (LabelRelation $labelRelation) {
                return new Label(
                    new LabelName(
                        $labelRelation->getLabelName()->toNative()
                    )
                );
            },
            $labelRelations
        );

        return Labels::fromArray($labels);
    }

    public function getUnlockedLabelsForItem(string $itemId): Labels
    {
        // Get all related labels for the item that were imported via the JSON CRUD API.
        // These are considered unlocked for removal.
        $labelRelations = array_filter(
            $this->relationshipRepository->getLabelRelationsForItem(new StringLiteral($itemId)),
            function (LabelRelation $labelRelation) {
                return $labelRelation->isImported();
            }
        );

        $labels = array_map(
            function (LabelRelation $labelRelation) {
                return new Label(
                    new LabelName(
                        $labelRelation->getLabelName()->toNative()
                    )
                );
            },
            $labelRelations
        );

        return Labels::fromArray($labels);
    }
}
