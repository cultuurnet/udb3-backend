<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Security\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableLabelCommand;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractLabelCommand extends AbstractOrganizerCommand implements AuthorizableCommandInterface, AuthorizableLabelCommand
{
    /**
     * @var Label
     */
    private $label;

    public function __construct(
        string $organizerId,
        Label $label
    ) {
        parent::__construct($organizerId);
        $this->label = $label;
    }

    public function getLabel(): Label
    {
        return $this->label;
    }

    public function getItemId(): string
    {
        return $this->getOrganizerId();
    }

    public function getLabelNames(): array
    {
        return [
            new StringLiteral((string) $this->label),
        ];
    }

    public function getPermission(): Permission
    {
        return Permission::ORGANISATIES_BEWERKEN();
    }
}
