<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class TariffsTest extends TestCase
{
    private Tariff $tariff1;
    private Tariff $tariff2;

    protected function setUp(): void
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
        $this->tariff1 = new Tariff($duplicateName, $price1);
        $this->tariff2 = new Tariff($name2, $price2);
    }

    /**
     * @test
     */
    public function it_should_find_duplicates(): void
    {
        $duplicateName = new TranslatedTariffName(
            new Language('nl'),
            new TariffName('Leerkrachten')
        );
        $price3 = new Money(2000, new Currency('EUR'));

        $tariff3 = new Tariff($duplicateName, $price3);
        $tariffs = new Tariffs($this->tariff1, $this->tariff2, $tariff3);

        $this->assertTrue($tariffs->hasDuplicates());
    }

    /**
     * @test
     */
    public function it_should_find_only_unique_values(): void
    {
        $name3 = new TranslatedTariffName(
            new Language('nl'),
            new TariffName('Directeurs')
        );
        $price3 = new Money(2000, new Currency('EUR'));
        $tariff3 = new Tariff($name3, $price3);

        $tariffs = new Tariffs($this->tariff1, $this->tariff2, $tariff3);

        $this->assertFalse($tariffs->hasDuplicates());
    }

    /**
     * @test
     */
    public function it_should_ignore_same_tariff_names_in_another_language(): void
    {
        $name3 = new TranslatedTariffName(
            new Language('en'),
            new TariffName('Leerkrachten')
        );
        $price3 = new Money(2000, new Currency('EUR'));
        $tariff3 = new Tariff($name3, $price3);

        $tariffs = new Tariffs($this->tariff1, $this->tariff2, $tariff3);

        $this->assertFalse($tariffs->hasDuplicates());
    }

    /**
     * @test
     */
    public function it_should_find_same_tariff_names_in_another_language(): void
    {
        $duplicateNameInForeignLanguage = new TranslatedTariffName(
            new Language('en'),
            new TariffName('CEO')
        );
        $price3 = new Money(2000, new Currency('EUR'));
        $tariff3 = new Tariff($duplicateNameInForeignLanguage, $price3);

        $price4 = new Money(2000, new Currency('EUR'));
        $tariff4 = new Tariff($duplicateNameInForeignLanguage, $price4);

        $tariffs = new Tariffs($this->tariff1, $this->tariff2, $tariff3, $tariff4);

        $this->assertTrue($tariffs->hasDuplicates());
    }
}
