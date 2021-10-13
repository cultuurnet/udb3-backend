<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Offer\Commands\Video\DeleteVideo;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteVideoRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    /**
     * @var DocumentRepository|MockObject
     */
    private $eventDocumentRepository;

    /**
     * @var DocumentRepository|MockObject
     */
    private $placeDocumentRepository;

    private DeleteVideoRequestHandler $deleteVideoRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->eventDocumentRepository = $this->createMock(DocumentRepository::class);

        $this->placeDocumentRepository = $this->createMock(DocumentRepository::class);

        $this->deleteVideoRequestHandler = new DeleteVideoRequestHandler(
            $this->commandBus,
            new OfferJsonDocumentReadRepository(
                $this->eventDocumentRepository,
                $this->placeDocumentRepository
            )
        );

        $this->commandBus->record();
    }

    /**
     * @dataProvider deleteVideoDataProvider
     * @test
     */
    public function it_handles_deleting_a_video_from_an_offer(string $offerType, DeleteVideo $deleteVideo): void
    {
        $this->eventDocumentRepository
            ->method('fetch')
            ->willReturn(new JsonDocument($deleteVideo->getItemId()));

        $this->placeDocumentRepository
            ->method('fetch')
            ->willReturn(new JsonDocument($deleteVideo->getItemId()));

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

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_an_event_is_not_found(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withRouteParameter('videoId', 'ae526780-6295-4f03-8ebd-c07c1a2857ac')
            ->build('DELETE');

        $this->eventDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('609a8214-51c9-48c0-903f-840a4f38852f', false)
            ->willThrowException(new DocumentDoesNotExist());

        $this->assertCallableThrowsApiProblem(
            ApiProblem::eventNotFound('609a8214-51c9-48c0-903f-840a4f38852f'),
            fn () => $this->deleteVideoRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_a_place_is_not_found(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'places')
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withRouteParameter('videoId', 'ae526780-6295-4f03-8ebd-c07c1a2857ac')
            ->build('DELETE');

        $this->placeDocumentRepository->expects($this->once())
            ->method('fetch')
            ->with('609a8214-51c9-48c0-903f-840a4f38852f', false)
            ->willThrowException(new DocumentDoesNotExist());

        $this->assertCallableThrowsApiProblem(
            ApiProblem::placeNotFound('609a8214-51c9-48c0-903f-840a4f38852f'),
            fn () => $this->deleteVideoRequestHandler->handle($request)
        );
    }
}
