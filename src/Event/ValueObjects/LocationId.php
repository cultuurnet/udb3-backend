<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

final class LocationId
{
    use IsString;

    private const VIRTUAL_LOCATION = '00000000-0000-0000-0000-000000000000';

    private static array $dummyPlaceForEducationIds = [];

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('LocationId can\'t have an empty value.');
        }

        $this->setValue($value);
    }

    public function isVirtualLocation(): bool
    {
        return substr($this->value, -strlen(self::VIRTUAL_LOCATION)) === self::VIRTUAL_LOCATION;
    }

    public function isDummyPlaceForEducation(): bool
    {
        return in_array($this->value, self::$dummyPlaceForEducationIds, true);
    }

    public static function setDummyPlaceForEducationIds(array $dummyPlaceForEducationIds): void
    {
        self::$dummyPlaceForEducationIds = $dummyPlaceForEducationIds;
    }
}
