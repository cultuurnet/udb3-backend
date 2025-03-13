<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels\Helper;

use CultuurNet\UDB3\Role\ValueObjects\Query;

class ExtraUuidFromConstraint
{
    public static function extractUuid(Query $query): ?string
    {
        preg_match('/id:([a-f0-9\-]{36})/', $query->toString(), $matches);
        return $matches[1] ?? null;
    }
}
