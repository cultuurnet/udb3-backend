<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use PHPUnit\Framework\TestCase;

final class PriceInfoValidatingRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private PriceInfoValidatingRequestBodyParser $priceInfoValidatingRequestBodyParser;

    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->priceInfoValidatingRequestBodyParser = new PriceInfoValidatingRequestBodyParser();
        $this->requestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_handles_priceInfo(): void
    {
        $priceInfo = [
            (object) [
                'category' => 'base',
                'name' => (object) [
                    'nl' => 'Basistarief',
                ],
                'price' => 10,
                'priceCurrency' => 'EUR',
            ],
            (object) [
                'category' => 'tariff',
                'name' => 'Senioren',
                'price' => '100',
                'priceCurrency' => 'USD',
            ],
        ];

        $request = $this->requestBuilder
            ->build('PUT')
            ->withParsedBody($priceInfo);

        $actual =  $this->priceInfoValidatingRequestBodyParser->parse($request)->getParsedBody();

        $this->assertEquals($priceInfo, $actual);
    }

    /**
     * @test
     */
    public function it_throws_on_same_names(): void
    {
        $priceInfo = '{
                       "mainLanguage":"nl",
                       "priceInfo":[
                          {
                             "category":"tariff",
                             "name":{
                                "nl":"Studenten"
                             },
                             "price":100,
                             "priceCurrency":"EUR"
                          },
                          {
                             "category":"tariff",
                             "name":{
                                "nl":"Studenten"
                             },
                             "price":50,
                             "priceCurrency":"EUR"
                          },
                          {
                            "category":"base",
                            "name":{
                               "nl":"Basistarief",
                               "fr":"Tarif de base",
                               "en":"Base tariff",
                               "de":"Basisrate"
                            },
                            "price":200,
                            "priceCurrency":"EUR"
                         },
                         {
                            "category":"tariff",
                            "name":{
                              "nl":"Studenten"
                            },
                            "price":50,
                            "priceCurrency":"EUR"
                         }
                       ]
                    }';

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody(Json::decode($priceInfo));

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/priceInfo/1/name/nl', 'Tariff name "Studenten" should be unique.'),
                new SchemaError('/priceInfo/3/name/nl', 'Tariff name "Studenten" should be unique.')
            ),
            fn () => $this->priceInfoValidatingRequestBodyParser->parse($request)
        );
    }
}
