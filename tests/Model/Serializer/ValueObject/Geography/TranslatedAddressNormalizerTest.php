<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TranslatedAddressNormalizerTest extends TestCase
{
    private TranslatedAddressNormalizer $normalizer;
    private TranslatedAddress $translatedAddress;

    protected function setUp(): void
    {
        $translatedAddress = new TranslatedAddress(
            new Language('nl'),
            new Address(
                new Street('Henegouwenkaai 41-43'),
                new PostalCode('1080'),
                new Locality('Brussel'),
                new CountryCode('BE')
            )
        );
        $this->translatedAddress =  $translatedAddress->withTranslation(
            new Language('fr'),
            new Address(
                new Street('Quai du Hainaut 41-43'),
                new PostalCode('1080'),
                new Locality('Bruxelles'),
                new CountryCode('BE')
            )
        );

        $this->normalizer = new TranslatedAddressNormalizer();
    }

    /**
     * @test
     */
    public function it_should_normalize_TranslatedAddress(): void
    {
        $expected = [
            'nl' => [
                'street' => 'Henegouwenkaai 41-43',
                'postalCode' => '1080',
                'locality' => 'Brussel',
                'countryCode' => 'BE',
            ],
            'fr' => [
                'street' => 'Quai du Hainaut 41-43',
                'postalCode' => '1080',
                'locality' => 'Bruxelles',
                'countryCode' => 'BE',
            ],
        ];

        $this->assertEquals($expected, $this->normalizer->normalize($this->translatedAddress));
    }

    /**
     * @test
     */
    public function it_should_throw_exception_for_invalid_object(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid object type, expected %s, received %s.', TranslatedAddress::class, DateTimeImmutable::class));

        $this->normalizer->normalize(new DateTimeImmutable());
    }

    /**
     * @test
     */
    public function it_should_support_TranslatedAddress_objects(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization($this->translatedAddress));
    }

    /**
     * @test
     */
    public function it_should_not_support_non_TranslatedAddress_objects(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new DateTimeImmutable()));
    }
}
