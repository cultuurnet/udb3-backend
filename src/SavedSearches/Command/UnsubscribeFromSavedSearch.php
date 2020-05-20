<?php

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\ValueObject\SapiVersion;
use ValueObjects\StringLiteral\StringLiteral;

class UnsubscribeFromSavedSearch extends SavedSearchCommand
{
    /**
     * @var StringLiteral
     */
    protected $searchId;

    /**
     * @param SapiVersion $sapiVersion
     * @param StringLiteral $userId
     * @param StringLiteral $searchId
     */
    public function __construct(
        SapiVersion $sapiVersion,
        StringLiteral $userId,
        StringLiteral $searchId
    ) {
        parent::__construct($sapiVersion, $userId);
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
