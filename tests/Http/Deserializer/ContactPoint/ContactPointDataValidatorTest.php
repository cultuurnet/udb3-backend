<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\ContactPoint;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use PHPUnit\Framework\TestCase;

class ContactPointDataValidatorTest extends TestCase
{
    private ContactPointDataValidator $validator;

    public function setUp(): void
    {
        $this->validator = new ContactPointDataValidator();
    }

    /**
     * @test
     */
    public function it_throws_an_exception_for_multiple_errors_at_once(): void
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
        } catch (DataValidationException $e) {
            $this->assertEquals($expectedMessages, $e->getValidationMessages());
        }
    }
}
