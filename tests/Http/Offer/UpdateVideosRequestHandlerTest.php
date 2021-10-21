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
     * @test
     */
    public function it_allows_updating_a_video(): void
    {
        $updateVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->withBodyFromString(
                '[
                    {
                        "id": "a927e515-7020-460f-a47e-718ecc785cca",
                        "url":"https://www.youtube.com/watch?v=sdsd234",
                        "copyrightHolder":"publiq",
                        "language": "nl"
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
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_allows_updating_multiple_videos(): void
    {
        $updateVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'events')
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
     * @test
     */
    public function it_requires_an_id(): void
    {
        $updateVideoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'events')
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
}
