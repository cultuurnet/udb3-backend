<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Role\ReadModel\Search\RepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Search\Results;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RolesSearchRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private RolesSearchRequestHandler $rolesSearchRequestHandler;

    /**
     * @var RepositoryInterface | MockObject
     */
    private $roleSearchRepository;

    protected function setUp()
    {
        $this->roleSearchRepository = $this->createMock(RepositoryInterface::class);

        $this->rolesSearchRequestHandler = new RolesSearchRequestHandler($this->roleSearchRepository);
    }

    /**
     * @test
     */
    public function it_can_search(): void
    {
        $search = 'search-test';
        $limit = '42';
        $start = '24';

        $this->roleSearchRepository
            ->expects($this->once())
            ->method('search')
            ->with($search, $limit, $start)
            ->willReturn(new Results($limit, [], 0));

        $expectedResults = json_encode((object) [
            'itemsPerPage' => $limit,
            'member' => [],
            'totalItems' => 0,
        ]);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('query', $search)
            ->withRouteParameter('limit', $limit)
            ->withRouteParameter('start', $start)
            ->build('GET');

        $actualResponse = $this->rolesSearchRequestHandler->handle($request);

        $expectedResponse = new JsonResponse(
            $expectedResults,
            200
        );

        $this->assertJsonResponse($expectedResponse, $actualResponse);
    }

    /**
     * @test
     */
    public function it_can_search_and_provides_default_values(): void
    {
        $this->roleSearchRepository
            ->expects($this->once())
            ->method('search')
            ->with('', '10', '0')
            ->willReturn(new Results(10, [], 0));

        $expectedResults = json_encode((object) [
            'itemsPerPage' => 10,
            'member' => [],
            'totalItems' => 0,
        ]);

        $request = (new Psr7RequestBuilder())
            ->build('GET');

        $actualResponse = $this->rolesSearchRequestHandler->handle($request);

        $expectedResponse = new JsonResponse(
            $expectedResults,
            200
        );

        $this->assertJsonResponse($expectedResponse, $actualResponse);
    }
}
