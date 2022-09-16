<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Commands\RenameRole;
use CultuurNet\UDB3\Role\MissingContentTypeException;
use CultuurNet\UDB3\Role\UnknownContentTypeException;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;

class UpdateRoleRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private UpdateRoleRequestHandler $handler;

    private TraceableCommandBus $commandBus;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->handler = new UpdateRoleRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_throws_when_content_type_header_is_not_given(): void
    {
        $roleId = '1ed1588b-a771-44ce-bac0-8f19f09a7d0f';
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('id', $roleId)
            ->build('PATCH');

        $this->expectException(MissingContentTypeException::class);
        $this->handler->handle($request);
    }

    /**
     * @test
     */
    public function it_throws_when_unknown_content_type_header_is_given(): void
    {
        $roleId = '1ed1588b-a771-44ce-bac0-8f19f09a7d0f';
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('id', $roleId)
            ->withHeader('Content-Type', 'unknown')
            ->build('PATCH');

        $this->expectException(UnknownContentTypeException::class);
        $this->handler->handle($request);
    }

    /**
     * @test
     */
    public function it_updates_the_role_name(): void
    {
        $roleId = '1ed1588b-a771-44ce-bac0-8f19f09a7d0f';
        $name = 'my-little-role';
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('id', $roleId)
            ->withHeader('Content-Type', 'application/ld+json;domain-model=RenameRole')
            ->withJsonBodyFromArray(['name' => $name])
            ->build('PATCH');

        $this->commandBus->record();

        $response = $this->handler->handle($request);
        $this->assertJsonResponse(new Response(StatusCodeInterface::STATUS_NO_CONTENT), $response);

        $expectedCommand = [new RenameRole(new UUID($roleId), $name)];
        $this->assertEquals($expectedCommand, $this->commandBus->getRecordedCommands());
    }
}
