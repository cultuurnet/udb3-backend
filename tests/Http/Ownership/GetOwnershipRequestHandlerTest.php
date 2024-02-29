<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class GetOwnershipRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private InMemoryDocumentRepository $ownershipRepository;

    private GetOwnershipRequestHandler $getOwnershipRequestHandler;

    protected function setUp(): void
    {
        $this->ownershipRepository = new InMemoryDocumentRepository();

        $this->getOwnershipRequestHandler = new GetOwnershipRequestHandler($this->ownershipRepository);

        parent::setUp();
    }

    /**
     * @test
     */
    public function it_handles_getting_an_ownership(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';

        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('GET');

        $body = Json::encode([
            'id' => 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            'itemId' => '9e68dafc-01d8-4c1c-9612-599c918b981d',
        ]);

        $this->ownershipRepository->save(new JsonDocument($ownershipId, $body));

        $response = $this->getOwnershipRequestHandler->handle($getOwnershipRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($body, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_ownership_is_not_found(): void
    {
        $ownershipId = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';

        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withRouteParameter('ownershipId', $ownershipId)
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::ownershipNotFound($ownershipId),
            fn () => $this->getOwnershipRequestHandler->handle($getOwnershipRequest)
        );
    }
}
