<?php

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

    /**
     * @param \CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface $numberFormatRepository
     * @param \CommerceGuys\Intl\Currency\CurrencyRepositoryInterface $currencyRepository
     */
    public function __construct(
        NumberFormatRepositoryInterface $numberFormatRepository,
        CurrencyRepositoryInterface $currencyRepository
    ) {
        $this->numberFormatRepository = $numberFormatRepository;
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * @param string $description
     *
     * @return array
     *   An array of price name value pairs.
     */
    public function parse($description)
    {
        $prices = array();

        $possiblePriceDescriptions = preg_split('/\s*;\s*/', $description);

        try {
            foreach ($possiblePriceDescriptions as $possiblePriceDescription) {
                $price = $this->parseSinglePriceDescription($possiblePriceDescription);
                $prices += $price;
            }
        } catch (RuntimeException $e) {
            $prices = array();
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

        $currencyFormatter = $this->getCurrencyFormatter();
        $currency = $this->currencyRepository->get('EUR');

        $priceValue = $currencyFormatter->parseCurrency(
            $priceValue,
            $currency
        );

        if (false === $priceValue) {
            throw new RuntimeException();
        }

        return [ $priceName => floatval($priceValue) ];
    }

    private function getCurrencyFormatter()
    {
        if (!$this->currencyFormatter) {
            $numberFormat = $this->numberFormatRepository->get('nl-BE');
            $this->currencyFormatter = new NumberFormatter(
                $numberFormat,
                NumberFormatter::CURRENCY
            );
        }

        return $this->currencyFormatter;
    }
}
