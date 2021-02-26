<?php

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MatchesRegexPattern;

class UUID
{
    use IsString;
    use MatchesRegexPattern;

    /**
     * Ensures backwards compatibility with older, malformed, uuids present in UDB.
     */
    public const BC_REGEX = '\\A[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12}\\z';

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->guardRegexPattern(
            '/' . self::BC_REGEX . '/',
            $value,
            $value . ' is not a valid uuid.'
        );

        $this->setValue($value);
    }
}
