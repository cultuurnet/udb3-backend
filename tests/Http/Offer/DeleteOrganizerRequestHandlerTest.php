<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\DeleteOrganizer;
use PHPUnit\Framework\TestCase;

final class DeleteOrganizerRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const ORGANIZER_ID = 'd03b2ac9-19b2-46d7-8e59-224e80733163';

    private TraceableCommandBus $commandBus;

    private DeleteOrganizerRequestHandler $deleteOrganizerRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->deleteOrganizerRequestHandler = new DeleteOrganizerRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_deleting_the_organizer_of_an_offer(
        string $offerType,
        DeleteOrganizer $deleteOrganizer
    ): void {
        $deleteOrganizerRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('organizerId', self::ORGANIZER_ID)
            ->build('DELETE');

        $response = $this->deleteOrganizerRequestHandler->handle($deleteOrganizerRequest);

        $this->assertEquals(
            [
                $deleteOrganizer,
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
        $deleteOrganizer = new DeleteOrganizer(
            self::OFFER_ID,
            self::ORGANIZER_ID
        );
        return [
            [
                'offerType' => 'events',
                'deleteOrganizer' => $deleteOrganizer,
            ],
            [
                'offerType' => 'places',
                'deleteOrganizer' => $deleteOrganizer,
            ],
        ];
    }
}
