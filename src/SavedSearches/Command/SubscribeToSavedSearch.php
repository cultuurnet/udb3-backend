<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\StringLiteral;

class SubscribeToSavedSearch extends SavedSearchCommand
{
    /**
     * @var StringLiteral
     */
    protected $name;

    /**
     * @var QueryString
     */
    protected $query;


    public function __construct(
        string $userId,
        StringLiteral $name,
        QueryString $query
    ) {
        parent::__construct($userId);
        $this->name = $name;
        $this->query = $query;
    }

    /**
     * @return StringLiteral
     */
    public function getName()
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
