<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use ValueObjects\StringLiteral\StringLiteral;

class CommandAuthorizationException extends \Exception
{
    /**
     * @var StringLiteral
     */
    private $userId;

    /**
     * @var AuthorizableCommandInterface
     */
    private $command;

    /**
     * CommandAuthorizationException constructor.
     */
    public function __construct(
        StringLiteral $userId,
        AuthorizableCommandInterface $command
    ) {
        parent::__construct(
            sprintf(
                "User with id: %s has no permission: \"%s\" on item: %s when executing command: %s",
                $userId->toNative(),
                $command->getPermission()->toNative(),
                $command->getItemId(),
                get_class($command)
            ),
            401
        );

        $this->userId = $userId;
        $this->command = $command;
    }

    /**
     * @return StringLiteral
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return AuthorizableCommandInterface
     */
    public function getCommand()
    {
        return $this->command;
    }
}
