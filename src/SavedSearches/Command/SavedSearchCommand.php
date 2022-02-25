<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\StringLiteral;

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
