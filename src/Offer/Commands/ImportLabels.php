<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Security\LabelSecurityInterface;
use ValueObjects\StringLiteral\StringLiteral;

final class ImportLabels extends AbstractCommand implements LabelSecurityInterface
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

    public function __construct(string $itemId, Labels $labels)
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

    public function getLabelsToImport(): Labels
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

    public function getNames(): array
    {
        return array_map(
            function (Label $label) {
                return new StringLiteral($label->getName()->toString());
            },
            $this->getLabelsToImport()->toArray()
        );
    }
}
