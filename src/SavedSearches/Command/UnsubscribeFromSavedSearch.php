<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use ValueObjects\StringLiteral\StringLiteral;

class UnsubscribeFromSavedSearch extends SavedSearchCommand
{
    /**
     * @var StringLiteral
     */
    protected $searchId;


    public function __construct(
        StringLiteral $userId,
        StringLiteral $searchId
    ) {
        parent::__construct($userId);
        $this->searchId = $searchId;
    }

    /**
     * @return StringLiteral
     */
    public function getSearchId()
    {
        return $this->searchId;
    }
}
