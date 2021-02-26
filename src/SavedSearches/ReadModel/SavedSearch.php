<?php

namespace CultuurNet\UDB3\SavedSearches\ReadModel;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use ValueObjects\StringLiteral\StringLiteral;

class SavedSearch implements \JsonSerializable
{
    /**
     * @var StringLiteral
     */
    protected $id;

    /**
     * @var StringLiteral
     */
    protected $name;

    /**
     * @var QueryString
     */
    protected $query;

    /**
     * @param StringLiteral $id
     */
    public function __construct(StringLiteral $name, QueryString $query, StringLiteral $id = null)
    {
        $this->name = $name;
        $this->query = $query;
        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $serializedSavedSearch = [
            'name' => $this->name->toNative(),
            'query' => $this->query->toNative(),
        ];

        if ($this->id) {
            $serializedSavedSearch['id'] = (string) $this->id;
        }

        return $serializedSavedSearch;
    }
}
