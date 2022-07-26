<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Event;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\StringLiteral;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

final class PricesUpdatedDeserializerTest extends TestCase
{
    private PricesUpdatedDeserializer $pricesUpdatedDeserializer;

    protected function setUp(): void
    {
        $this->pricesUpdatedDeserializer = new PricesUpdatedDeserializer();
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $pricesUpdatedAsArray = [
            'cdbid' => '12345',
            'tariffs' => [
                [
                    'name' => 'Tariff 1',
                    'price' => 1.99,
                ],
                [
                    'name' => 'Tariff 2',
                    'price' => 2.99,
                ],
            ]
        ];

        $this->assertEquals(
            new PricesUpdated(
                '12345',
                new Tariffs(
                    new Tariff(
                        new TranslatedTariffName(
                            new Language('nl'),
                            new TariffName('Tariff 1')
                        ),
                        new Money(
                            199,
                            new Currency('EUR')
                        )
                    ),
                    new Tariff(
                        new TranslatedTariffName(
                            new Language('nl'),
                            new TariffName('Tariff 2')
                        ),
                        new Money(
                            299,
                            new Currency('EUR')
                        )
                    )
                )
            ),
            $this->pricesUpdatedDeserializer->deserialize(
                new StringLiteral(Json::encode($pricesUpdatedAsArray))
            )
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_cdbid_is_missing(): void
    {
        $pricesUpdatedAsArray = [
            'tariffs' => [
                [
                    'name' => 'Tariff 1',
                    'price' => 1.99,
                ],
                [
                    'name' => 'Tariff 2',
                    'price' => 2.99,
                ],
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing cdbid property.');

        $this->pricesUpdatedDeserializer->deserialize(
            new StringLiteral(Json::encode($pricesUpdatedAsArray))
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_tariffs_is_missing(): void
    {
        $pricesUpdatedAsArray = [
            'cdbid' => '12345',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing tariffs property.');

        $this->pricesUpdatedDeserializer->deserialize(
            new StringLiteral(Json::encode($pricesUpdatedAsArray))
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_tariffs_is_not_an_array(): void
    {
        $pricesUpdatedAsArray = [
            'cdbid' => '12345',
            'tariffs' => 'not an array',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tariffs property must be an array.');

        $this->pricesUpdatedDeserializer->deserialize(
            new StringLiteral(Json::encode($pricesUpdatedAsArray))
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_tariff_has_no_valid_name(): void
    {
        $pricesUpdatedAsArray = [
            'cdbid' => '12345',
            'tariffs' => [
                [
                    'name' => 'Tariff 1',
                    'price' => 1.99,
                ],
                [
                    'price' => 2.99,
                ],
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tariff must have a name.');

        $this->pricesUpdatedDeserializer->deserialize(
            new StringLiteral(Json::encode($pricesUpdatedAsArray))
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_tariff_has_no_valid_price(): void
    {
        $pricesUpdatedAsArray = [
            'cdbid' => '12345',
            'tariffs' => [
                [
                    'name' => 'Tariff 1',
                    'price' => 'not a number',
                ],
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tariff price must be a number.');

        $this->pricesUpdatedDeserializer->deserialize(
            new StringLiteral(Json::encode($pricesUpdatedAsArray))
        );
    }
}
