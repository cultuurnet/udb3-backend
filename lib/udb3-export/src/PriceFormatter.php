<?php

namespace CultuurNet\UDB3\EventExport;

class PriceFormatter
{
    /**
     * @var int
     */
    protected $significantDecimals = 2;

    /**
     * @var string
     */
    protected $decimalPoint = '.';

    /**
     * @var string
     */
    protected $thousandsSeparator = ',';

    /**
     * @var string
     */
    protected $freeLabel = '';

    /**
     * @var bool
     */
    protected $useFreeLabel = false;

    /**
     * @param int $significantDecimals
     * @param string $decimalPoint
     * @param string $thousandsSeparator
     * @param string|false $freeLabel
     */
    public function __construct(
        $significantDecimals = 2,
        $decimalPoint = '.',
        $thousandsSeparator = ',',
        $freeLabel = ''
    ) {
        $this->setSignificantDecimals($significantDecimals);
        $this->setDecimalPoint($decimalPoint);
        $this->setThousandsSeparator($thousandsSeparator);

        if (!empty($freeLabel)) {
            $this->useFreeLabel($freeLabel);
        }
    }

    /**
     * @param float|int $price
     * @return string $price
     */
    public function format($price)
    {
        // Limit the number of decimals, and set the decimal point and thousands separator.
        $price = number_format(
            $price,
            $this->significantDecimals,
            $this->decimalPoint,
            $this->thousandsSeparator
        );

        // Check that the price is zero after rounding the decimals.
        if ($price === "0{$this->decimalPoint}00") {
            if ($this->useFreeLabel) {
                // Return the "free" label if enabled.
                return $this->freeLabel;
            } else {
                // Otherwise simply return zero without decimals, as we only show significant decimals anyway.
                return '0';
            }
        }

        // Trim any insignificant zeroes after the decimal point.
        $price = rtrim($price, 0);

        // Trim the decimal point if there were only zeroes after the decimal point. Don't do this in the same trim as
        // above, as that would format 50,00 as 5.
        $price = trim($price, $this->decimalPoint);

        return $price;
    }

    /**
     * @param int $significantDecimals
     */
    public function setSignificantDecimals($significantDecimals)
    {
        $this->significantDecimals = $significantDecimals;
    }

    /**
     * @param string $decimalPoint
     */
    public function setDecimalPoint($decimalPoint)
    {
        $this->decimalPoint = $decimalPoint;
    }

    /**
     * @param string $thousandsSeparator
     */
    public function setThousandsSeparator($thousandsSeparator)
    {
        $this->thousandsSeparator = $thousandsSeparator;
    }

    /**
     * @param string $freeLabel
     */
    public function useFreeLabel($freeLabel)
    {
        $this->setFreeLabel($freeLabel);
        $this->enableFreeLabel();
    }

    /**
     * @param string $freeLabel
     */
    public function setFreeLabel($freeLabel)
    {
        $this->freeLabel = $freeLabel;
    }

    public function enableFreeLabel()
    {
        $this->useFreeLabel = true;
    }

    public function disableFreeLabel()
    {
        $this->useFreeLabel = false;
    }
}
