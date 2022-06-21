<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableLabelCommand;

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

    public function getLabel(): Label
    {
        return $this->label;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getLabelNames(): array
    {
        return [$this->label->getName()->toString()];
    }

    public function getPermission(): Permission
    {
        return Permission::organisatiesBewerken();
    }
}
