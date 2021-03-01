<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MatchesRegexPattern;

class CountryCode
{
    use IsString;
    use MatchesRegexPattern;

    public const REGEX = '/^[A-Z]{2}$/';

    /**
     * @param string $code
     */
    public function __construct($code)
    {
        $this->guardRegexPattern(self::REGEX, $code);
        $this->setValue($code);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->toString();
    }
}
