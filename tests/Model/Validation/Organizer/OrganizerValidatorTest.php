<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\Organizer;

use CultuurNet\UDB3\Model\Validation\ValidatorTestCase;
use Respect\Validation\Validator;

class OrganizerValidatorTest extends ValidatorTestCase
{
    private OrganizerValidator $validator;

    public function setUp(): void
    {
        $this->validator = new OrganizerValidator();
    }

    protected function getValidator(): Validator
    {
        return $this->validator;
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
}
