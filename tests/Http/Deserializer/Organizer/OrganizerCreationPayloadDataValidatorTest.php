<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Organizer;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use PHPUnit\Framework\TestCase;

class OrganizerCreationPayloadDataValidatorTest extends TestCase
{
    /**
     * @var OrganizerCreationPayloadDataValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new OrganizerCreationPayloadDataValidator();
    }

    /**
     * @test
     */
    public function it_throws_an_exception_for_multiple_errors_at_once()
    {
        $data = [
            'name' => '',
            'website' => 'not-a-url',
            'address' => [
                'streetAddress' => 'Martelarenplein 12',
                'postalCode' => 3000,
                'addressLocality' => 'Leuven',
            ],
            'contact' => [
                [
                    'value' => 'foo',
                    'type' => 'not-a-valid-type',
                ],
            ],
        ];

        $expectedMessages = [
            'name' => 'Title can not be empty.',
            'website' => 'Not a valid url.',
            'address.addressCountry' => 'Should not be empty.',
            'contact.0.type' => 'Invalid type. Allowed types are: url, phone, email.',
        ];

        try {
            $this->validator->validate($data);
            $this->fail('Did not catch expected DataValidationException');
        } catch (DataValidationException $e) {
            $this->assertEquals($expectedMessages, $e->getValidationMessages());
        }
    }
}
