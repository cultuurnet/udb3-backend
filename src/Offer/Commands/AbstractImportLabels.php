<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Security\LabelSecurityInterface;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractImportLabels extends AbstractCommand implements LabelSecurityInterface
{
    /**
     * @var Labels
     */
    private $labels;

    /**
     * @var Labels
     */
    private $labelsToKeepIfAlreadyOnOffer;

    /**
     * @var Labels
     */
    private $labelsToRemoveWhenOnOffer;

    /**
     * @param string $itemId
     * @param Labels $labels
     */
    public function __construct($itemId, Labels $labels)
    {
        parent::__construct($itemId);
        $this->labels = $labels;
        $this->labelsToKeepIfAlreadyOnOffer = new Labels();
        $this->labelsToRemoveWhenOnOffer = new Labels();
    }

    public function withLabelsToKeepIfAlreadyOnOffer(Labels $labels): self
    {
        $c = clone $this;
        $c->labelsToKeepIfAlreadyOnOffer = $labels;
        return $c;
    }

    public function getLabelsToKeepIfAlreadyOnOffer(): Labels
    {
        return $this->labelsToKeepIfAlreadyOnOffer;
    }

    public function withLabelsToRemoveWhenOnOffer(Labels $labels): self
    {
        $c = clone $this;
        $c->labelsToRemoveWhenOnOffer = $labels;
        return $c;
    }

    public function getLabelsToRemoveWhenOnOffer(): Labels
    {
        return $this->labelsToRemoveWhenOnOffer;
    }

    /**
     * @return Labels
     */
    public function getLabelsToImport()
    {
        $labelNamesToKeep = array_map(
            function (Label $label) {
                return $label->getName();
            },
            $this->labelsToKeepIfAlreadyOnOffer->toArray()
        );

        return $this->labels->filter(
            function (Label $label) use ($labelNamesToKeep) {
                return !in_array($label->getName(), $labelNamesToKeep);
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function getNames()
    {
        return array_map(
            function (Label $label) {
                return new StringLiteral($label->getName()->toString());
            },
            $this->getLabelsToImport()->toArray()
        );
    }
}
