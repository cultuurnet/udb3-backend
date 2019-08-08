<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\PriceInfo;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\PriceInfo\Tariff;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\TestCase;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;

class PriceInfoJSONDeserializerTest extends TestCase
{
    /**
     * @var PriceInfoJSONDeserializer
     */
    private $deserializer;

    public function setUp()
    {
        $this->deserializer = new PriceInfoJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_for_a_different_main_language()
    {
        $mainLanguage = new Language('en');
        $deserializer = $this->deserializer->forMainLanguage($mainLanguage);

        $data = new StringLiteral(
            json_encode(
                [
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
                        'price' => 10,
                        'priceCurrency' => 'EUR',
                    ],
                ]
            )
        );

        try {
            $deserializer->deserialize($data);
            $this->fail('Expected DataValidationException.');
        } catch (DataValidationException $e) {
            $messages = $e->getValidationMessages();
            $expected = ['[1].name' => "Missing translation for mainLanguage 'en'."];
            $this->assertEquals($expected, $messages);
        }
    }

    /**
     * @test
     */
    public function it_should_return_the_same_deserializer_if_the_injected_validator_is_not_mainLanguage_aware()
    {
        $validator = $this->createMock(DataValidatorInterface::class);
        $deserializer = new PriceInfoJSONDeserializer($validator);
        $expected = $deserializer;

        $mainLanguage = new Language('en');
        $actual = $deserializer->forMainLanguage($mainLanguage);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_deserializes_valid_price_info_data()
    {
        $data = new StringLiteral(
            '[
                {"category": "base", "price": 15, "priceCurrency": "EUR"},
                {"category": "tarrif", "name": {"nl": "Werkloze dodo kwekers"}, "price": 0, "priceCurrency": "EUR"}
            ]'
        );

        $basePrice = new BasePrice(
            new Price(1500),
            Currency::fromNative('EUR')
        );

        $tariff = new Tariff(
            new MultilingualString(new Language('nl'), new StringLiteral('Werkloze dodo kwekers')),
            new Price(0),
            Currency::fromNative('EUR')
        );

        $expectedPriceInfo = (new PriceInfo($basePrice))
            ->withExtraTariff($tariff);

        $actualPriceInfo = $this->deserializer->deserialize($data);

        $this->assertEquals($expectedPriceInfo, $actualPriceInfo);
    }
}
