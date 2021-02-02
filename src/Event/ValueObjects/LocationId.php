<?php

namespace CultuurNet\UDB3\Event\ValueObjects;

use ValueObjects\StringLiteral\StringLiteral;

class LocationId extends StringLiteral
{
    private static $dummyPlaceForEducationIds = [];

    public function __construct($value)
    {
        parent::__construct($value);

        if (empty($value)) {
            throw new \InvalidArgumentException('LocationId can\'t have an empty value.');
        }
    }

    public function isDummyPlaceForEducation(): bool
    {
        return in_array($this->value, self::$dummyPlaceForEducationIds);
    }

    public static function setDummyPlaceForEducationIds(array $dummyPlaceForEducationIds): void
    {
        self::$dummyPlaceForEducationIds = $dummyPlaceForEducationIds;
    }
}
