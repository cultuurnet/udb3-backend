<?php

namespace CultuurNet\UDB3\PriceInfo;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Money\Money;
use PHPUnit\Framework\TestCase;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;

class PriceInfoTest extends TestCase
{
    /**
     * @var BasePrice
     */
    private $basePrice;

    /**
     * @var Tariff[]
     */
    private $tariffs;

    /**
     * @var PriceInfo
     */
    private $priceInfo;

    public function setUp()
    {
        $this->basePrice = new BasePrice(
            Price::fromFloat(10.5),
            Currency::fromNative('EUR')
        );

        $this->tariffs = [
            new Tariff(
                new MultilingualString(
                    new Language('nl'),
                    new StringLiteral('Werkloze dodo kwekers')
                ),
                new Price(0),
                Currency::fromNative('EUR')
            ),
        ];

        $this->priceInfo = (new PriceInfo($this->basePrice))
            ->withExtraTariff($this->tariffs[0]);
    }

    /**
     * @test
     */
    public function it_returns_the_base_price()
    {
        $this->assertEquals($this->basePrice, $this->priceInfo->getBasePrice());
    }

    /**
     * @test
     */
    public function it_returns_any_extra_tariffs()
    {
        $this->assertEquals($this->tariffs, $this->priceInfo->getTariffs());
    }

    /**
     * @test
     */
    public function it_can_be_serialized_and_deserialized()
    {
        $serialized = $this->priceInfo->serialize();
        $deserialized = PriceInfo::deserialize($serialized);

        $this->assertEquals($this->priceInfo, $deserialized);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_price_info_without_tariffs()
    {
        $udb3ModelPriceInfo = new \CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo(
            new \CultuurNet\UDB3\Model\ValueObject\Price\Tariff(
                new TranslatedTariffName(
                    new \CultuurNet\UDB3\Model\ValueObject\Translation\Language('nl'),
                    new TariffName('Basistarief')
                ),
                new Money(1000, new \Money\Currency('EUR'))
            ),
            new Tariffs()
        );

        $expected = new PriceInfo(
            new BasePrice(
                new Price(1000),
                Currency::fromNative('EUR')
            )
        );

        $actual = PriceInfo::fromUdb3ModelPriceInfo($udb3ModelPriceInfo);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_price_info_with_tariffs()
    {
        $udb3ModelPriceInfo = new \CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo(
            new \CultuurNet\UDB3\Model\ValueObject\Price\Tariff(
                new TranslatedTariffName(
                    new \CultuurNet\UDB3\Model\ValueObject\Translation\Language('nl'),
                    new TariffName('Basistarief')
                ),
                new Money(1000, new \Money\Currency('EUR'))
            ),
            new Tariffs(
                new \CultuurNet\UDB3\Model\ValueObject\Price\Tariff(
                    new TranslatedTariffName(
                        new \CultuurNet\UDB3\Model\ValueObject\Translation\Language('nl'),
                        new TariffName('Senioren')
                    ),
                    new Money(500, new \Money\Currency('EUR'))
                )
            )
        );

        $expected = new PriceInfo(
            new BasePrice(
                new Price(1000),
                Currency::fromNative('EUR')
            )
        );
        $expected = $expected
            ->withExtraTariff(
                new Tariff(
                    new MultilingualString(
                        new Language('nl'),
                        new StringLiteral('Senioren')
                    ),
                    new Price(500),
                    Currency::fromNative('EUR')
                )
            );

        $actual = PriceInfo::fromUdb3ModelPriceInfo($udb3ModelPriceInfo);

        $this->assertEquals($expected, $actual);
    }
}
