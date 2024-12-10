<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
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
     * @var UuidFactoryInterface&MockObject
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
     * @dataProvider offerTypeDataProvider
     */
    public function it_allows_adding_a_video_with_copyright_holder(string $offerType): void
    {
        $addVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString(
                '{"url":"https://www.youtube.com/watch?v=sdsd234", "copyrightHolder":"publiq", "language": "nl"}'
            )
            ->build('POST');

        $videoId = Uuid::uuid4();
        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn($videoId);

        $this->addVideoRequestHandler->handle($addVideoRequest);

        $this->assertEquals(
            [
                new AddVideo(
                    '609a8214-51c9-48c0-903f-840a4f38852f',
                    (new Video(
                        $videoId->toString(),
                        new Url('https://www.youtube.com/watch?v=sdsd234'),
                        new Language('nl')
                    ))->withCopyrightHolder(new CopyrightHolder('publiq'))
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_allows_adding_a_video_without_copyright_holder(string $offerType): void
    {
        $addVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{"url":"https://www.youtube.com/watch?v=sdsd234", "language":"nl"}')
            ->build('POST');

        $videoId = Uuid::uuid4();
        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn($videoId);

        $this->addVideoRequestHandler->handle($addVideoRequest);

        $this->assertEquals(
            [
                new AddVideo(
                    '609a8214-51c9-48c0-903f-840a4f38852f',
                    new Video(
                        $videoId->toString(),
                        new Url('https://www.youtube.com/watch?v=sdsd234'),
                        new Language('nl')
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_allows_adding_a_shortened_youtube_url(string $offerType): void
    {
        $addVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '91c75325-3830-4000-b580-5778b2de4548')
            ->withBodyFromString('{"url":"https://youtu.be/bsaAOun-dec", "language":"nl"}')
            ->build('POST');

        $videoId = Uuid::uuid4();
        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn($videoId);

        $this->addVideoRequestHandler->handle($addVideoRequest);

        $this->assertEquals(
            [
                new AddVideo(
                    '91c75325-3830-4000-b580-5778b2de4548',
                    new Video(
                        $videoId->toString(),
                        new Url('https://youtu.be/bsaAOun-dec'),
                        new Language('nl')
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_allows_adding_an_embedded_youtube_url(string $offerType): void
    {
        $addVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '91c75325-3830-4000-b580-5778b2de4548')
            ->withBodyFromString('{"url":"https://www.youtube.com/embed/yi_XlQetN28", "language":"nl"}')
            ->build('POST');

        $videoId = Uuid::uuid4();
        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn($videoId);

        $this->addVideoRequestHandler->handle($addVideoRequest);

        $this->assertEquals(
            [
                new AddVideo(
                    '91c75325-3830-4000-b580-5778b2de4548',
                    new Video(
                        $videoId->toString(),
                        new Url('https://www.youtube.com/embed/yi_XlQetN28'),
                        new Language('nl')
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_requires_a_url(string $offerType): void
    {
        $addVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{"language":"nl", "copyrightHolder":"publiq"}')
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'The required properties (url) are missing')
            ),
            fn () => $this->addVideoRequestHandler->handle($addVideoRequest)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_requires_a_language(string $offerType): void
    {
        $addVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{"url":"https://www.youtube.com/watch?v=sdsd234", "copyrightHolder":"publiq"}')
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'The required properties (language) are missing')
            ),
            fn () => $this->addVideoRequestHandler->handle($addVideoRequest)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_requires_a_valid_copyright_holder(string $offerType): void
    {
        $addVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{"language":"nl", "url":"https://www.youtube.com/watch?v=sdsd234", "copyrightHolder":123}')
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/copyrightHolder', 'The data (integer) must match the type: string')
            ),
            fn () => $this->addVideoRequestHandler->handle($addVideoRequest)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_requires_a_valid_language_enum(string $offerType): void
    {
        $addVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{"language":"Gesproken", "url":"https://www.youtube.com/watch?v=sdsd234", "copyrightHolder":"Publiq"}')
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/language', 'The data should match one item from enum')
            ),
            fn () => $this->addVideoRequestHandler->handle($addVideoRequest)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_only_allows_supported_video_platforms(string $offerType): void
    {
        $addVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString('{"url":"https://www.google.com/?v=sdsd234", "language": "nl"}')
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/url',
                    'The string should match pattern: ^http(s?):\/\/(www\.)?((youtube\.com\/watch\?v=([^\/#&?]*))|(vimeo\.com\/([^\/#&?]*))|(youtu\.be\/([^\/#&?]*))|(youtube.com/embed/([^\/#&?]*))|(youtube.com/shorts/([^\/#&?]*)))'
                )
            ),
            fn () => $this->addVideoRequestHandler->handle($addVideoRequest)
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
