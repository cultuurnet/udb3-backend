<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryNotFound;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateTypeRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var CommandBus&MockObject */
    private $commandBus;
    private UpdateTypeRequestHandler $updateTypeRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->updateTypeRequestHandler = new UpdateTypeRequestHandler(
            $this->commandBus
        );
    }

    /**
     * @test
     */
    public function it_dispatches_an_update_type_command(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerId', '15c43813-0a74-44d2-a6ff-ba00fe12751b')
            ->withRouteParameter('termId', '0.50.4.0.0')
            ->build('PUT');

        $expected = new UpdateType('15c43813-0a74-44d2-a6ff-ba00fe12751b', '0.50.4.0.0');

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expected);

        $this->updateTypeRequestHandler->handle($request);
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_the_term_id_is_not_valid(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerId', '15c43813-0a74-44d2-a6ff-ba00fe12751b')
            ->withRouteParameter('termId', 'foobar')
            ->build('PUT');

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new CategoryNotFound('Term not found.'));

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('Term not found.'),
            fn () => $this->updateTypeRequestHandler->handle($request)
        );
    }
}
