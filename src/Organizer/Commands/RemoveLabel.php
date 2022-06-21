<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Security\AuthorizableLabelCommand;

class RemoveLabel implements AuthorizableCommand, AuthorizableLabelCommand
{
    private string $organizerId;

    private string $labelName;

    public function __construct(string $organizerId, string $labelName)
    {
        $this->organizerId = $organizerId;
        $this->labelName = $labelName;
    }

    public function getLabelName(): string
    {
        return $this->labelName;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getLabelNames(): array
    {
        return [$this->labelName];
    }

    public function getPermission(): Permission
    {
        return Permission::organisatiesBewerken();
    }
}
