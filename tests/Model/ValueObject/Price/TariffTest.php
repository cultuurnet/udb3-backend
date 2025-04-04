<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class TariffTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_combine_a_translated_name_and_a_price(): void
    {
        $name = new TranslatedTariffName(
            new Language('nl'),
            new TariffName('Senioren')
        );

        $price = new Money(1000, new Currency('EUR'));

        $tariff = new Tariff($name, $price);

        $this->assertEquals($name, $tariff->getName());
        $this->assertEquals($price, $tariff->getPrice());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_name(): void
    {
        $name = new TranslatedTariffName(
            new Language('nl'),
            new TariffName('Senioren')
        );
        $price = new Money(1000, new Currency('EUR'));
        $tariff = new Tariff($name, $price);

        $updatedName = $name->withTranslation(
            new Language('fr'),
            new TariffName('Senioren FR')
        );
        $updatedTariff = $tariff->withName($updatedName);

        $this->assertNotEquals($tariff, $updatedTariff);
        $this->assertEquals($name, $tariff->getName());
        $this->assertEquals($updatedName, $updatedTariff->getName());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_price(): void
    {
        $name = new TranslatedTariffName(
            new Language('nl'),
            new TariffName('Senioren')
        );
        $price = new Money(1000, new Currency('EUR'));
        $tariff = new Tariff($name, $price);

        $updatedPrice = $price->multiply(2);
        $updatedTariff = $tariff->withPrice($updatedPrice);

        $this->assertNotEquals($tariff, $updatedTariff);
        $this->assertEquals($price, $tariff->getPrice());
        $this->assertEquals($updatedPrice, $updatedTariff->getPrice());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_a_group_price(): void
    {
        $name = new TranslatedTariffName(
            new Language('nl'),
            new TariffName('Leerlingen')
        );
        $price = new Money(1000, new Currency('EUR'));
        $tariff = new Tariff($name, $price);

        $updatedTariff = $tariff->withGroupPrice(true);

        $this->assertNotEquals($tariff, $updatedTariff);
        $this->assertFalse($tariff->isGroupPrice());
        $this->assertTrue($updatedTariff->isGroupPrice());
    }
}
