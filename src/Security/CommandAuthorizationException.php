<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\StringLiteral;

class CommandAuthorizationException extends \Exception
{
    private StringLiteral $userId;

    private AuthorizableCommand $command;

    /**
     * CommandAuthorizationException constructor.
     */
    public function __construct(
        StringLiteral $userId,
        AuthorizableCommand $command
    ) {
        parent::__construct(
            sprintf(
                'User with id: %s has no permission: "%s" on item: %s when executing command: %s',
                $userId->toNative(),
                $command->getPermission()->toString(),
                $command->getItemId(),
                get_class($command)
            ),
            403
        );

        $this->userId = $userId;
        $this->command = $command;
    }

    public function getUserId(): StringLiteral
    {
        return $this->userId;
    }

    public function getCommand(): AuthorizableCommand
    {
        return $this->command;
    }
}
