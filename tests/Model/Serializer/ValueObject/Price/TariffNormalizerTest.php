<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use DateTimeImmutable;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use InvalidArgumentException;

class TariffNormalizerTest extends TestCase
{
    private TariffNormalizer $normalizer;
    private Tariff $tariff;

    protected function setUp(): void
    {
        $this->normalizer = new TariffNormalizer();
        $this->tariff = new Tariff(
            (new TranslatedTariffName(
                new Language('nl'),
                new TariffName('Basistarief')
            ))->withTranslation(new Language('en'), new TariffName('Base tariff')),
            new Money(
                100,
                new Currency('EUR')
            )
        );
    }

    /**
     * @test
     */
    public function it_should_normalize_a_tariff(): void
    {
        $expected = [
            'price' => ['amount' => 100, 'currency' => 'EUR'],
            'names' => [
                'en' => 'Base tariff',
                'nl' => 'Basistarief',
            ],
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($this->tariff));
    }

    /**
     * @test
     */
    public function it_should_throw_exception_for_invalid_object(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid object type, expected %s, received %s.', Tariff::class, DateTimeImmutable::class));

        $this->normalizer->normalize(new DateTimeImmutable());
    }

    /**
     * @test
     */
    public function it_should_support_tariff_objects(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization($this->tariff));
    }

    /**
     * @test
     */
    public function it_should_not_support_non_tariff_objects(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new DateTimeImmutable()));
    }
}
