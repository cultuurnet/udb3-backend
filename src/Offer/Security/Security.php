<?php

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
     * @var UserIdentificationInterface
     */
    private $userIdentification;

    /**
     * @var PermissionVoterInterface
     */
    private $permissionVoter;

    /**
     * Security constructor.
     * @param UserIdentificationInterface $userIdentification
     * @param PermissionVoterInterface $permissionVoter
     */
    public function __construct(
        UserIdentificationInterface $userIdentification,
        PermissionVoterInterface $permissionVoter
    ) {
        $this->userIdentification = $userIdentification;
        $this->permissionVoter = $permissionVoter;
    }

    /**
     * @inheritdoc
     */
    public function allowsUpdateWithCdbXml(StringLiteral $offerId)
    {
        return $this->currentUiTIDUserCanEditOffer(
            $offerId,
            new PreflightCommand($offerId->toNative(), Permission::AANBOD_BEWERKEN())
        );
    }

    /**
     * @inheritdoc
     */
    public function isAuthorized(AuthorizableCommandInterface $command)
    {
        $offerId = new StringLiteral($command->getItemId());

        return $this->currentUiTIDUserCanEditOffer($offerId, $command);
    }

    /**
     * @param StringLiteral $offerId
     * @param AuthorizableCommandInterface $command
     * @return bool
     */
    private function currentUiTIDUserCanEditOffer(
        StringLiteral $offerId,
        AuthorizableCommandInterface $command
    ) {
        if (!$this->userIdentification->getId()) {
            return false;
        }

        return $this->permissionVoter->isAllowed(
            $command->getPermission(),
            $offerId,
            $this->userIdentification->getId()
        );
    }
}
