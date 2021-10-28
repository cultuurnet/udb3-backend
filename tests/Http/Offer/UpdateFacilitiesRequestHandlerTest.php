<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\Commands\UpdateFacilities;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateFacilitiesRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var CommandBus|MockObject */
    private CommandBus $commandBus;
    private UpdateFacilitiesRequestHandler $updateFacilitiesRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->updateFacilitiesRequestHandler = new UpdateFacilitiesRequestHandler($this->commandBus);
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_accepts_a_request_with_json_body_in_the_legacy_format(string $offerType): void
    {
        $json = Json::encode(
            (object) [
                'facilities' => [
                    '3.25.0.0.0',
                    '3.26.0.0.0',
                ],
            ]
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '84dcd140-a33f-4c52-83ad-7500cecb1652')
            ->withBodyFromString($json)
            ->build('PUT');

        $expectedCommand = new UpdateFacilities(
            '84dcd140-a33f-4c52-83ad-7500cecb1652',
            [
                '3.25.0.0.0',
                '3.26.0.0.0',
            ]
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand);

        $this->updateFacilitiesRequestHandler->handle($request);
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_accepts_a_request_with_json_body_in_the_simplified_format(string $offerType): void
    {
        $json = Json::encode(
            [
                '3.23.3.0.0',
                '3.13.0.0.0',
            ]
        );

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '84dcd140-a33f-4c52-83ad-7500cecb1652')
            ->withBodyFromString($json)
            ->build('PUT');

        $expectedCommand = new UpdateFacilities(
            '84dcd140-a33f-4c52-83ad-7500cecb1652',
            [
                '3.23.3.0.0',
                '3.13.0.0.0',
            ]
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand);

        $this->updateFacilitiesRequestHandler->handle($request);
    }

    public function offerTypeDataProvider(): array
    {
        return [
            ['events'],
            ['places'],
        ];
    }
}
