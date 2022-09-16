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

class GetRoleLabelsRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private GetRoleLabelsRequestHandler $getRoleLabelsRequestHandler;

    private DocumentRepository $roleLabelsReadRepository;

    protected function setUp(): void
    {
        $this->roleLabelsReadRepository = new InMemoryDocumentRepository();

        $this->getRoleLabelsRequestHandler = new GetRoleLabelsRequestHandler($this->roleLabelsReadRepository);
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

        $this->getRoleLabelsRequestHandler->handle($request);
    }

    /**
     * @test
     */
    public function it_returns_all_labels_for_a_role(): void
    {
        $roleId = '2c160ac2-5f7f-4eb6-998d-498fa28f65ef';

        $labels = [
            '5355ddc5-3f09-4fb9-b535-056f19bd6997' => [
                'uuid' => '5355ddc5-3f09-4fb9-b535-056f19bd6997',
                'name' => 'myLittleLabel',
                'visibility' => 'visible',
                'privacy' => 'public',
                'excluded' => false,
            ],
            '2d467a0c-66a5-4547-a293-533aa7a69ddf' => [
                'uuid' => '2d467a0c-66a5-4547-a293-533aa7a69ddf',
                'name' => 'myBigLabel',
                'visibility' => 'visible',
                'privacy' => 'public',
                'excluded' => false,
            ],
        ];

        $this->roleLabelsReadRepository->save((new JsonDocument($roleId))->withAssocBody($labels));

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->build('GET');

        $response = $this->getRoleLabelsRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse(array_values($labels)),
            $response
        );
    }
}
