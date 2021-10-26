<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Offer\UpdateVideosRequestHandler;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\Video\UpdateVideo;
use PHPUnit\Framework\TestCase;

class UpdateVideosRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateVideosRequestHandler $updateVideosRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateVideosRequestHandler = new UpdateVideosRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @dataProvider updateVideoDataProvider
     * @test
     */
    public function it_allows_updating_a_video(string $body, UpdateVideo $updateVideo): void
    {
        $updateVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString($body)
            ->build('PATCH');

        $this->updateVideosRequestHandler->handle($updateVideoRequest);

        $this->assertEquals(
            [$updateVideo],
            $this->commandBus->getRecordedCommands()
        );
    }

    public function updateVideoDataProvider(): array
    {
        return [
            'Update url' => [
                '[
                    {
                        "id": "a927e515-7020-460f-a47e-718ecc785cca",
                        "url":"https://www.youtube.com/watch?v=sdsd234"
                    }
                ]',
                (new UpdateVideo('609a8214-51c9-48c0-903f-840a4f38852f', 'a927e515-7020-460f-a47e-718ecc785cca'))
                    ->withUrl(new Url('https://www.youtube.com/watch?v=sdsd234')),
            ],
            'Update copyright holder' => [
                '[
                    {
                        "id": "a927e515-7020-460f-a47e-718ecc785cca",
                        "copyrightHolder":"publiq"
                    }
                ]',
                (new UpdateVideo('609a8214-51c9-48c0-903f-840a4f38852f', 'a927e515-7020-460f-a47e-718ecc785cca'))
                    ->withCopyrightHolder(new CopyrightHolder('publiq')),
            ],
            'Update language' => [
                '[
                    {
                        "id": "a927e515-7020-460f-a47e-718ecc785cca",
                        "language": "nl"
                    }
                ]',
                (new UpdateVideo('609a8214-51c9-48c0-903f-840a4f38852f', 'a927e515-7020-460f-a47e-718ecc785cca'))
                    ->withLanguage(new Language('nl')),
            ],
            'Update url and copyright holder' => [
                '[
                    {
                        "id": "a927e515-7020-460f-a47e-718ecc785cca",
                        "url":"https://www.youtube.com/watch?v=sdsd234",
                        "copyrightHolder":"publiq"
                    }
                ]',
                (new UpdateVideo('609a8214-51c9-48c0-903f-840a4f38852f', 'a927e515-7020-460f-a47e-718ecc785cca'))
                    ->withUrl(new Url('https://www.youtube.com/watch?v=sdsd234'))
                    ->withCopyrightHolder(new CopyrightHolder('publiq')),
            ],
            'Update url and language' => [
                '[
                    {
                        "id": "a927e515-7020-460f-a47e-718ecc785cca",
                        "url":"https://www.youtube.com/watch?v=sdsd234",
                        "language": "nl"
                    }
                ]',
                (new UpdateVideo('609a8214-51c9-48c0-903f-840a4f38852f', 'a927e515-7020-460f-a47e-718ecc785cca'))
                    ->withUrl(new Url('https://www.youtube.com/watch?v=sdsd234'))
                    ->withLanguage(new Language('nl')),
            ],
            'Update copyright holder and language' => [
                '[
                    {
                        "id": "a927e515-7020-460f-a47e-718ecc785cca",
                        "copyrightHolder":"publiq",
                        "language": "nl"
                    }
                ]',
                (new UpdateVideo('609a8214-51c9-48c0-903f-840a4f38852f', 'a927e515-7020-460f-a47e-718ecc785cca'))
                    ->withLanguage(new Language('nl'))
                    ->withCopyrightHolder(new CopyrightHolder('publiq')),
            ],
            'Update all properties' => [
                '[
                    {
                        "id": "a927e515-7020-460f-a47e-718ecc785cca",
                        "url":"https://www.youtube.com/watch?v=sdsd234",
                        "copyrightHolder":"publiq",
                        "language": "nl"
                    }
                ]',
                (new UpdateVideo('609a8214-51c9-48c0-903f-840a4f38852f', 'a927e515-7020-460f-a47e-718ecc785cca'))
                    ->withUrl(new Url('https://www.youtube.com/watch?v=sdsd234'))
                    ->withLanguage(new Language('nl'))
                    ->withCopyrightHolder(new CopyrightHolder('publiq')),
            ],
        ];
    }

    /**
     * @dataProvider offerTypeProvider
     * @test
     */
    public function it_allows_updating_multiple_videos(string $offerType): void
    {
        $updateVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString(
                '[
                    {
                        "id": "a927e515-7020-460f-a47e-718ecc785cca",
                        "url":"https://www.youtube.com/watch?v=sdsd234",
                        "copyrightHolder":"publiq",
                        "language": "nl"
                    },
                    {
                        "id": "58eccca1-a3e3-4233-8ba2-32ab291fccd8",
                        "language": "fr"
                    }
                ]'
            )
            ->build('PATCH');

        $this->updateVideosRequestHandler->handle($updateVideoRequest);

        $this->assertEquals(
            [
                (new UpdateVideo('609a8214-51c9-48c0-903f-840a4f38852f', 'a927e515-7020-460f-a47e-718ecc785cca'))
                    ->withUrl(new Url('https://www.youtube.com/watch?v=sdsd234'))
                    ->withLanguage(new Language('nl'))
                    ->withCopyrightHolder(new CopyrightHolder('publiq')),
                (new UpdateVideo('609a8214-51c9-48c0-903f-840a4f38852f', '58eccca1-a3e3-4233-8ba2-32ab291fccd8'))
                    ->withLanguage(new Language('fr')),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @dataProvider offerTypeProvider
     * @test
     */
    public function it_requires_an_id(string $offerType): void
    {
        $updateVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString(
                '[
                    {
                        "url":"https://www.youtube.com/watch?v=sdsd234",
                        "copyrightHolder":"publiq",
                        "language": "nl"
                    }
                ]'
            )
            ->build('PATCH');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/0', 'The required properties (id) are missing')
            ),
            fn () => $this->updateVideosRequestHandler->handle($updateVideoRequest)
        );
    }

    public function offerTypeProvider(): array
    {
        return [
            'events' => [
                'events',
            ],
            'places' => [
                'places',
            ],
        ];
    }
}
