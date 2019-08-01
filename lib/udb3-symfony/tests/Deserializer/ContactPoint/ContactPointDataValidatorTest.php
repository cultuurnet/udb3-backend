<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\ContactPoint;

use CultuurNet\Deserializer\DataValidationException;

class ContactPointDataValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContactPointDataValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new ContactPointDataValidator();
    }

    /**
     * @test
     */
    public function it_throws_an_exception_for_multiple_errors_at_once()
    {
        $data = [
            [
                'value' => 'foo',
            ],
            [
                'value' => 'foo',
                'type' => 'bar',
            ],
            [
                'type' => 'url',
            ],
        ];

        $expectedMessages = [
            '0.type' => 'Required but could not be found.',
            '1.type' => 'Invalid type. Allowed types are: url, phone, email.',
            '2.value' => 'Required but could not be found.',
        ];

        try {
            $this->validator->validate($data);
            $this->fail('Did not catch expected DataValidationException.');
        } catch (\Exception $e) {
            /* @var DataValidationException $e */
            $this->assertInstanceOf(DataValidationException::class, $e);
            $this->assertEquals($expectedMessages, $e->getValidationMessages());
        }
    }
}
