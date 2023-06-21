<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

final class MoneyFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider EuroDataProvider
     */
    public function it_can_return_a_price_from_a(Money $expectedMoney, Money $actualMoney): void
    {
        $this->assertEquals(
            $expectedMoney,
            $actualMoney
        );
    }

    /**
     * @test
     * @dataProvider CentsDataProvider
     */
    public function it_can_return_a_price_from_cents(Money $expectedMoney, Money $actualMoney): void
    {
        $this->assertEquals(
            $expectedMoney,
            $actualMoney
        );
    }

    /**
     * @test
     */
    public function it_throws_with_an_invalid_input_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Given value should be an int, string, float, double. Got boolean instead.');

        // @phpstan-ignore-next-line
        MoneyFactory::create(true, new Currency('EUR'));
    }

    public function EuroDataProvider(): array
    {
        return [
            'euro in int' => [
                'expectedMoney' => new Money(200, new Currency('EUR')),
                'actualMoney' => MoneyFactory::create(2, new Currency('EUR')),
            ],
            'euro in float' => [
                'expectedMoney' => new Money(230, new Currency('GBP')),
                'actualMoney' => MoneyFactory::create(2.3, new Currency('GBP')),
            ],
            'euro in string' => [
                'expectedMoney' => new Money(4500, new Currency('EUR')),
                'actualMoney' => MoneyFactory::create('45', new Currency('EUR')),
            ],
            'euro in string with decimals' => [
                'expectedMoney' => new Money(3390, new Currency('EUR')),
                'actualMoney' => MoneyFactory::create('33.9', new Currency('EUR')),
            ],
        ];
    }

    public function CentsDataProvider(): array
    {
        return [
            'cents in int' => [
                'expectedMoney' => new Money(1999, new Currency('EUR')),
                'actualMoney' => MoneyFactory::createFromCents(1999, new Currency('EUR')),
            ],
            'cents in string' => [
                'expectedMoney' => new Money(2999, new Currency('EUR')),
                'actualMoney' => MoneyFactory::createFromCents('2999', new Currency('EUR')),
            ],
            'cents in different currency' => [
                'expectedMoney' => new Money(3999, new Currency('SEK')),
                'actualMoney' => MoneyFactory::createFromCents('3999', new Currency('SEK')),
            ],
        ];
    }
}
