<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class UpdateContributors implements AuthorizableCommand
{
    private string $offerId;

    private EmailAddresses $emailAddresses;

    private OfferType $offerType;

    public function __construct(string $offerId, EmailAddresses $emailAddresses, OfferType $offerType)
    {
        $this->offerId = $offerId;
        $this->emailAddresses = $emailAddresses;
        $this->offerType = $offerType;
    }

    public function getItemId(): string
    {
        return $this->offerId;
    }

    public function getEmailAddresses(): EmailAddresses
    {
        return $this->emailAddresses;
    }

    public function getOfferType(): OfferType
    {
        return $this->offerType;
    }

    public function getPermission(): Permission
    {
        return Permission::aanbodBewerken();
    }
}
