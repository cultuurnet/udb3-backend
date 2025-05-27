<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class ReplaceLabels implements AuthorizableCommand
{
    private string $organizerId;

    private Labels $labels;

    public function __construct(
        string $organizerId,
        Labels $label
    ) {
        $this->organizerId = $organizerId;
        $this->labels = $label;
    }

    public function getLabels(): Labels
    {
        return $this->labels;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getPermission(): Permission
    {
        return Permission::organisatiesBewerken();
    }
}
