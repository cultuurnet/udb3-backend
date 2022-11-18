<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateTypicalAgeRange as EventUpdateTypicalAgeRange;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\UpdateTypicalAgeRange as PlaceUpdateTypicalAgeRange;
use Iterator;
use PHPUnit\Framework\TestCase;

final class UpdateTypicalAgeRangeRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';

    private TraceableCommandBus $commandBus;

    private UpdateTypicalAgeRangeRequestHandler $updateTypicalAgeRangeRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateTypicalAgeRangeRequestHandler = new UpdateTypicalAgeRangeRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_updating_the_typical_age_range_of_an_offer(
        string $offerType,
        string $request,
        AbstractUpdateTypicalAgeRange $updateTypicalAgeRange
    ): void {
        $updateTypicalAgeRangeRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withBodyFromString($request)
            ->build('PUT');

        $response = $this->updateTypicalAgeRangeRequestHandler->handle($updateTypicalAgeRangeRequest);

        $this->assertEquals([$updateTypicalAgeRange], $this->commandBus->getRecordedCommands());

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    public function offerTypeDataProvider(): Iterator
    {
        $offers = [
            'events' => EventUpdateTypicalAgeRange::class,
            'places' => PlaceUpdateTypicalAgeRange::class,
        ];

        foreach ($offers as $offerType => $offerCommand) {
            yield 'min and max age are filled in  ' . $offerType => [
                'offerType' => $offerType,
                'request' => '{ "typicalAgeRange": "1-12" }',
                'updateTypicalAgeRange' => new $offerCommand(
                    self::OFFER_ID,
                    '1-12'
                ),
            ];
        }
    }
}
