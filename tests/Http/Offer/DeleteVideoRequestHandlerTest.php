<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Offer\Commands\Video\DeleteVideo;
use PHPUnit\Framework\TestCase;

class DeleteVideoRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private DeleteVideoRequestHandler $deleteVideoRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->deleteVideoRequestHandler = new DeleteVideoRequestHandler($this->commandBus);

        $this->commandBus->record();
    }

    /**
     * @dataProvider deleteVideoDataProvider
     * @test
     */
    public function it_handles_deleting_a_video_from_an_offer(string $offerType, DeleteVideo $deleteVideo): void
    {
        $this->deleteVideoRequestHandler->handle(
            (new Psr7RequestBuilder())
                ->withRouteParameter('offerType', $offerType)
                ->withRouteParameter('offerId', $deleteVideo->getItemId())
                ->withRouteParameter('videoId', $deleteVideo->getVideoId())
                ->build('DELETE')
        );

        $this->assertEquals([$deleteVideo], $this->commandBus->getRecordedCommands());
    }

    public function deleteVideoDataProvider(): array
    {
        return [
            'delete_from_event' => [
                'events',
                new DeleteVideo(
                    '609a8214-51c9-48c0-903f-840a4f38852f',
                    'ae526780-6295-4f03-8ebd-c07c1a2857ac'
                ),
            ],
            'delete_from_place' => [
                'places',
                new DeleteVideo(
                    '609a8214-51c9-48c0-903f-840a4f38852f',
                    'ae526780-6295-4f03-8ebd-c07c1a2857ac'
                ),
            ],
        ];
    }
}
