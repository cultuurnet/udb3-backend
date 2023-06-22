<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use CultuurNet\UDB3\StringLiteral;

class UnauthorizableCommandException extends \Exception
{
    private StringLiteral $userId;

    private $command;

    public function __construct(StringLiteral $userId, $command)
    {
        parent::__construct('User with id: ' . $userId->toNative() .
            ' failed to execute command: ' . get_class($command) .
            ' because it is not authorizable.');

        $this->userId = $userId;
        $this->command = $command;
    }

    public function getUserId(): StringLiteral
    {
        return $this->userId;
    }


    public function getCommand()
    {
        return $this->command;
    }
}
