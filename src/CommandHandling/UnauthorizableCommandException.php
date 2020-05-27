<?php

namespace CultuurNet\UDB3\CommandHandling;

use ValueObjects\StringLiteral\StringLiteral;

class UnauthorizableCommandException extends \Exception
{
    /**
     * @var StringLiteral
     */
    private $userId;

    /**
     * @var mixed
     */
    private $command;

    /**
     * @param StringLiteral $userId
     * @param mixed $command
     */
    public function __construct(StringLiteral $userId, $command)
    {
        parent::__construct('User with id: ' . $userId->toNative() .
            ' failed to execute command: ' . get_class($command) .
            ' because it is not authorizable.');

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
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }
}
