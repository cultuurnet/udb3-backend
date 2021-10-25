<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String;

/**
 * @method static MockEnum foo()
 * @method static MockEnum bar()
 */
class MockEnum extends Enum
{
    /**
     * @return string[]
     */
    public static function getAllowedValues(): array
    {
        return [
            'foo',
            'bar',
        ];
    }
}
