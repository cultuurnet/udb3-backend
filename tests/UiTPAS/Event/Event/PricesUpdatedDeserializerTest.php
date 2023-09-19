<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Event;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
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
            ],
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
            $this->pricesUpdatedDeserializer->deserialize(Json::encode($pricesUpdatedAsArray))
        );
    }

    /**
     * @test
     * @dataProvider invalidDataProvider
     */
    public function it_throws_on_invalid_data(array $invalidData, string $exceptionMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->pricesUpdatedDeserializer->deserialize(Json::encode($invalidData));
    }

    public function invalidDataProvider(): array
    {
        return [
            'missing cdbid' => [
                [
                    'tariffs' => [
                        [
                            'name' => 'Tariff 1',
                            'price' => 1.99,
                        ],
                    ],
                ],
                'The required properties (cdbid) are missing (JsonPointer: /).',
            ],
            'missing tariffs' => [
                [
                    'cdbid' => '12345',
                ],
                'The required properties (tariffs) are missing (JsonPointer: /).',
            ],
            'tariffs not an array' => [
                [
                    'cdbid' => '12345',
                    'tariffs' => 'not an array',
                ],
                'The data (string) must match the type: array (JsonPointer: /tariffs).',
            ],
            'tariff missing name' => [
                [
                    'cdbid' => '12345',
                    'tariffs' => [
                        [
                            'price' => 1.99,
                        ],
                    ],
                ],
                'The required properties (name) are missing (JsonPointer: /tariffs/0).',
            ],
            'tariff missing price' => [
                [
                    'cdbid' => '12345',
                    'tariffs' => [
                        [
                            'name' => 'Tariff 1',
                        ],
                    ],
                ],
                'The required properties (price) are missing (JsonPointer: /tariffs/0).',
            ],
            'tariff price not a number' => [
                [
                    'cdbid' => '12345',
                    'tariffs' => [
                        [
                            'name' => 'Tariff 1',
                            'price' => 'not a number',
                        ],
                    ],
                ],
                'The data (string) must match the type: number (JsonPointer: /tariffs/0/price).',
            ],
        ];
    }
}
