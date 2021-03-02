<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractUserCommand extends AbstractCommand
{
    /**
     * @var StringLiteral
     */
    private $userId;


    public function __construct(
        UUID $uuid,
        StringLiteral $userId
    ) {
        parent::__construct($uuid);
        $this->userId = $userId;
    }

    /**
     * @return StringLiteral
     */
    public function getUserId()
    {
        return $this->userId;
    }
}
