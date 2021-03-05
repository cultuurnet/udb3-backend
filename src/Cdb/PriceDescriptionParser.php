<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use CommerceGuys\Intl\Currency\CurrencyRepositoryInterface;
use CommerceGuys\Intl\Formatter\NumberFormatter;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface;
use RuntimeException;

/**
 * Parses a cdbxml <pricedescription> string into name value pairs.
 */
class PriceDescriptionParser
{
    /**
     * @var NumberFormatRepositoryInterface
     */
    private $numberFormatRepository;

    /**
     * @var CurrencyRepositoryInterface
     */
    private $currencyRepository;

    /**
     * @var NumberFormatter
     */
    private $currencyFormatter;

    public function __construct(
        NumberFormatRepositoryInterface $numberFormatRepository,
        CurrencyRepositoryInterface $currencyRepository
    ) {
        $this->numberFormatRepository = $numberFormatRepository;
        $this->currencyRepository = $currencyRepository;
        $this->currencyFormatter = new NumberFormatter(
            $this->numberFormatRepository->get('nl-BE'),
            NumberFormatter::CURRENCY
        );
    }

    /**
     * @param string $description
     *
     * @return array
     *   An array of price name value pairs.
     */
    public function parse($description)
    {
        $prices = [];

        $possiblePriceDescriptions = preg_split('/\s*;\s*/', $description);

        try {
            foreach ($possiblePriceDescriptions as $possiblePriceDescription) {
                $price = $this->parseSinglePriceDescription($possiblePriceDescription);
                $prices += $price;
            }
        } catch (RuntimeException $e) {
            $prices = [];
        }

        return $prices;
    }

    private function parseSinglePriceDescription($possiblePriceDescription)
    {
        $possiblePriceDescription = trim($possiblePriceDescription);
        $matches = [];

        $namePattern = '[\w\s]+';
        $valuePattern = '\€?\s*[\d,]+\s*\€?';

        $pricePattern =
            "/^(?<name>{$namePattern}):\s*(?<value>{$valuePattern})$/u";

        $priceDescriptionIsValid = preg_match(
            $pricePattern,
            $possiblePriceDescription,
            $matches
        );

        if (!$priceDescriptionIsValid) {
            throw new RuntimeException();
        }

        $priceName = trim($matches['name']);
        $priceValue = trim($matches['value']);

        $currency = $this->currencyRepository->get('EUR');

        $priceValue = $this->currencyFormatter->parseCurrency(
            $priceValue,
            $currency
        );

        if (false === $priceValue) {
            throw new RuntimeException();
        }

        return [ $priceName => (float) $priceValue ];
    }
}
