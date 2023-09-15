<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

class CommandAuthorizationException extends \Exception
{
    private string $userId;

    private AuthorizableCommand $command;

    public function __construct(
        string $userId,
        AuthorizableCommand $command
    ) {
        parent::__construct(
            sprintf(
                'User with id: %s has no permission: "%s" on item: %s when executing command: %s',
                $userId,
                $command->getPermission()->toString(),
                $command->getItemId(),
                get_class($command)
            ),
            403
        );

        $this->userId = $userId;
        $this->command = $command;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCommand(): AuthorizableCommand
    {
        return $this->command;
    }
}
