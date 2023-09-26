<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use InvalidArgumentException;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType as much as possible
 */
final class StatusType
{
    use IsString;

    private const AVAILABLE = 'Available';
    private const TEMPORARILY_UNAVAILABLE = 'TemporarilyUnavailable';
    private const UNAVAILABLE = 'Unavailable';

    /**
     * @var string[]
     */
    private const ALLOWED_VALUES = [
        self::AVAILABLE,
        self::TEMPORARILY_UNAVAILABLE,
        self::UNAVAILABLE,
    ];

    private function __construct(string $value)
    {
        if (!\in_array($value, self::ALLOWED_VALUES, true)) {
            throw new InvalidArgumentException('Status does not support the value "' . $value . '"');
        }
        $this->value = $value;
    }

    public static function available(): StatusType
    {
        return new StatusType(self::AVAILABLE);
    }

    public static function temporarilyUnavailable(): StatusType
    {
        return new StatusType(self::TEMPORARILY_UNAVAILABLE);
    }

    public static function unavailable(): StatusType
    {
        return new StatusType(self::UNAVAILABLE);
    }

    public static function fromNative(string $value): StatusType
    {
        return new StatusType($value);
    }
}
