<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Security\AuthorizableLabelCommand;
use CultuurNet\UDB3\StringLiteral;

class RemoveLabel implements AuthorizableCommand, AuthorizableLabelCommand
{
    private string $organizerId;

    private string $labelName;

    private bool $isVisible;

    public function __construct(
        string $organizerId,
        string $labelName,
        bool $isVisible = true
    ) {
        $this->organizerId = $organizerId;
        $this->labelName = $labelName;
        $this->isVisible = $isVisible;
    }

    public function getLabel(): Label
    {
        return new Label(new LabelName($this->labelName)); // REMOVE
    }

    public function getLabelName(): string
    {
        return $this->labelName;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getLabelNames(): array
    {
        return [
            new StringLiteral($this->labelName),
        ];
    }

    public function getPermission(): Permission
    {
        return Permission::organisatiesBewerken();
    }
}
