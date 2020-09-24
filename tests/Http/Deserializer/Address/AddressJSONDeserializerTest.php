<?php

namespace CultuurNet\UDB3\Http\Deserializer\Address;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\StringLiteral\StringLiteral;

class AddressJSONDeserializerTest extends TestCase
{
    /**
     * @var AddressJSONDeserializer
     */
    private $deserializer;

    public function setUp()
    {
        $this->deserializer = new AddressJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_checks_all_required_fields_are_present()
    {
        $data = new StringLiteral('{}');

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
            $this->fail("No DataValidationException was thrown.");
        } catch (DataValidationException $e) {
            $this->assertEquals($expectedException->getValidationMessages(), $e->getValidationMessages());
        }
    }

    /**
     * @test
     */
    public function it_returns_an_address_object()
    {
        $data = new StringLiteral(
            json_encode(
                [
                    'streetAddress' => 'Wetstraat 1',
                    'postalCode' => '1000',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ]
            )
        );

        $expectedAddress = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            Country::fromNative('BE')
        );

        $actualAddress = $this->deserializer->deserialize($data);

        $this->assertEquals($expectedAddress, $actualAddress);
    }
}
