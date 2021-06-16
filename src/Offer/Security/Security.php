<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Offer\Commands\PreflightCommand;
use CultuurNet\UDB3\Offer\Security\Permission\PermissionVoterInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\SecurityInterface;
use CultuurNet\UDB3\Security\UserIdentificationInterface;
use ValueObjects\StringLiteral\StringLiteral;

class Security implements SecurityInterface
{
    /**
     * @var string
     */
    private $userId;

    /**
     * @var PermissionVoterInterface
     */
    private $permissionVoter;

    public function __construct(
        ?string $userId = null,
        PermissionVoterInterface $permissionVoter
    ) {
        $this->userId = $userId;
        $this->permissionVoter = $permissionVoter;
    }

    public function allowsUpdateWithCdbXml(StringLiteral $offerId)
    {
        return $this->currentUiTIDUserCanEditOffer(
            $offerId,
            new PreflightCommand($offerId->toNative(), Permission::AANBOD_BEWERKEN())
        );
    }

    public function isAuthorized(AuthorizableCommandInterface $command)
    {
        $offerId = new StringLiteral($command->getItemId());

        return $this->currentUiTIDUserCanEditOffer($offerId, $command);
    }

    private function currentUiTIDUserCanEditOffer(
        StringLiteral $offerId,
        AuthorizableCommandInterface $command
    ): bool {
        if (!$this->userId) {
            return false;
        }

        return $this->permissionVoter->isAllowed(
            $command->getPermission(),
            $offerId,
            new StringLiteral($this->userId)
        );
    }
}
