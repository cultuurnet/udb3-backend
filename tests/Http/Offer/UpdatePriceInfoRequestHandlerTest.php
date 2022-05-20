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
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withBodyFromString('[{"category":"base","name":{"nl":"Basistarief"},"price":10,"priceCurrency":"EUR"},{"category":"tariff","name":{"nl":"Jongeren"},"price":5,"priceCurrency":"EUR"},{"category":"tariff","name":{"nl":"Senioren"},"price":5,"priceCurrency":"EUR"}]')
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

        if ($offerType === 'events') {
            $expected = new UpdatePriceInfo(
                'a91bc028-c44a-4429-9784-8641c9858eed',
                $priceInfo
            );
        } else {
            $expected = new UpdatePriceInfo(
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
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'a91bc028-c44a-4429-9784-8641c9858eed')
            ->withBodyFromString('[{"category":"base","name":{"nl":"Basistarief"},"price":10,"priceCurrency":"EUR"},{"category":"tariff","name":{"nl":"Studenten"},"price":5,"priceCurrency":"EUR"},{"category":"tariff","name":{"nl":"Studenten"},"price":5,"priceCurrency":"EUR"}]')
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
