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
