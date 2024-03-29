<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use PHPUnit\Framework\TestCase;

final class UpdateTitleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateTitleRequestHandler $updateTitleRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateTitleRequestHandler = new UpdateTitleRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_updating_the_title(): void
    {
        $updateTitleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'fr')
            ->withBodyFromString(
                '{
                    "name": "The new title"
                }'
            )
            ->build('PUT');

        $this->updateTitleRequestHandler->handle($updateTitleRequest);

        $this->assertEquals(
            [
                new UpdateTitle(
                    'a088f396-ac96-45c4-b6b2-e2b6afe8af07',
                    new Title('The new title'),
                    new Language('fr')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_handles_updating_the_title_without_language_param(): void
    {
        $updateTitleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withBodyFromString(
                '{
                    "name": "De nieuwe titel"
                }'
            )
            ->build('PUT');

        $this->updateTitleRequestHandler->handle($updateTitleRequest);

        $this->assertEquals(
            [
                new UpdateTitle(
                    'a088f396-ac96-45c4-b6b2-e2b6afe8af07',
                    new Title('De nieuwe titel'),
                    new Language('nl')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_requires_a_name(): void
    {
        $updateTitleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'fr')
            ->withBodyFromString(
                '{
                    "title": "This is wrong"
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'The required properties (name) are missing')
            ),
            fn () => $this->updateTitleRequestHandler->handle($updateTitleRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_a_name_with_at_least_one_character(): void
    {
        $updateTitleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'fr')
            ->withBodyFromString(
                '{
                    "name": ""
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/name', 'Minimum string length is 1, found 0')
            ),
            fn () => $this->updateTitleRequestHandler->handle($updateTitleRequest)
        );
    }

    /**
     * @test
     */
    public function it_requires_a_name_with_at_least_one_non_whitespace_character(): void
    {
        $updateTitleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('language', 'fr')
            ->withBodyFromString(
                '{
                    "name": "    "
                }'
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/name', 'The string should match pattern: \S')
            ),
            fn () => $this->updateTitleRequestHandler->handle($updateTitleRequest)
        );
    }
}
