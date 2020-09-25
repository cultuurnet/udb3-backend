<?php

namespace CultuurNet\UDB3\SavedSearches\Properties;

use ValueObjects\StringLiteral\StringLiteral;

class QueryString extends StringLiteral
{
    /**
     * @return string
     */
    public function toURLQueryString()
    {
        return http_build_query([
            'q' => $this->value,
        ]);
    }

    public static function fromURLQueryString(string $queryString): QueryString
    {
        parse_str($queryString, $queryArray);

        if (!isset($queryArray['q'])) {
            throw new \InvalidArgumentException('Provided query string should contain a parameter named "q".');
        }

        return new QueryString($queryArray['q']);
    }
}
