<?php

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use ValueObjects\StringLiteral\StringLiteral;

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

    /**
     * @param SapiVersion $sapiVersion
     * @param StringLiteral $userId
     * @param StringLiteral $name
     * @param QueryString $query
     */
    public function __construct(
        SapiVersion $sapiVersion,
        StringLiteral $userId,
        StringLiteral $name,
        QueryString $query
    ) {
        parent::__construct($sapiVersion, $userId);
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
