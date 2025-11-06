<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateTheme;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryNotFound;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateThemeRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private CommandBus&MockObject $commandBus;
    private UpdateThemeRequestHandler $updateThemeRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->updateThemeRequestHandler = new UpdateThemeRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_dispatches_an_update_theme_command(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', '15c43813-0a74-44d2-a6ff-ba00fe12751b')
            ->withRouteParameter('termId', '1.8.3.3.0')
            ->build('PUT');

        $expected = new UpdateTheme('15c43813-0a74-44d2-a6ff-ba00fe12751b', '1.8.3.3.0');

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expected);

        $this->updateThemeRequestHandler->handle($request);
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_the_term_id_is_not_valid(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', '15c43813-0a74-44d2-a6ff-ba00fe12751b')
            ->withRouteParameter('termId', 'foobar')
            ->build('PUT');

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new CategoryNotFound('Term not found.'));

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('Term not found.'),
            fn () => $this->updateThemeRequestHandler->handle($request)
        );
    }
}
