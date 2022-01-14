<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class DeleteDescription implements AuthorizableCommand
{
    private string $organizerId;

    private Language $language;

    public function __construct(string $organizerId, Language $language)
    {
        $this->organizerId = $organizerId;
        $this->language = $language;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getPermission(): Permission
    {
        return Permission::ORGANISATIES_BEWERKEN();
    }
}
