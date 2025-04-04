<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class PriceInfoTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_combine_a_base_price_and_optional_extra_tariffs(): void
    {
        $basePrice = Tariff::createBasePrice(
            new Money(1000, new Currency('EUR'))
        );
        $tariffs = new Tariffs();
        $priceInfo = new PriceInfo($basePrice, $tariffs);

        $this->assertEquals($basePrice, $priceInfo->getBasePrice());
        $this->assertEquals($tariffs, $priceInfo->getTariffs());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_base_price(): void
    {
        $basePrice = Tariff::createBasePrice(
            new Money(1000, new Currency('EUR'))
        );
        $tariffs = new Tariffs();
        $priceInfo = new PriceInfo($basePrice, $tariffs);

        $updatedBasePrice = Tariff::createBasePrice(
            new Money(2000, new Currency('EUR'))
        );
        $updatedPriceInfo = $priceInfo->withBasePrice($updatedBasePrice);

        $this->assertNotEquals($priceInfo, $updatedPriceInfo);
        $this->assertEquals($basePrice, $priceInfo->getBasePrice());
        $this->assertEquals($updatedBasePrice, $updatedPriceInfo->getBasePrice());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_updated_tariffs(): void
    {
        $basePrice = Tariff::createBasePrice(
            new Money(1000, new Currency('EUR'))
        );
        $tariffs = new Tariffs();
        $priceInfo = new PriceInfo($basePrice, $tariffs);

        $updatedTariffs = new Tariffs(
            new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('Senioren')
                ),
                new Money(500, new Currency('EUR'))
            )
        );
        $updatedPriceInfo = $priceInfo->withTariffs($updatedTariffs);

        $this->assertNotEquals($priceInfo, $updatedPriceInfo);
        $this->assertEquals($tariffs, $priceInfo->getTariffs());
        $this->assertEquals($updatedTariffs, $updatedPriceInfo->getTariffs());
    }

    /**
     * @test
     * @dataProvider priceInfos
     */
    public function it_can_serialize(PriceInfo $priceInfo, array $serializedPriceInfo): void
    {
        $this->assertEquals(
            $serializedPriceInfo,
            $priceInfo->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
    }

    public function priceInfos(): array
    {
        return [
            'originalPriceInfo' => [
                new PriceInfo(
                    Tariff::createBasePrice(
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
                ),
                [
                    'base' => [
                        'price' => 1000,
                        'currency' => 'EUR',
                        'groupPrice' => false,
                    ],
                    'tariffs' => [
                        0 => [
                            'price' => 500,
                            'currency' => 'EUR',
                            'name' => [
                                'nl' => 'Senioren',
                            ],
                            'groupPrice' => false,
                        ],
                    ],
                    'uitpas_tariffs' => [],
                ],
            ],
           'priceInfoWithGroupPrice' => [
               new PriceInfo(
                   (Tariff::createBasePrice(
                       new Money(1000, new Currency('EUR'))
                   ))->withGroupPrice(true),
                   new Tariffs(
                       new Tariff(
                           new TranslatedTariffName(
                               new Language('nl'),
                               new TariffName('Senioren')
                           ),
                           new Money(500, new Currency('EUR'))
                       ),
                       (new Tariff(
                           new TranslatedTariffName(
                               new Language('nl'),
                               new TariffName('Jongeren')
                           ),
                           new Money(750, new Currency('EUR'))
                       ))->withGroupPrice(true)
                   )
               ),
               [
                   'base' => [
                       'price' => 1000,
                       'currency' => 'EUR',
                       'groupPrice' => true,
                   ],
                   'tariffs' => [
                       0 => [
                           'price' => 500,
                           'currency' => 'EUR',
                           'name' => [
                               'nl' => 'Senioren',
                           ],
                           'groupPrice' => false,
                       ],
                       1 => [
                           'price' => 750,
                           'currency' => 'EUR',
                           'name' => [
                               'nl' => 'Jongeren',
                           ],
                           'groupPrice' => true,
                       ],
                   ],
                   'uitpas_tariffs' => [],
               ],
           ],
        ];
    }
}
