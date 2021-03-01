<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Translation;

use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validatable;

class HasMainLanguageRuleTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_not_throw_an_exception_if_the_main_language_is_not_set()
    {
        $rule = new HasMainLanguageRule('name');

        $input = [
            'name' => [
                'nl' => 'foo',
            ],
        ];

        $this->assertValid($rule, $input);
    }

    /**
     * @test
     */
    public function it_should_not_throw_an_exception_if_the_input_is_an_object_instead_of_an_array()
    {
        $rule = new HasMainLanguageRule('name');

        $input = (object) [
            'mainLanguage' => 'nl',
            'name' => (object) [
                'nl' => 'foo',
            ],
        ];

        $this->assertValid($rule, $input);
    }

    /**
     * @test
     */
    public function it_should_not_throw_an_exception_if_the_property_has_a_value_for_the_main_language()
    {
        $rule = new HasMainLanguageRule('name');

        $input = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'foo',
            ],
        ];

        $this->assertValid($rule, $input);
    }

    /**
     * @test
     */
    public function it_should_not_throw_an_exception_if_the_nested_property_has_a_value_for_the_main_language()
    {
        $rule = new HasMainLanguageRule('bookingInfo.urlLabel');

        $input = [
            'mainLanguage' => 'nl',
            'bookingInfo' => [
                'urlLabel' => [
                    'nl' => 'foo',
                ],
            ],
        ];

        $this->assertValid($rule, $input);
    }

    /**
     * @test
     */
    public function it_should_not_throw_an_exception_if_the_nested_array_item_has_a_value_for_the_main_language()
    {
        $rule = new HasMainLanguageRule('priceInfo.[].name');

        $input = [
            'mainLanguage' => 'nl',
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => ['nl' => 'Basistarief'],
                ],
                [
                    'category' => 'tariff',
                    'name' => ['nl' => 'Senioren'],
                ],
            ],
        ];

        $this->assertValid($rule, $input);
    }

    /**
     * @test
     */
    public function it_should_not_throw_an_exception_if_the_property_does_not_exist()
    {
        $rule = new HasMainLanguageRule('name');

        $input = [
            'mainLanguage' => 'nl',
        ];

        $this->assertValid($rule, $input);
    }

    /**
     * @test
     */
    public function it_should_not_throw_an_exception_if_a_nested_property_does_not_exist()
    {
        $rule = new HasMainLanguageRule('bookingInfo.urlLabel');

        $input = [
            'mainLanguage' => 'nl',
            'bookingInfo' => [
                'url' => 'https://www.publiq.be',
            ],
        ];

        $this->assertValid($rule, $input);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_main_language_is_missing_on_the_property()
    {
        $rule = new HasMainLanguageRule('name');

        $input = [
            'mainLanguage' => 'fr',
            'name' => [
                'nl' => 'foo',
            ],
        ];

        $this->assertValidationException(
            $rule,
            $input,
            ['name must have a value for the mainLanguage (fr)']
        );
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_main_language_is_missing_on_the_nested_property()
    {
        $rule = new HasMainLanguageRule('bookingInfo.urlLabel');

        $input = [
            'mainLanguage' => 'fr',
            'bookingInfo' => [
                'urlLabel' => [
                    'nl' => 'Foo',
                ],
            ],
        ];

        $this->assertValidationException(
            $rule,
            $input,
            ['bookingInfo.urlLabel must have a value for the mainLanguage (fr)']
        );
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_the_nested_array_item_does_not_have_a_value_for_the_main_language()
    {
        $rule = new HasMainLanguageRule('priceInfo.[].name');

        $input = [
            'mainLanguage' => 'en',
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => ['nl' => 'Basistarief'],
                ],
                [
                    'category' => 'tariff',
                    'name' => ['en' => 'Example tariff'],
                ],
                [
                    'category' => 'tariff',
                    'name' => ['nl' => 'Senioren'],
                ],
            ],
        ];

        $this->assertValidationException(
            $rule,
            $input,
            [
                'priceInfo[0].name must have a value for the mainLanguage (en)',
                'priceInfo[2].name must have a value for the mainLanguage (en)',
            ]
        );
    }

    /**
     * @test
     */
    public function it_should_have_a_customizable_name_and_template()
    {
        $rule = new HasMainLanguageRule('name');
        $rule->setName('mockName');
        $rule->setTemplate('mainLanguage {{mainLanguage}} required for {{name}}');

        $input = [
            'mainLanguage' => 'fr',
            'name' => [
                'nl' => 'foo',
            ],
        ];

        $this->assertValidationException(
            $rule,
            $input,
            ['mainLanguage fr required for mockName']
        );
    }


    private function assertValid(Validatable $rule, $input)
    {
        $rule->assert($input);
        $rule->check($input);
        $this->assertTrue($rule->validate($input));
    }


    private function assertValidationException(Validatable $rule, $input, array $expectedMessages)
    {
        try {
            $rule->assert($input);
            $assertedMessages = [];
        } catch (NestedValidationException $e) {
            $assertedMessages = $e->getMessages();
        }

        /* @var NestedValidationException $reportedException */
        $reportedException = $rule->reportError($input);
        $reportedMessages = $reportedException->getMessages();

        $this->assertEquals($expectedMessages, $assertedMessages);
        $this->assertEquals($expectedMessages, $reportedMessages);
    }
}
