<?php

namespace CultuurNet\UDB3\Model\Validation\Organizer;

use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class OrganizerValidatorTest extends TestCase
{
    /**
     * @var OrganizerValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new OrganizerValidator();
    }

    /**
     * @test
     */
    public function it_should_pass_if_all_required_properties_are_present_in_a_valid_format()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_required_property_is_missing()
    {
        $organizer = [];

        $expectedErrors = [
            'Key @id must be present',
            'Key mainLanguage must be present',
            'Key name must be present',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_url_is_required_and_missing()
    {
        $validator = new OrganizerValidator([], true);

        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
        ];

        $expectedErrors = [
            'Key url must be present',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors, $validator);
    }

    /**
     * @test
     */
    public function it_should_pass_if_url_is_required_and_present()
    {
        $validator = new OrganizerValidator([], true);

        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.google.com',
        ];

        $this->assertTrue($validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_id_is_in_an_invalid_format()
    {
        $organizer = [
            '@id' => 'http://io.uitdatabank.be/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
        ];

        // @codingStandardsIgnoreStart
        $expectedErrors = [
            '@id must validate against "/\\\/organizer[s]?\\\/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12})[\\\/]?/"',
        ];
        // @codingStandardsIgnoreEnd

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_mainLanguage_is_in_an_invalid_format()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'foo',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
        ];

        $expectedErrors = [
            'mainLanguage must validate against "/^[a-z]{2}$/"',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_name_has_no_entries()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [],
            'url' => 'https://www.publiq.be',
        ];

        $expectedErrors = [
            'name must have a length greater than 1',
            'name must have a value for the mainLanguage (nl)',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_name_translation_is_empty()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => '',
            ],
            'url' => 'https://www.publiq.be',
        ];

        $expectedErrors = [
            'name value must not be empty',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_name_translation_has_an_invalid_language()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
                'foo' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
        ];

        $expectedErrors = [
            '"foo" must validate against "/^[a-z]{2}$/"',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_name_is_a_string()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => 'Publiq vzw',
            'url' => 'https://www.publiq.be',
        ];

        $expectedErrors = [
            'name must be of the type array',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_url_is_in_an_invalid_format()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'info@publiq.be',
        ];

        $expectedErrors = [
            'url must be a URL',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_url_is_a_valid_url()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_labels_is_set_but_not_an_array()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'labels' => 'foo,bar',
        ];

        $expectedErrors = [
            'labels must be of the type array',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_labels_is_set_but_contains_something_different_than_a_string()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'labels' => [
                ['name' => 'foo', 'visible' => true],
            ],
        ];

        $expectedErrors = [
            'each label must be a string',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_labels_is_an_array_of_strings()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'labels' => [
                'foo',
                'bar',
            ],
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_hiddenLabels_is_set_but_not_an_array()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'hiddenLabels' => 'foo,bar',
        ];

        $expectedErrors = [
            'hiddenLabels must be of the type array',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_hiddenLabels_is_set_but_contains_something_different_than_a_string()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'hiddenLabels' => [
                ['name' => 'foo', 'visible' => true],
            ],
        ];

        $expectedErrors = [
            'each label must be a string',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_hiddenLabels_is_an_array_of_strings()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'hiddenLabels' => [
                'foo',
                'bar',
            ],
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_address_has_no_entries()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'address' => [],
        ];

        $expectedErrors = [
            'address must have a length greater than 1',
            'address must have a value for the mainLanguage (nl)',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_an_address_translation_is_missing_fields()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'address' => [
                'nl' => [],
            ],
        ];

        $expectedErrors = [
            'All of the required rules must pass for address value',
            'Key streetAddress must be present',
            'Key postalCode must be present',
            'Key addressLocality must be present',
            'Key addressCountry must be present',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_address_translation_has_an_invalid_language()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
                'foo' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $expectedErrors = [
            '"foo" must validate against "/^[a-z]{2}$/"',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_address_is_in_a_valid_format()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_invalid_phone()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                    '',
                ],
                'email' => [
                    'info@publiq.be',
                    'foo@publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            'each phone must not be empty',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_invalid_email()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                ],
                'email' => [
                    'info@publiq.be',
                    'publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            'each email must be valid email',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_invalid_url()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                ],
                'email' => [
                    'info@publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            'each url must be a URL',
        ];

        $this->assertValidationErrors($organizer, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_contactPoint_has_valid_phone_numbers()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                    '02/551 18 70',
                ],
            ],
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_pass_if_contactPoint_has_valid_email_addresses()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'contactPoint' => [
                'email' => [
                    'info@publiq.be',
                    'foo@publiq.be',
                ],
            ],
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_pass_if_contactPoint_has_valid_urls()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'contactPoint' => [
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }

    /**
     * @test
     */
    public function it_should_pass_if_contactPoint_has_multiple_valid_properties()
    {
        $organizer = [
            '@id' => 'https://io.uitdatabank.be/organizers/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Publiq vzw',
            ],
            'url' => 'https://www.publiq.be',
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                    '02/551 18 70',
                ],
                'email' => [
                    'info@publiq.be',
                    'foo@publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $this->assertTrue($this->validator->validate($organizer));
    }


    private function assertValidationErrors($data, array $expectedMessages, Validator $validator = null)
    {
        $validator = $validator ? $validator : $this->getValidator();

        try {
            $validator->assert($data);
            $this->fail('No error messages found.');
        } catch (NestedValidationException $e) {
            $actualMessages = $e->getMessages();
            $this->assertEquals($expectedMessages, $actualMessages);
        }
    }

    /**
     * @return Validator
     */
    private function getValidator()
    {
        return $this->validator;
    }
}
