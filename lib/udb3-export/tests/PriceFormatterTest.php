<?php

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\EventExport\PriceFormatter;

/**
 * Class PriceFormatterTest
 * @package CultuurNet\UDB3\EventExport\Format\HTML
 */
class PriceFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider priceData
     */
    public function it_rounds_prices($decimals, $price, $expected)
    {
        $formatter = new PriceFormatter($decimals);
        $formatted = $formatter->format($price);
        $this->assertEquals($expected, $formatted);
    }

    public function priceData()
    {
        return [
            // Zero significant decimals.
            [0, 1.00, '1'],
            [0, 1.10, '1'],
            [0, 1.01, '1'],
            [0, 1.11, '1'],
            [0, 1.15, '1'],

            // One significant decimal.
            [1, 1.00, '1'],
            [1, 1.10, '1.1'],
            [1, 1.01, '1'],
            [1, 1.11, '1.1'],
            [1, 1.15, '1.2'],

            // Two significant decimals.
            [2, 1, '1'],
            [2, 1.00, '1'],
            [2, 10.00, '10'],
            [2, 10.5, '10.5'],
            [2, 10.05, '10.05'],
            [2, 10.50, '10.5'],
            [2, 10.55, '10.55'],
            [2, 10.554, '10.55'],
            [2, 10.555, '10.56'],
            [2, 10.001, '10'],
            [2, 10.005, '10.01'],
            [2, 0.45, '0.45'],
        ];
    }

    /**
     * @test
     * @dataProvider customSeparatorData()
     */
    public function it_has_customizable_separators($decimalPoint, $thousandsSeparator, $original, $expected)
    {
        $formatter = new PriceFormatter(2, $decimalPoint, $thousandsSeparator);
        $formatted = $formatter->format($original);
        $this->assertEquals($expected, $formatted);
    }

    public function customSeparatorData()
    {
        return [
            ['.', ',', 1000000.66, '1,000,000.66'],
            [',', '.', 1000000.66, '1.000.000,66'],
            [',', ' ', 1000000.66, '1 000 000,66'],
            [',', '', 1000000.66, '1000000,66'],
        ];
    }

    /**
     * @test
     */
    public function it_replaces_zero_with_a_label_if_enabled()
    {
        // Free label disabled by default.
        $formatter = new PriceFormatter();
        $formatted = $formatter->format(0);
        $this->assertEquals('0', $formatted);

        // Use a free label.
        $formatter->useFreeLabel('Free');
        $formatted = $formatter->format(0);
        $this->assertEquals('Free', $formatted);

        // Free label for price that is rounded to zero.
        $formatter->useFreeLabel('Free');
        $formatted = $formatter->format(0.001);
        $this->assertEquals('Free', $formatted);

        // Disabling the free label.
        $formatter->disableFreeLabel();
        $formatted = $formatter->format(0);
        $this->assertEquals('0', $formatted);

        // Re-enabling the free label.
        $formatter->enableFreeLabel();
        $formatted = $formatter->format(0);
        $this->assertEquals('Free', $formatted);

        // Changing the free label.
        $formatter->setFreeLabel('Gratuit');
        $formatted = $formatter->format(0);
        $this->assertEquals('Gratuit', $formatted);
    }
}
