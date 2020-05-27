<?php

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
     * @param StringLiteral $userId
     * @param AuthorizableCommandInterface $command
     */
    public function __construct(
        StringLiteral $userId,
        AuthorizableCommandInterface $command
    ) {
        parent::__construct('User with id: ' . $userId->toNative() .
            ' has no permission: "' . $command->getPermission()->toNative() .
            '" on item: ' . $command->getItemId() .
            ' when executing command: ' . get_class($command));

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
