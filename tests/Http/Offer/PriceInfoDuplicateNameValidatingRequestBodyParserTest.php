<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class PriceInfoDuplicateNameValidatingRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private PriceInfoDuplicateNameValidatingRequestBodyParser $priceInfoDuplicateNameValidatingRequestBodyParser;

    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->priceInfoDuplicateNameValidatingRequestBodyParser = new PriceInfoDuplicateNameValidatingRequestBodyParser();
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

        $actual =  $this->priceInfoDuplicateNameValidatingRequestBodyParser->parse($request)->getParsedBody();

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
            fn () => $this->priceInfoDuplicateNameValidatingRequestBodyParser->parse($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_same_names_with_different_spacing(): void
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
                        'nl' => 'Studenten ',
                    ],
                    'price' => 15,
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
                        'nl' => 'Studenten   ',
                    ],
                    'price' => 30,
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
            fn () => $this->priceInfoDuplicateNameValidatingRequestBodyParser->parse($request)
        );
    }
}
