<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\PriceInfo;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use PHPUnit\Framework\TestCase;

class PriceInfoDataValidatorTest extends TestCase
{
    /**
     * @var PriceInfoDataValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new PriceInfoDataValidator();
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_category_is_missing()
    {
        $data = [
            [
                'category' => 'base',
                'price' => 15,
                'priceCurrency' => 'EUR',
            ],
            [
                'name' => [
                    'nl' => 'Senioren',
                ],
                'price' => 10.5,
                'priceCurrency' => 'EUR',
            ],
        ];

        $expectedMessages = [
            '[1].category' => 'Required but not found.',
        ];

        $this->assertDataValidationMessages($this->validator, $data, $expectedMessages);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_tariff_name_is_missing()
    {
        $data = [
            [
                'category' => 'base',
                'price' => 15,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'price' => 10.5,
                'priceCurrency' => 'EUR',
            ],
        ];

        $expectedMessages = [
            '[1].name' => 'Required but not found.',
        ];

        $this->assertDataValidationMessages($this->validator, $data, $expectedMessages);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_tariff_name_is_malformed()
    {
        $data = [
            [
                'category' => 'base',
                'price' => 15,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => 'Senioren',
                'price' => 10.5,
                'priceCurrency' => 'EUR',
            ],
        ];

        $expectedMessages = [
            '[1].name' => 'Name must be an associative array with language keys and translations.',
        ];

        $this->assertDataValidationMessages($this->validator, $data, $expectedMessages);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_tariff_name_has_an_invalid_language_code()
    {
        $data = [
            [
                'category' => 'base',
                'price' => 15,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'foo' => 'Senioren',
                ],
                'price' => 10.5,
                'priceCurrency' => 'EUR',
            ],
        ];

        $expectedMessages = [
            '[1].name.foo' => 'Invalid language code.',
        ];

        $this->assertDataValidationMessages($this->validator, $data, $expectedMessages);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_tariff_name_has_an_invalid_value()
    {
        $data = [
            [
                'category' => 'base',
                'price' => 15,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 1000,
                ],
                'price' => 10.5,
                'priceCurrency' => 'EUR',
            ],
        ];

        $expectedMessages = [
            '[1].name.nl' => 'Name translation must be a string.',
        ];

        $this->assertDataValidationMessages($this->validator, $data, $expectedMessages);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_tariff_name_is_missing_a_translation_for_the_main_language()
    {
        $mainLanguage = new Language('en');
        $validator = $this->validator->forMainLanguage($mainLanguage);

        $data = [
            [
                'category' => 'base',
                'price' => 15,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Senioren',
                ],
                'price' => 10.5,
                'priceCurrency' => 'EUR',
            ],
        ];

        $expectedMessages = [
            '[1].name' => "Missing translation for mainLanguage 'en'.",
        ];

        $this->assertDataValidationMessages($validator, $data, $expectedMessages);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_tariff_price_is_missing()
    {
        $data = [
            [
                'category' => 'base',
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Senioren',
                ],
                'priceCurrency' => 'EUR',
            ],
        ];

        $expectedMessages = [
            '[0].price' => 'Required but not found.',
            '[1].price' => 'Required but not found.',
        ];

        $this->assertDataValidationMessages($this->validator, $data, $expectedMessages);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_tariff_price_is_not_a_number()
    {
        $data = [
            [
                'category' => 'base',
                'price' => 15,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Senioren',
                ],
                'price' => 'foo',
                'priceCurrency' => 'EUR',
            ],
        ];

        $expectedMessages = [
            '[1].price' => 'Price must have a numeric value.',
        ];

        $this->assertDataValidationMessages($this->validator, $data, $expectedMessages);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_multiple_base_tariffs_are_found()
    {
        $data = [
            [
                'category' => 'base',
                'price' => 15,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Senioren',
                ],
                'price' => 10.5,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'base',
                'price' => 20,
                'priceCurrency' => 'EUR',
            ],
        ];

        $expectedMessages = [
            '[2].category' => "Exactly one entry with category 'base' allowed but found a duplicate.",
        ];

        $this->assertDataValidationMessages($this->validator, $data, $expectedMessages);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_no_tariffs_with_category_base_are_found()
    {
        $data = [
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Senioren',
                ],
                'price' => 10.5,
                'priceCurrency' => 'EUR',
            ],
        ];

        $expectedMessages = [
            '[].category' => "Exactly one entry with category 'base' required but none found.",
        ];

        $this->assertDataValidationMessages($this->validator, $data, $expectedMessages);
    }


    private function assertDataValidationMessages(DataValidatorInterface $validator, $data, array $expectedMessages)
    {
        try {
            $validator->validate($data);
            $messages = [];
        } catch (DataValidationException $e) {
            $messages = $e->getValidationMessages();
        }

        $this->assertEquals($expectedMessages, $messages);
    }
}
