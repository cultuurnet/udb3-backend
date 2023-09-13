<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Security\Permission\PermissionVoter;

final class PermissionVoterCommandBusSecurity implements CommandBusSecurity
{
    private string $userId;

    private PermissionVoter $permissionVoter;

    public function __construct(
        ?string $userId = null,
        PermissionVoter $permissionVoter
    ) {
        $this->userId = $userId;
        $this->permissionVoter = $permissionVoter;
    }

    public function isAuthorized(AuthorizableCommand $command): bool
    {
        return $this->currentUserCanEditItem($command->getItemId(), $command);
    }

    private function currentUserCanEditItem(
        string $itemId,
        AuthorizableCommand $command
    ): bool {
        if (!$this->userId) {
            return false;
        }

        return $this->permissionVoter->isAllowed(
            $command->getPermission(),
            $itemId,
            $this->userId
        );
    }
}
