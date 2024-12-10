<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Commands\RenameRole;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;

class UpdateRoleRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    private UpdateRoleRequestHandler $handler;

    private TraceableCommandBus $commandBus;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->commandBus->record();

        $this->handler = new UpdateRoleRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_throws_when_content_type_header_is_not_given(): void
    {
        $roleId = '1ed1588b-a771-44ce-bac0-8f19f09a7d0f';
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->build('PATCH');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::unsupportedMediaType(),
            fn () => $this->handler->handle($request)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_throws_when_unknown_content_type_header_is_given(): void
    {
        $roleId = '1ed1588b-a771-44ce-bac0-8f19f09a7d0f';
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withHeader('Content-Type', 'unknown')
            ->build('PATCH');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::unsupportedMediaType(),
            fn () => $this->handler->handle($request)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_updates_the_role_name(): void
    {
        $roleId = '1ed1588b-a771-44ce-bac0-8f19f09a7d0f';
        $name = 'my-little-role';
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withHeader('Content-Type', 'application/ld+json;domain-model=RenameRole')
            ->withJsonBodyFromArray(['name' => $name])
            ->build('PATCH');

        $response = $this->handler->handle($request);
        $this->assertJsonResponse(new Response(StatusCodeInterface::STATUS_NO_CONTENT), $response);

        $expectedCommand = [new RenameRole(new UUID($roleId), $name)];
        $this->assertEquals($expectedCommand, $this->commandBus->getRecordedCommands());
    }
}
