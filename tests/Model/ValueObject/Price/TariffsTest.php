<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class TariffsTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_find_duplicates()
    {
        $duplicateName = new TranslatedTariffName(
            new Language('nl'),
            new TariffName('Leerkrachten')
        );
        $name2 = new TranslatedTariffName(
            new Language('nl'),
            new TariffName('Studenten')
        );

        $price1 = new Money(1000, new Currency('EUR'));
        $price2 = new Money(500, new Currency('EUR'));
        $price3 = new Money(2000, new Currency('EUR'));

        $tariff1 = new Tariff($duplicateName, $price1);
        $tariff2 = new Tariff($name2, $price2);
        $tariff3 = new Tariff($duplicateName, $price3);

        $tariffs = new Tariffs($tariff1, $tariff2, $tariff3);

        $this->assertTrue($tariffs->hasDuplicates());
    }

    /**
     * @test
     */
    public function it_should_find_only_unique_values()
    {
        $name1 = new TranslatedTariffName(
            new Language('nl'),
            new TariffName('Leerkrachten')
        );
        $name2 = new TranslatedTariffName(
            new Language('nl'),
            new TariffName('Studenten')
        );
        $name3 = new TranslatedTariffName(
            new Language('nl'),
            new TariffName('Directeurs')
        );

        $price1 = new Money(1000, new Currency('EUR'));
        $price2 = new Money(500, new Currency('EUR'));
        $price3 = new Money(2000, new Currency('EUR'));

        $tariff1 = new Tariff($name1, $price1);
        $tariff2 = new Tariff($name2, $price2);
        $tariff3 = new Tariff($name3, $price3);

        $tariffs = new Tariffs($tariff1, $tariff2, $tariff3);

        $this->assertFalse($tariffs->hasDuplicates());
    }
}