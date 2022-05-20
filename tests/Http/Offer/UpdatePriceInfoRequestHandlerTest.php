<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdatePriceInfo as EventUpdatePriceInfo;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Place\Commands\UpdatePriceInfo as PlaceUpdatePriceInfo;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\PriceInfo\Tariff;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdatePriceInfoRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var CommandBus|MockObject */
    private $commandBus;

    private UpdatePriceInfoRequestHandler $updatePriceInfoRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

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

        $priceInfo = (new PriceInfo(
            new BasePrice(
                new Money(
                    1000,
                    new Currency('EUR')
                )
            )
        ))->withExtraTariff(
            new Tariff(
                new MultilingualString(
                    new Language('nl'),
                    new StringLiteral('Jongeren')
                ),
                new Money(500, new Currency('EUR'))
            )
        )->withExtraTariff(
            new Tariff(
                new MultilingualString(
                    new Language('nl'),
                    new StringLiteral('Senioren')
                ),
                new Money(500, new Currency('EUR'))
            )
        );

        if ($offerType === 'events') {
            $expected = new EventUpdatePriceInfo(
                'a91bc028-c44a-4429-9784-8641c9858eed',
                $priceInfo
            );
        } else {
            $expected = new PlaceUpdatePriceInfo(
                'a91bc028-c44a-4429-9784-8641c9858eed',
                $priceInfo
            );
        }

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

    public function offerTypeDataProvider(): array
    {
        return [
            ['events'],
            ['places'],
        ];
    }
}
