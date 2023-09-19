<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;

class SubscribeToSavedSearch extends SavedSearchCommand
{
    protected string $name;

    /**
     * @var QueryString
     */
    protected $query;


    public function __construct(
        string $userId,
        string $name,
        QueryString $query
    ) {
        parent::__construct($userId);
        $this->name = $name;
        $this->query = $query;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return QueryString
     */
    public function getQuery()
    {
        return $this->query;
    }
}
