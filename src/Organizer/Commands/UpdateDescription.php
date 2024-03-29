<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class UpdateDescription implements AuthorizableCommand
{
    private string $organizerId;

    private Description $description;

    private Language $language;

    public function __construct(
        string $organizerId,
        Description $description,
        Language $language
    ) {
        $this->organizerId = $organizerId;
        $this->description = $description;
        $this->language = $language;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getLanguage(): Language
    {
        return $this->language;
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
