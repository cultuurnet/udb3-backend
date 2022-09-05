<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetRoleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    /** @var EntityServiceInterface|MockObject */
    private $roleService;

    private GetRoleRequestHandler $getRoleRequestHandler;

    protected function setUp(): void
    {
        $this->roleService = $this->createMock(EntityServiceInterface::class);

        $this->getRoleRequestHandler = new GetRoleRequestHandler($this->roleService);
    }

    /**
     * @test
     */
    public function it_throws_not_found_on_missing_role(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->build('GET');

        $this->roleService->expects($this->once())
            ->method('getEntity')
            ->with('609a8214-51c9-48c0-903f-840a4f38852f')
            ->willThrowException(new EntityNotFoundException('609a8214-51c9-48c0-903f-840a4f38852f'));

        $this->assertCallableThrowsApiProblem(
            ApiProblem::roleNotFound('609a8214-51c9-48c0-903f-840a4f38852f'),
            fn () => $this->getRoleRequestHandler->handle($request),
        );
    }

    /**
     * @test
     */
    public function it_gets_a_role(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->build('GET');

        $this->roleService->expects($this->once())
            ->method('getEntity')
            ->with('609a8214-51c9-48c0-903f-840a4f38852f')
            ->willReturn('{"name: "test role"}');

        $response = $this->getRoleRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse('{"name: "test role"}'),
            $response
        );
    }
}
