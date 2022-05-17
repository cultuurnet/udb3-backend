<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
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
                'name' => (object) [
                    'nl' => 'Senioren',
                ],
                'price' => 100,
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
        $priceInfo = (object) [
            'priceInfo' => (object) [
                (object)[
                    'category' => 'tariff',
                    'name' => (object) [
                        'nl' => 'Studenten',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                (object)[
                    'category' => 'base',
                    'name' => (object) [
                        'nl' => 'Basistarief',
                        ],
                    'price' => 50,
                    'priceCurrency' => 'EUR',
                    ],
                (object)[
                    'category' => 'tariff',
                    'name' => (object) [
                        'nl' => 'Studenten',
                        ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                    ],
                (object)[
                    'category' => 'tariff',
                    'name' => (object) [
                        'nl' => 'Leraren',
                    ],
                    'price' => 20,
                ],
                (object)[
                    'category' => 'tariff',
                    'name' => (object) [
                        'nl' => 'Studenten',
                        ],
                    'price' => 10,
                    ],
                ],
            ];

        $request = $this->requestBuilder
            ->build('POST')
            ->withParsedBody($priceInfo);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/priceInfo/2/name/nl', 'Tariff name "Studenten" must be unique.'),
                new SchemaError('/priceInfo/4/name/nl', 'Tariff name "Studenten" must be unique.')
            ),
            fn () => $this->priceInfoValidatingRequestBodyParser->parse($request)
        );
    }
}
