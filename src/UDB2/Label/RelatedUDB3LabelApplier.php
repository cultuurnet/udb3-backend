<?php

namespace CultuurNet\UDB3\UDB2\Label;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelsRelationsRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\LabelAwareAggregateRoot;
use Psr\Log\LoggerInterface;
use ValueObjects\StringLiteral\StringLiteral;

class RelatedUDB3LabelApplier implements LabelApplierInterface
{
    /**
     * @var LabelsRelationsRepositoryInterface
     */
    private $labelsRelationsRepository;

    /**
     * @var LabelsRepositoryInterface
     */
    private $labelsRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;


    public function __construct(
        LabelsRelationsRepositoryInterface $labelsRelationsRepository,
        LabelsRepositoryInterface $labelsRepository,
        LoggerInterface $logger
    ) {
        $this->labelsRelationsRepository = $labelsRelationsRepository;
        $this->labelsRepository = $labelsRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function apply(LabelAwareAggregateRoot $aggregateRoot)
    {
        $labelRelations = $this->labelsRelationsRepository->getLabelRelationsForItem(
            new StringLiteral($aggregateRoot->getAggregateRootId())
        );

        /** @var Label[] $udb3Labels */
        $udb3Labels = [];

        foreach ($labelRelations as $labelRelation) {
            if (!$labelRelation->isImported()) {
                $labelName = $labelRelation->getLabelName();
                $label = $this->labelsRepository->getByName($labelName);

                if ($label) {
                    $this->logger->info(
                        'Found udb3 label ' . $label->getName()->toNative()
                        . ' for aggregate ' . $aggregateRoot->getAggregateRootId()
                    );

                    $udb3Labels[] = new Label(
                        $labelRelation->getLabelName()->toNative(),
                        $label->getVisibility() === Visibility::VISIBLE()
                    );
                }
            }
        }

        foreach ($udb3Labels as $udb3Label) {
            $aggregateRoot->addLabel($udb3Label);
            $this->logger->info(
                'Added udb3 label ' . $udb3Label
                . ' for aggregate ' . $aggregateRoot->getAggregateRootId()
            );
        }
    }
}
