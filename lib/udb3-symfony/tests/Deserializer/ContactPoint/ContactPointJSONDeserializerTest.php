<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\ContactPoint;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\ContactPoint;
use ValueObjects\StringLiteral\StringLiteral;

class ContactPointJSONDeserializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContactPointJSONDeserializer
     */
    private $contactPointJSONDeserializer;

    protected function setUp()
    {
        $this->contactPointJSONDeserializer = new ContactPointJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_validates_data_on_deserialize_and_throws_exception_when_invalid()
    {
        $data = new StringLiteral('[{"type":"foo","value":"0123456789"}]');

        $this->setExpectedException(DataValidationException::class);

        $this->contactPointJSONDeserializer->deserialize($data);
    }

    /**
     * @test
     */
    public function it_deserializes_data_to_contact_point()
    {
        $phone1 = '{"type":"phone","value":"0123456789"}';
        $phone2 = '{"type":"phone","value":"9876543210"}';
        $email = '{"type":"email","value":"user@company.be"}';
        $data = new StringLiteral('[' . $phone1 . ', ' . $phone2 . ', ' . $email .']');

        $expectedContactPoint = new ContactPoint(
            ['0123456789', '9876543210'],
            ['user@company.be']
        );

        $contactPoint = $this->contactPointJSONDeserializer->deserialize($data);

        $this->assertEquals($expectedContactPoint, $contactPoint);
    }
}
