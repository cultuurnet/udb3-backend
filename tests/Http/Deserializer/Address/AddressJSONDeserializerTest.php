<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Address;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use PHPUnit\Framework\TestCase;

class AddressJSONDeserializerTest extends TestCase
{
    private AddressJSONDeserializer $deserializer;

    public function setUp(): void
    {
        $this->deserializer = new AddressJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_checks_all_required_fields_are_present(): void
    {
        $data = '{}';

        $expectedException = new DataValidationException();
        $expectedException->setValidationMessages(
            [
                'streetAddress' => 'Should not be empty.',
                'postalCode' => 'Should not be empty.',
                'addressLocality' => 'Should not be empty.',
                'addressCountry' => 'Should not be empty.',
            ]
        );

        try {
            $this->deserializer->deserialize($data);
            $this->fail('No DataValidationException was thrown.');
        } catch (DataValidationException $e) {
            $this->assertEquals($expectedException->getValidationMessages(), $e->getValidationMessages());
        }
    }

    /**
     * @test
     */
    public function it_returns_an_address_object(): void
    {
        $data = Json::encode(
            [
                'streetAddress' => 'Wetstraat 1',
                'postalCode' => '1000',
                'addressLocality' => 'Brussel',
                'addressCountry' => 'BE',
            ]
        );

        $expectedAddress = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            new CountryCode('BE')
        );

        $actualAddress = $this->deserializer->deserialize($data);

        $this->assertEquals($expectedAddress, $actualAddress);
    }
}
