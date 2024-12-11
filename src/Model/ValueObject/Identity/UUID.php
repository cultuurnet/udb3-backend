<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MatchesRegexPattern;
use Ramsey\Uuid\Uuid as RamseyUuid;

class UUID
{
    use IsString;
    use MatchesRegexPattern;

    public const NIL = '00000000-0000-0000-0000-000000000000';

    /**
     * Ensures backwards compatibility with older, malformed, uuids present in UDB.
     */
    private const BC_REGEX = '/\\A[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12}\\z/';

    public function __construct(string $value)
    {
        $this->guardRegexPattern(
            self::BC_REGEX,
            $value,
            $value . ' is not a valid uuid.'
        );

        $this->setValue($value);
    }

    public static function uuid4(): self
    {
        return new UUID(RamseyUuid::uuid4()->toString());
    }
}
