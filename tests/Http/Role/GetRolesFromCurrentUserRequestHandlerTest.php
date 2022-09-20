<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class GetRolesFromCurrentUserRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private GetRolesFromCurrentUserRequestHandler $getRolesFromUserRequestHandler;

    private DocumentRepository $userRolesRepository;

    private string $currentUserId;

    protected function setUp(): void
    {
        $this->userRolesRepository = new InMemoryDocumentRepository();
        $this->currentUserId = '132c7cdd-d771-4c81-bca1-ba9b93b1f42b';

        $this->getRolesFromUserRequestHandler = new GetRolesFromCurrentUserRequestHandler(
            $this->userRolesRepository,
            $this->currentUserId
        );
    }

    /**
     * @test
     */
    public function it_returns_an_empty_response_when_no_roles_document_is_found_for_the_current_user(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('userId', $this->currentUserId)
            ->build('GET');

        $response = $this->getRolesFromUserRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse([]),
            $response
        );
    }

    /**
     * @test
     */
    public function it_responds_with_an_array_of_roles_for_the_current_user(): void
    {
        $roles = [
            'c022ae72-cb7b-4031-b79f-a93a41e9fa44' => [
                'uuid' => 'c022ae72-cb7b-4031-b79f-a93a41e9fa44',
                'name' => 'Validator Leuven',
                'constraint' => 'zipcode:3000',
                'permissions' => [
                    'AANBOD_INVOEREN',
                    'AANBOD_MODEREREN',
                ],
            ],
            '3a67f02e-3938-4c81-9935-44a9ee3ae5e0' => [
                'uuid' => '3a67f02e-3938-4c81-9935-44a9ee3ae5e0',
                'name' => 'validator Kessel-Lo',
                'constraint' => 'zipcode:3010',
                'permissions' => [
                    'AANBOD_INVOEREN',
                    'AANBOD_MODEREREN',
                ],
            ],
        ];

        $this->userRolesRepository->save((new JsonDocument($this->currentUserId))->withAssocBody($roles));

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('userId', $this->currentUserId)
            ->build('GET');

        $response = $this->getRolesFromUserRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse(array_values($roles)),
            $response
        );
    }
}
