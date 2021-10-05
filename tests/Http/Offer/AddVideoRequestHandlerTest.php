<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\Video\AddVideo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidFactoryInterface;

class AddVideoRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    /**
     * @var MockObject|UuidFactoryInterface
     */
    private $uuidFactory;

    private AddVideoRequestHandler $addVideoRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->uuidFactory = $this->createMock(UuidFactoryInterface::class);

        $this->addVideoRequestHandler = new AddVideoRequestHandler(
            $this->commandBus,
            $this->uuidFactory
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_allows_adding_a_video_with_copyright_holder(): void
    {
        $addVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{"url":"https://www.youtube.com/?v=sdsd234", "copyrightHolder":"publiq"}')
            ->build('POST');

        $videoId = \Ramsey\Uuid\Uuid::uuid4();
        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn($videoId);

        $this->addVideoRequestHandler->handle($addVideoRequest);

        $this->assertEquals(
            [
                new AddVideo(
                    new UUID('609a8214-51c9-48c0-903f-840a4f38852f'),
                    (new Video(
                        new UUID($videoId->toString()),
                        new Url('https://www.youtube.com/?v=sdsd234')
                    ))->withCopyrightHolder(new CopyrightHolder('publiq'))
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_allows_adding_a_video_without_copyright_holder(): void
    {
        $addVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{"url":"https://www.youtube.com/?v=sdsd234"}')
            ->build('POST');

        $videoId = \Ramsey\Uuid\Uuid::uuid4();
        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn($videoId);

        $this->addVideoRequestHandler->handle($addVideoRequest);

        $this->assertEquals(
            [
                new AddVideo(
                    new UUID('609a8214-51c9-48c0-903f-840a4f38852f'),
                    new Video(new UUID($videoId->toString()), new Url('https://www.youtube.com/?v=sdsd234'))
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
