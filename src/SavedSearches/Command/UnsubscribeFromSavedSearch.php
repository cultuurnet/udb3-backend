<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

class UnsubscribeFromSavedSearch extends SavedSearchCommand
{
    protected string $searchId;


    public function __construct(
        string $userId,
        string $searchId
    ) {
        parent::__construct($userId);
        $this->searchId = $searchId;
    }

    public function getSearchId(): string
    {
        return $this->searchId;
    }
}
