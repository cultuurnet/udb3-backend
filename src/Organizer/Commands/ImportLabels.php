<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

final class ImportLabels implements AuthorizableCommand
{
    private string $organizerId;

    private Labels $labels;

    private Labels $labelsToKeepIfAlreadyOnOrganizer;

    public function __construct(
        string $organizerId,
        Labels $label
    ) {
        $this->organizerId = $organizerId;
        $this->labels = $label;
        $this->labelsToKeepIfAlreadyOnOrganizer = new Labels();
    }

    public function withLabelsToKeepIfAlreadyOnOrganizer(Labels $labels): self
    {
        $c = clone $this;
        $c->labelsToKeepIfAlreadyOnOrganizer = $labels;
        return $c;
    }

    public function getLabelsToKeepIfAlreadyOnOrganizer(): Labels
    {
        return $this->labelsToKeepIfAlreadyOnOrganizer;
    }

    public function getLabels(): Labels
    {
        $labelNamesToKeep = array_map(
            function (Label $label) {
                return $label->getName();
            },
            $this->labelsToKeepIfAlreadyOnOrganizer->toArray()
        );

        return $this->labels->filter(
            function (Label $label) use ($labelNamesToKeep) {
                return !in_array($label->getName(), $labelNamesToKeep);
            }
        );
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getPermission(): Permission
    {
        return Permission::aanbodBewerken();
    }

    public function getLabelNames(): array
    {
        return array_map(
            function (Label $label) {
                return new StringLiteral($label->getName()->toString());
            },
            $this->getLabels()->toArray()
        );
    }
}
