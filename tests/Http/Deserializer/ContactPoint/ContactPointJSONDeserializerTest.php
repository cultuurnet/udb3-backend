<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\ContactPoint;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\ContactPoint;
use PHPUnit\Framework\TestCase;

class ContactPointJSONDeserializerTest extends TestCase
{
    private ContactPointJSONDeserializer $contactPointJSONDeserializer;

    protected function setUp(): void
    {
        $this->contactPointJSONDeserializer = new ContactPointJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_validates_data_on_deserialize_and_throws_exception_when_invalid(): void
    {
        $data = '[{"type":"foo","value":"0123456789"}]';

        $this->expectException(DataValidationException::class);

        $this->contactPointJSONDeserializer->deserialize($data);
    }

    /**
     * @test
     */
    public function it_deserializes_data_to_contact_point(): void
    {
        $phone1 = '{"type":"phone","value":"0123456789"}';
        $phone2 = '{"type":"phone","value":"9876543210"}';
        $email = '{"type":"email","value":"user@company.be"}';
        $data = '[' . $phone1 . ', ' . $phone2 . ', ' . $email . ']';

        $expectedContactPoint = new ContactPoint(
            ['0123456789', '9876543210'],
            ['user@company.be']
        );

        $contactPoint = $this->contactPointJSONDeserializer->deserialize($data);

        $this->assertEquals($expectedContactPoint, $contactPoint);
    }
}
