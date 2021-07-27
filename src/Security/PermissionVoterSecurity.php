<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Security\Permission\PermissionVoterInterface;
use ValueObjects\StringLiteral\StringLiteral;

class PermissionVoterSecurity implements Security
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
