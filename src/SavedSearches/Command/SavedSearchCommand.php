<?php

namespace CultuurNet\UDB3\SavedSearches\Command;

use ValueObjects\StringLiteral\StringLiteral;

abstract class SavedSearchCommand
{
    /**
     * @var StringLiteral
     */
    protected $userId;


    public function __construct(
        StringLiteral $userId
    ) {
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
