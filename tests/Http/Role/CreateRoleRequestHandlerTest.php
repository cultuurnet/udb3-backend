<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\UuidGenerator\Testing\MockUuidGenerator;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\Commands\CreateRole;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

final class CreateRoleRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    private CreateRoleRequestHandler $handler;

    private TraceableCommandBus $commandBus;

    private string $uuid = '9714108c-dddc-4105-a736-2e32632999f4';

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->commandBus->record();

        $this->handler = new CreateRoleRequestHandler(
            $this->commandBus,
            new MockUuidGenerator($this->uuid)
        );
    }

    /**
     * @test
     */
    public function it_throws_api_problem_when_an_empty_name_is_given(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray(['name' => ''])
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::requiredFieldMissing('name'),
            fn () => $this->handler->handle($request)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_throws_api_problem_when_no_name_is_given(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([])
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::requiredFieldMissing('name'),
            fn () => $this->handler->handle($request)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_can_create_a_role(): void
    {
        $name = 'test-role';
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray(['name' => $name])
            ->build('POST');

        $response = $this->handler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse(['roleId' => $this->uuid], StatusCodeInterface::STATUS_CREATED),
            $response
        );

        $this->assertEquals([new CreateRole(new Uuid($this->uuid), $name)], $this->commandBus->getRecordedCommands());
    }
}
