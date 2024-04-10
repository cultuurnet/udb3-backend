<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Properties;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;

class QueryString
{
    use IsString;
    use IsNotEmpty;
    use Trims;

    public function __construct(string $value)
    {
        $value = $this->trim($value);
        $this->guardNotEmpty($value);
        $this->setValue($value);
    }

    public static function fromURLQueryString(string $queryString): QueryString
    {
        parse_str($queryString, $queryArray);

        if (!isset($queryArray['q'])) {
            throw new \InvalidArgumentException('Provided query string should contain a parameter named "q".');
        }

        return new QueryString($queryArray['q']);
    }

    public function clean(): self
    {
        /* Bugfix https://jira.publiq.be/browse/III-6131
        Why not always do this in the constructor?
        It will give problems with newly entered faulty queries, they will be escaped twice (saving + loading), this could corrupt to query
        Example: %2B -> change to + -> change to ""
        */
        return new QueryString(urldecode(stripslashes($this->value)));
    }
}
