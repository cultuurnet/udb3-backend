<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use PHPUnit\Framework\TestCase;

final class AddressNormalizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $address = new Address(
            new Street('Wetstraat 16'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            new CountryCode('BE')
        );
        $addressArray = [
            'addressCountry' => 'BE',
            'addressLocality' => 'Brussel',
            'postalCode' => '1000',
            'streetAddress' => 'Wetstraat 16',
        ];

        $this->assertEquals(
            $addressArray,
            (new AddressNormalizer())->normalize($address)
        );
    }
}
