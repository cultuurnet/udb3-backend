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

class GetUsersWithRoleRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private GetUsersWithRoleRequestHandler $getUsersWithRoleRequestHandler;

    private DocumentRepository $userRolesRepository;

    protected function setUp(): void
    {
        $this->userRolesRepository = new InMemoryDocumentRepository();

        $this->getUsersWithRoleRequestHandler = new GetUsersWithRoleRequestHandler($this->userRolesRepository);
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

        $this->getUsersWithRoleRequestHandler->handle($request);
    }

    /**
     * @test
     */
    public function it_gets_all_users_with_a_role(): void
    {
        $roleId = '609a8214-51c9-48c0-903f-840a4f38852f';

        $users = [
            'ead2e60d-a319-4f10-9d22-40911fb6542e' => [
                'uuid' => 'ead2e60d-a319-4f10-9d22-40911fb6542e',
                'email' => 'john@example.com',
                'username' => 'johndoe',
            ],
            'e039c73a-d157-482f-a09b-c1f78dc589bc' => [
                'uuid' => 'e039c73a-d157-482f-a09b-c1f78dc589bc',
                'email' => 'jane@example.com',
                'username' => 'janedoe',
            ],
        ];

        $this->userRolesRepository->save((new JsonDocument($roleId))->withAssocBody($users));

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->build('GET');

        $response = $this->getUsersWithRoleRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse(array_values($users)),
            $response
        );
    }
}
