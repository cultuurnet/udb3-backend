<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class UpdateEducationalDescription implements AuthorizableCommand
{
    private string $organizerId;

    private Description $educationalDescription;

    private Language $language;

    public function __construct(
        string $organizerId,
        Description $educationalDescription,
        Language $language
    ) {
        $this->organizerId = $organizerId;
        $this->educationalDescription = $educationalDescription;
        $this->language = $language;
    }

    public function getEducationalDescription(): Description
    {
        return $this->educationalDescription;
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
