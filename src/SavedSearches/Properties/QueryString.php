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
        $this->setValue($this->clean($value));
    }

    public static function fromURLQueryString(string $queryString): QueryString
    {
        parse_str($queryString, $queryArray);

        if (!isset($queryArray['q'])) {
            throw new \InvalidArgumentException('Provided query string should contain a parameter named "q".');
        }

        return new QueryString($queryArray['q']);
    }

    private function clean(string $value): string
    {
        /* Bugfix https://jira.publiq.be/browse/III-6131 */

        // Use preg_replace_callback to apply stripslashes() only to the part between square brackets
        $value = preg_replace_callback('/\[([^]]+)\]/', function ($matches) {
            return '[' . stripslashes($matches[1]) . ']';
        }, $value);

        return str_replace('%2B', '+', $value);
    }
}
