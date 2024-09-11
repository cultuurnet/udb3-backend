<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

class PriceFormatter
{
    protected int $significantDecimals = 2;

    protected string $decimalPoint = '.';

    protected string $thousandsSeparator = ',';

    protected string $freeLabel = '';

    protected bool $useFreeLabel = false;

    public function __construct(
        int $significantDecimals = 2,
        string $decimalPoint = '.',
        string $thousandsSeparator = ',',
        string $freeLabel = ''
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
     */
    public function format($price): string
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
        $price = rtrim($price, '0');

        // Trim the decimal point if there were only zeroes after the decimal point. Don't do this in the same trim as
        // above, as that would format 50,00 as 5.
        $price = trim($price, $this->decimalPoint);

        return $price;
    }

    public function setSignificantDecimals(int $significantDecimals): void
    {
        $this->significantDecimals = $significantDecimals;
    }

    public function setDecimalPoint(string $decimalPoint): void
    {
        $this->decimalPoint = $decimalPoint;
    }

    public function setThousandsSeparator(string $thousandsSeparator): void
    {
        $this->thousandsSeparator = $thousandsSeparator;
    }

    public function useFreeLabel(string $freeLabel): void
    {
        $this->setFreeLabel($freeLabel);
        $this->enableFreeLabel();
    }

    public function setFreeLabel(string $freeLabel): void
    {
        $this->freeLabel = $freeLabel;
    }

    public function enableFreeLabel(): void
    {
        $this->useFreeLabel = true;
    }

    public function disableFreeLabel(): void
    {
        $this->useFreeLabel = false;
    }
}
