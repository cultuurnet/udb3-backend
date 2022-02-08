<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MatchesRegexPattern;

class Url
{
    use IsString;
    use MatchesRegexPattern;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->guardString($value);
        $this->guardRegexPattern('/\\Ahttp[s]?:\\/\\//', $value);

        $value = $this->encode($value);

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Given string is not a valid url.');
        }

        $this->setValue($value);
    }

    private function encode(string $value): string
    {
        // Take into account already encoded urls by first decoding them.
        $value = urldecode($value);

        // Encode the url but revert forward slash / and colon :
        $value = urlencode($value);
        return str_replace(['%2F', '%3A'], ['/', ':'], $value);
    }
}
