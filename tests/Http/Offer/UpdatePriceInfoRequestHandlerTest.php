<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdatePriceInfoRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var CommandBus&MockObject */
    private $commandBus;

    private UpdatePriceInfoRequestHandler $updatePriceInfoRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->updatePriceInfoRequestHandler = new UpdatePriceInfoRequestHandler($this->commandBus);
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_dispatches_an_update_command_if_no_duplicate_names_are_found(string $offerType): void
    {
        $body = [
            [
                'category' => 'base',
                'name' => [
                    'nl' => 'Basistarief',
                ],
                'price' => 10,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Jongeren',
                ],
                'price' => 5,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Senioren',
                ],
                'price' => 5,
                'priceCurrency' => 'EUR',
            ],
        ];
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($body)
            ->build('PUT');

        $priceInfo = new PriceInfo(
            new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('Basistarief')
                ),
                new Money(1000, new Currency('EUR'))
            ),
            new Tariffs(
                new Tariff(
                    new TranslatedTariffName(
                        new Language('nl'),
                        new TariffName('Jongeren')
                    ),
                    new Money(500, new Currency('EUR'))
                ),
                new Tariff(
                    new TranslatedTariffName(
                        new Language('nl'),
                        new TariffName('Senioren')
                    ),
                    new Money(500, new Currency('EUR'))
                )
            )
        );

        $expected = new UpdatePriceInfo(
            'a91bc028-c44a-4429-9784-8641c9858eed',
            $priceInfo
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expected);

        $this->updatePriceInfoRequestHandler->handle($request);
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_does_not_have_rounding_issues(string $offerType): void
    {
        $body = [
            [
                'category' => 'base',
                'name' => [
                    'nl' => 'Basistarief',
                ],
                'price' => 2.3,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Early birds',
                ],
                'price' => 2.3,
                'priceCurrency' => 'EUR',
            ],
        ];
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($body)
            ->build('PUT');

        $priceInfo = new PriceInfo(
            new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('Basistarief')
                ),
                new Money(230, new Currency('EUR'))
            ),
            new Tariffs(
                new Tariff(
                    new TranslatedTariffName(
                        new Language('nl'),
                        new TariffName('Early birds')
                    ),
                    new Money(230, new Currency('EUR'))
                ),
            )
        );

        $expected = new UpdatePriceInfo(
            'a91bc028-c44a-4429-9784-8641c9858eed',
            $priceInfo
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expected);

        $this->updatePriceInfoRequestHandler->handle($request);
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_supports_group_prices(string $offerType): void
    {
        $body = [
            [
                'category' => 'base',
                'name' => [
                    'nl' => 'Basistarief',
                ],
                'price' => 250,
                'priceCurrency' => 'EUR',
                'groupPrice' => true,
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Individuen',
                ],
                'price' => 15,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Leraren',
                ],
                'price' => 100,
                'priceCurrency' => 'EUR',
                'groupPrice' => true,
            ]
        ];
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($body)
            ->build('PUT');

        $priceInfo = new PriceInfo(
            (new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('Basistarief')
                ),
                new Money(25000, new Currency('EUR'))
            ))->withGroupPrice(true),
            new Tariffs(
                new Tariff(
                    new TranslatedTariffName(
                        new Language('nl'),
                        new TariffName('Individuen')
                    ),
                    new Money(1500, new Currency('EUR'))
                ),
                (new Tariff(
                    new TranslatedTariffName(
                        new Language('nl'),
                        new TariffName('Leraren')
                    ),
                    new Money(10000, new Currency('EUR'))
                ))->withGroupPrice(true),
            )
        );

        $expected = new UpdatePriceInfo(
            'a91bc028-c44a-4429-9784-8641c9858eed',
            $priceInfo
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expected);

        $this->updatePriceInfoRequestHandler->handle($request);
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_an_api_problem_if_the_price_info_contains_duplicate_names(string $offerType): void
    {
        $body = [
            [
                'category' => 'base',
                'name' => [
                    'nl' => 'Basistarief',
                ],
                'price' => 10,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Studenten',
                ],
                'price' => 5,
                'priceCurrency' => 'EUR',
            ],
            [
                'category' => 'tariff',
                'name' => [
                    'nl' => 'Studenten',
                ],
                'price' => 2,
                'priceCurrency' => 'EUR',
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($body)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/priceInfo/2/name/nl', 'Tariff name "Studenten" must be unique.')
            ),
            fn () => $this->updatePriceInfoRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_should_throw_an_exception_if_a_category_is_missing(string $offerType): void
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

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($data)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'At most 1 array items must match schema')
            ),
            fn () => $this->updatePriceInfoRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_should_throw_an_exception_if_a_tariff_name_is_missing(string $offerType): void
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

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($data)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/1', 'The required properties (name) are missing')
            ),
            fn () => $this->updatePriceInfoRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_should_throw_an_exception_if_a_tariff_name_is_malformed(string $offerType): void
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

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($data)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/1/name', 'The data (string) must match the type: object')
            ),
            fn () => $this->updatePriceInfoRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_should_throw_an_exception_if_a_tariff_name_has_an_invalid_value(string $offerType): void
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

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($data)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/1/name/nl', 'The data (integer) must match the type: string')
            ),
            fn () => $this->updatePriceInfoRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_should_throw_an_exception_if_a_tariff_price_is_missing(string $offerType): void
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

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($data)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/0', 'The required properties (price) are missing'),
                new SchemaError('/1', 'The required properties (price) are missing')
            ),
            fn () => $this->updatePriceInfoRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_should_throw_an_exception_if_a_tariff_price_is_not_a_number(string $offerType): void
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

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($data)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/1/price', 'The data (string) must match the type: number')
            ),
            fn () => $this->updatePriceInfoRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_should_throw_an_exception_if_multiple_base_tariffs_are_found(string $offerType): void
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

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($data)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'At most 1 array items must match schema')
            ),
            fn () => $this->updatePriceInfoRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_should_throw_an_exception_if_no_tariffs_with_category_base_are_found(string $offerType): void
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

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withJsonBodyFromArray($data)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'At least 1 array items must match schema')
            ),
            fn () => $this->updatePriceInfoRequestHandler->handle($request)
        );
    }

    public function offerTypeDataProvider(): array
    {
        return [
            ['events'],
            ['places'],
        ];
    }
}
