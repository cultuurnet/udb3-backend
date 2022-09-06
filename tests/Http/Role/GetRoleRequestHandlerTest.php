<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

final class GetRoleRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private DocumentRepository $roleRepository;

    private GetRoleRequestHandler $getRoleRequestHandler;

    protected function setUp(): void
    {
        $this->roleRepository = new InMemoryDocumentRepository();

        $this->getRoleRequestHandler = new GetRoleRequestHandler($this->roleRepository);
    }

    /**
     * @test
     */
    public function it_throws_not_found_on_missing_role(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', '609a8214-51c9-48c0-903f-840a4f38852f')
            ->build('GET');

        $this->expectException(DocumentDoesNotExist::class);

        $this->getRoleRequestHandler->handle($request);
    }

    /**
     * @test
     */
    public function it_gets_a_role(): void
    {
        $roleId = '609a8214-51c9-48c0-903f-840a4f38852f';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->build('GET');

        $jsonDocument = (new JsonDocument($roleId))
            ->withAssocBody(['name' => 'test role']);

        $this->roleRepository->save($jsonDocument);

        $response = $this->getRoleRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse($jsonDocument->getRawBody()),
            $response
        );
    }
}
