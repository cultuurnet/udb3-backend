<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\DeleteTypicalAgeRange as EventDeleteTypicalAgeRange;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange as PlaceDeleteTypicalAgeRange;
use PHPUnit\Framework\TestCase;

class DeleteTypicalAgeRangeRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';

    private TraceableCommandBus $commandBus;

    private DeleteTypicalAgeRangeRequestHandler $deleteTypicalAgeRangeRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->deleteTypicalAgeRangeRequestHandler = new DeleteTypicalAgeRangeRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_deleting_the_typical_age_range_of_an_offer(
        string $offerType,
        AbstractDeleteTypicalAgeRange $deleteTypicalAgeRange
    ): void {
        $deleteTypicalAgeRangeRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->build('DELETE');

        $response = $this->deleteTypicalAgeRangeRequestHandler->handle($deleteTypicalAgeRangeRequest);

        $this->assertEquals(
            [
                $deleteTypicalAgeRange,
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    public function offerTypeDataProvider(): array
    {
        return [
            [
                'offerType' => 'events',
                'deleteTypicalAgeRange' => new EventDeleteTypicalAgeRange(self::OFFER_ID),
            ],
            [
                'offerType' => 'places',
                'deleteTypicalAgeRange' => new PlaceDeleteTypicalAgeRange(self::OFFER_ID),
            ],
        ];
    }
}
