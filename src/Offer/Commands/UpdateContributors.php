<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

abstract class UpdateContributors implements AuthorizableCommand
{
    private string $offerId;

    private EmailAddresses $emailAddresses;

    public function __construct(string $offerId, EmailAddresses $emailAddresses)
    {
        $this->offerId = $offerId;
        $this->emailAddresses = $emailAddresses;
    }

    public function getItemId(): string
    {
        return $this->offerId;
    }

    public function getEmailAddresses(): EmailAddresses
    {
        return $this->emailAddresses;
    }

    public function getPermission(): Permission
    {
        return Permission::aanbodBewerken();
    }
}
