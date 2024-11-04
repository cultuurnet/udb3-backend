<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\UnsupportedException;

class AddressDenormalizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_denormalize(): void
    {
        $addressArray = [
            'addressCountry' => 'BE',
            'addressLocality' => 'Brussel',
            'postalCode' => '1000',
            'streetAddress' => 'Wetstraat 16',
        ];
        $address = new Address(
            new Street('Wetstraat 16'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            new CountryCode('BE')
        );

        $this->assertEquals(
            $address,
            (new AddressDenormalizer())->denormalize($addressArray, Address::class)
        );
    }

    /**
     * @test
     */
    public function it_requires_an_address_type(): void
    {
        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage('AddressDenormalizer does not support stdClass.');

        (new AddressDenormalizer())->denormalize([], \stdClass::class);
    }

    /**
     * @test
     */
    public function it_requires_an_array(): void
    {
        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage('Address data should be an associative array.');

        (new AddressDenormalizer())->denormalize('not an array', Address::class);
    }
}
