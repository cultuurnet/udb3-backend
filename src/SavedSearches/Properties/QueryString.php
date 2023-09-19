<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Properties;

use CultuurNet\UDB3\StringLiteral;

class QueryString extends StringLiteral
{
    public static function fromURLQueryString(string $queryString): QueryString
    {
        parse_str($queryString, $queryArray);

        if (!isset($queryArray['q'])) {
            throw new \InvalidArgumentException('Provided query string should contain a parameter named "q".');
        }

        return new QueryString($queryArray['q']);
    }
}
