<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\PriceInfo;

use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class PriceInfoTest extends TestCase
{
    private Tariff $basePrice;

    /**
     * @var Tariff[]
     */
    private array $tariffs;

    /**
     * @var Tariff[]
     */
    private array $uitpasTariffs;

    private PriceInfo $priceInfo;

    public function setUp(): void
    {
        $this->basePrice = Tariff::createBasePrice(
            new Money(1050, new Currency('EUR'))
        );

        $this->tariffs = [
            new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('Inwoners')
                ),
                new Money(950, new Currency('EUR'))
            ),
        ];

        $this->uitpasTariffs = [
            new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('UiTPAS tarief')
                ),
                new Money(650, new Currency('EUR'))
            ),
        ];

        $this->priceInfo = (new PriceInfo($this->basePrice))
            ->withExtraTariff($this->tariffs[0])
            ->withExtraUiTPASTariff($this->uitpasTariffs[0]);
    }

    /**
     * @test
     */
    public function it_returns_the_base_price(): void
    {
        $this->assertEquals($this->basePrice, $this->priceInfo->getBasePrice());
    }

    /**
     * @test
     */
    public function it_returns_any_extra_tariffs(): void
    {
        $this->assertEquals($this->tariffs, $this->priceInfo->getTariffs());
    }

    /**
     * @test
     */
    public function it_returns_any_extra_uitpas_tariffs(): void
    {
        $this->assertEquals($this->uitpasTariffs, $this->priceInfo->getUiTPASTariffs());
    }

    /**
     * @test
     */
    public function it_can_be_serialized_and_deserialized(): void
    {
        $serialized = $this->priceInfo->serialize();
        $deserialized = PriceInfo::deserialize($serialized);

        $this->assertEquals($this->priceInfo, $deserialized);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_price_info_without_tariffs(): void
    {
        $udb3ModelPriceInfo = new \CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo(
            new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('Basistarief')
                ),
                new Money(1000, new Currency('EUR'))
            ),
            new Tariffs()
        );

        $expected = new PriceInfo(
            Tariff::createBasePrice(
                new Money(1000, new Currency('EUR'))
            )
        );

        $actual = PriceInfo::fromUdb3ModelPriceInfo($udb3ModelPriceInfo);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_price_info_with_tariffs(): void
    {
        $udb3ModelPriceInfo = new \CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo(
            new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('Basistarief')
                ),
                new Money(1000, new Currency('EUR'))
            ),
            new Tariffs(
                new Tariff(
                    new TranslatedTariffName(
                        new Language('nl'),
                        new TariffName('Senioren')
                    ),
                    new Money(500, new Currency('EUR'))
                )
            )
        );

        $expected = new PriceInfo(
            Tariff::createBasePrice(
                new Money(1000, new Currency('EUR'))
            )
        );
        $expected = $expected
            ->withExtraTariff(
                new Tariff(
                    new TranslatedTariffName(
                        new Language('nl'),
                        new TariffName('Senioren')
                    ),
                    new Money(500, new Currency('EUR'))
                )
            );

        $actual = PriceInfo::fromUdb3ModelPriceInfo($udb3ModelPriceInfo);

        $this->assertEquals($expected, $actual);
    }
}
