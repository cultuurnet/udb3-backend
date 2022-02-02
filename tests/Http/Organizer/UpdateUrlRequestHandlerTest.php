<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use PHPUnit\Framework\TestCase;

final class UpdateUrlRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateUrlRequestHandler $updateUrlRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateUrlRequestHandler = new UpdateUrlRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_updating_the_url(): void
    {
        $updateUrlRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withBodyFromString(
                '{
                    "url": "https://www.publiq.be"
                }'
            )
            ->build('PUT');

        $this->updateUrlRequestHandler->handle($updateUrlRequest);

        $this->assertEquals(
            [
                new UpdateWebsite(
                    'a088f396-ac96-45c4-b6b2-e2b6afe8af07',
                    new Url('https://www.publiq.be'),
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_requires_a_url(): void
    {
        $updateUrlRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'fr')
            ->withBodyFromString(
                '{
                    "missing": "https://www.publiq.be"
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'The required properties (url) are missing')
            ),
            fn () => $this->updateUrlRequestHandler->handle($updateUrlRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_a_valid_url(): void
    {
        $updateUrlRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'fr')
            ->withBodyFromString(
                '{
                    "url": "This is wrong"
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/url', 'The data must match the \'uri\' format')
            ),
            fn () => $this->updateUrlRequestHandler->handle($updateUrlRequest)
        );
    }
}
