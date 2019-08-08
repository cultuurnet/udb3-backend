<?php

namespace CultuurNet\UDB3\Http\Deserializer\BookingInfo;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use PHPUnit\Framework\TestCase;

class BookingInfoDataValidatorTest extends TestCase
{
    /**
     * @var DataValidatorInterface
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new BookingInfoDataValidator();
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_is_missing()
    {
        $given = [
            'url' => 'https://publiq.be',
            'urlLabel' => ['nl' => 'Publiq vzw'],
            'phone' => '044/444444',
            'email' => 'info@publiq.be',
            'availabilityStarts' => '2018-01-01T00:00:00+01:00',
            'availabilityEnds' => '2018-01-31T23:59:59+01:00',
        ];

        $expected = [
            'bookingInfo' => 'Required but could not be found.',
        ];

        $this->assertValidationMessages($given, $expected);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_availabilityStarts_or_availabilityEnds_is_in_an_invalid_format()
    {
        $given = [
            'bookingInfo' => [
                'url' => 'https://publiq.be',
                'urlLabel' => ['nl' => 'Publiq vzw'],
                'phone' => '044/444444',
                'email' => 'info@publiq.be',
                'availabilityStarts' => '2018-01-01T00:00:00.234Z',
                'availabilityEnds' => '2018-01-31T23:59:59.234Z',
            ],
        ];

        $expected = [
            'bookingInfo.availabilityStarts' => 'Invalid format. Expected ISO-8601 (eg. 2018-01-01T00:00:00+01:00).',
            'bookingInfo.availabilityEnds' => 'Invalid format. Expected ISO-8601 (eg. 2018-01-01T00:00:00+01:00).',
        ];

        $this->assertValidationMessages($given, $expected);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_urlLabel_is_a_string()
    {
        $given = [
            'bookingInfo' => [
                'url' => 'https://publiq.be',
                'urlLabel' => 'Publiq vzw',
                'phone' => '044/444444',
                'email' => 'info@publiq.be',
                'availabilityStarts' => '2018-01-01T00:00:00+01:00',
                'availabilityEnds' => '2018-01-31T23:59:59+01:00',
            ],
        ];

        $expected = [
            'bookingInfo.urlLabel' => 'Invalid format. ' .
                'Expected associative array with language codes as keys and translated strings as values.',
        ];

        $this->assertValidationMessages($given, $expected);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_urlLabel_language_code_is_invalid()
    {
        $given = [
            'bookingInfo' => [
                'url' => 'https://publiq.be',
                'urlLabel' => ['foo' => 'Publiq vzw'],
                'phone' => '044/444444',
                'email' => 'info@publiq.be',
                'availabilityStarts' => '2018-01-01T00:00:00+01:00',
                'availabilityEnds' => '2018-01-31T23:59:59+01:00',
            ],
        ];

        $expected = [
            'bookingInfo.urlLabel' => 'Invalid format. ' .
                'Expected associative array with language codes as keys and translated strings as values.',
        ];

        $this->assertValidationMessages($given, $expected);
    }

    /**
     * @test
     */
    public function it_should_pass_if_all_properties_are_valid()
    {
        $given = [
            'bookingInfo' => [
                'url' => 'https://publiq.be',
                'urlLabel' => ['nl' => 'Publiq vzw'],
                'phone' => '044/444444',
                'email' => 'info@publiq.be',
                'availabilityStarts' => '2018-01-01T00:00:00+01:00',
                'availabilityEnds' => '2018-01-31T23:59:59+01:00',
            ],
        ];

        $this->validator->validate($given);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @param array $data
     * @param array $expectedMessages
     */
    private function assertValidationMessages(array $data, array $expectedMessages)
    {
        try {
            $this->validator->validate($data);
            $messages = [];
        } catch (DataValidationException $e) {
            $messages = $e->getValidationMessages();
        }

        $this->assertEquals($expectedMessages, $messages);
    }
}
