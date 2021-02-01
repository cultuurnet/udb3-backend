<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use InvalidArgumentException;

final class StatusType
{
    private const AVAILABLE = 'Available';
    private const TEMPORARILY_UNAVAILABLE = 'TemporarilyUnavailable';
    private const UNAVAILABLE = 'Unavailable';

    /**
     * @var string
     */
    private $value;

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

    public function toNative(): string
    {
        return $this->value;
    }

    public static function fromNative(string $value): StatusType
    {
        return new StatusType($value);
    }

    public function equals(StatusType $status): bool
    {
        return $this->value === $status->toNative();
    }
}
