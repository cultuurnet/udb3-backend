<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Price;

use DateTimeImmutable;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;


class MoneyNormalizerTest extends TestCase
{
    private MoneyNormalizer $normalizer;
    private Money $money;

    protected function setUp(): void
    {
        $this->normalizer = new MoneyNormalizer();
        $this->money = new Money(100, new Currency('EUR'));
    }

    /**
     * @test
     */
    public function it_should_normalize_money(): void
    {
        $expected = [
            'amount' => 100,
            'currency' => 'EUR',
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($this->money));
    }

    /**
     * @test
     */
    public function it_should_throw_exception_for_invalid_object(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid object type, expected %s, received %s.', Money::class, DateTimeImmutable::class));

        $this->normalizer->normalize(new DateTimeImmutable());
    }

    /**
     * @test
     */
    public function it_should_support_money_objects(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization($this->money));
    }

    /**
     * @test
     */
    public function it_should_not_support_non_money_objects(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new DateTimeImmutable()));
    }
}
