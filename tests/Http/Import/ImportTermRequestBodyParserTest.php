<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Import;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\Import\Place\PlaceCategoryResolver;
use PHPUnit\Framework\TestCase;

final class ImportTermRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private ImportTermRequestBodyParser $importTermRequestBodyParser;

    protected function setUp(): void
    {
        $this->importTermRequestBodyParser = new ImportTermRequestBodyParser(new PlaceCategoryResolver());
    }

    /**
     * @test
     * @dataProvider bodyDataProvider
     */
    public function it_adds_missing_term_fields(object $originalBody, object $expectedBody): void
    {
        $request = (new Psr7RequestBuilder())
            ->withParsedBody($originalBody)
            ->build('POST');

        $this->assertEquals(
            $expectedBody,
            $this->importTermRequestBodyParser->parse($request)->getParsedBody()
        );
    }

    public function bodyDataProvider(): array
    {
        return [
            'body with missing term fields' => [
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                    'terms' => [
                        (object) [
                            'id' => '0.8.0.0.0',
                        ],
                    ],
                ],
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                    'terms' => [
                        (object) [
                            'id' => '0.8.0.0.0',
                            'label' => 'Openbare ruimte',
                            'domain' => 'eventtype',
                        ],
                    ],
                ],
            ],
            'body with no term fields' => [
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                ],
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                ],
            ],
            'body with terms fields without id' => [
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                    'terms' => [
                        (object) [
                            'label' => 'Openbare ruimte',
                            'domain' => 'eventtype',
                        ],
                    ],
                ],
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                    'terms' => [
                        (object) [
                            'label' => 'Openbare ruimte',
                            'domain' => 'eventtype',
                        ],
                    ],
                ],
            ],
            'body with invalid terms id' => [
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                    'terms' => [
                        (object) [
                            'id' => 123,
                            'label' => 'Openbare ruimte',
                            'domain' => 'eventtype',
                        ],
                    ],
                ],
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                    'terms' => [
                        (object) [
                            'id' => 123,
                            'label' => 'Openbare ruimte',
                            'domain' => 'eventtype',
                        ],
                    ],
                ],
            ],
            'body with wrong term fields' => [
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                    'terms' => [
                        (object) [
                            'id' => '0.8.0.0.0',
                            'label' => 'wrong label',
                            'domain' => 'wrong domain',
                        ],
                    ],
                ],
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                    'terms' => [
                        (object) [
                            'id' => '0.8.0.0.0',
                            'label' => 'Openbare ruimte',
                            'domain' => 'eventtype',
                        ],
                    ],
                ],
            ],
            'body with correct term' => [
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                    'terms' => [
                        (object) [
                            'id' => '0.8.0.0.0',
                            'label' => 'Openbare ruimte',
                            'domain' => 'eventtype',
                        ],
                    ],
                ],
                (object) [
                    'name' => (object) [
                        'nl' => 'Cafe Den Hemel',
                    ],
                    'terms' => [
                        (object) [
                            'id' => '0.8.0.0.0',
                            'label' => 'Openbare ruimte',
                            'domain' => 'eventtype',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throws_on_unsupported_term(): void
    {
        $terms = (object) [
            'name' => (object) [
                'nl' => 'Cafe Den Hemel',
            ],
            'terms' => [
                (object) [
                    'id' => '0.7.0.0.0',
                    'label' => 'Begeleide rondleiding',
                    'domain' => 'eventtype',
                ],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withParsedBody($terms)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/terms/0/id',
                    'The term 0.7.0.0.0 does not exist or is not supported'
                )
            ),
            fn () => $this->importTermRequestBodyParser->parse($request)->getParsedBody()
        );
    }

    /**
     * @test
     */
    public function it_throws_on_empty_term(): void
    {
        $terms = (object) [
            'name' => (object) [
                'nl' => 'Cafe Den Hemel',
            ],
            'terms' => [
                (object) [
                    'id' => '',
                    'label' => '',
                    'domain' => 'eventtype',
                ],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withParsedBody($terms)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/terms/0/id',
                    'Category ID should not be empty.'
                )
            ),
            fn () => $this->importTermRequestBodyParser->parse($request)->getParsedBody()
        );
    }
}
