<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class UpdateTitle implements AuthorizableCommand
{
    private string $organizerId;

    private Title $title;

    private Language $language;

    public function __construct(
        string $organizerId,
        Title $title,
        Language $language
    ) {
        $this->organizerId = $organizerId;
        $this->title = $title;
        $this->language = $language;
    }

    public function getTitle(): Title
    {
        return $this->title;
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
