<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class UpdateTitle implements AuthorizableCommand
{
    private string $offerId;

    private Language $language;

    private Title $title;

    public function __construct(string $offerId, Language $language, Title $title)
    {
        $this->offerId = $offerId;
        $this->language = $language;
        $this->title = $title;
    }

    public function getItemId(): string
    {
        return $this->offerId;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getPermission(): Permission
    {
        return Permission::aanbodBewerken();
    }
}
