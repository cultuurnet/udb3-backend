<?php

namespace CultuurNet\UDB3\SavedSearches\Properties;

class CreatorQueryString extends QueryString
{
    /**
     * @param string[] $queryParts
     */
    public function __construct(string ...$queryParts)
    {
        if (empty($queryParts)) {
            throw new \InvalidArgumentException('At least one query part is required.');
        }

        $query = implode(' OR ', $queryParts);
        if (count($queryParts) > 1) {
            $query = '(' . $query . ')';
        }
        $query = 'creator:' . $query;

        parent::__construct($query);
    }
}
