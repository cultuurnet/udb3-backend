<?php

namespace CultuurNet\UDB3\PriceInfo;

use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\Number\Natural;

/**
 * Price expressed in its lowest unit, eg cents.
 */
class Price extends Natural
{
    /**
     * @param float $value
     * @return Price
     */
    public static function fromFloat($value)
    {
        if (!is_float($value)) {
            throw new InvalidNativeArgumentException($value, ['float']);
        }

        $precision = 0;
        return new Price((int) round($value * 100, $precision));
    }

    /**
     * @return float
     */
    public function toFloat()
    {
        return $this->toNative() / 100;
    }
}
