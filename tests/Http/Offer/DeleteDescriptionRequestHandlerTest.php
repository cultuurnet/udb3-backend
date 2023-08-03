<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\DeleteDescription;
use PHPUnit\Framework\TestCase;

final class DeleteDescriptionRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';

    private TraceableCommandBus $commandBus;

    private DeleteDescriptionRequestHandler $eventRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->eventRequestHandler = new DeleteDescriptionRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @group deleteDescriptionOffer
     * @dataProvider validRequestsProvider
     */
    public function it_handles_deleting_the_description_of_an_offer(
        string $offerType,
        DeleteDescription $command
    ): void {
        $eventRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('language', 'nl')
            ->build('DELETE');

        $response = $this->eventRequestHandler->handle($eventRequest);

        $this->assertEquals(
            [$command],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    public function validRequestsProvider(): array
    {
        return [
            [
                'offerType' => 'events',
                'event' => new DeleteDescription(
                    self::OFFER_ID,
                    new Language('nl'),
                ),
            ],
            [
                'offerType' => 'places',
                'event' => new DeleteDescription(
                    self::OFFER_ID,
                    new Language('nl'),
                ),
            ],
        ];
    }
}
