<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Label as LegacyLabel;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableLabelCommand;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractLabelCommand implements AuthorizableCommand, AuthorizableLabelCommand
{
    private string $organizerId;

    private Label $label;

    public function __construct(
        string $organizerId,
        Label $label
    ) {
        $this->organizerId = $organizerId;
        $this->label = $label;
    }

    public function getLabel(): LegacyLabel
    {
        return new LegacyLabel(
            $this->label->getName()->toString(),
            $this->label->isVisible()
        );
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getLabelNames(): array
    {
        return [
            new StringLiteral($this->label->getName()->toString()),
        ];
    }

    public function getPermission(): Permission
    {
        return Permission::ORGANISATIES_BEWERKEN();
    }
}
