<?php

namespace CultuurNet\UDB3\Model\ValueObject\Translation;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MatchesRegexPattern;

class Language
{
    use IsString;
    use MatchesRegexPattern;

    public const REGEX = '/^[a-z]{2}$/';

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
