<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemCollection;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchOwnershipRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private InMemoryDocumentRepository $ownershipRepository;

    /** @var InMemoryDocumentRepository|MockObject */
    private $ownershipSearchRepository;

    private SearchOwnershipRequestHandler $getOwnershipRequestHandler;

    protected function setUp(): void
    {
        $this->ownershipSearchRepository = $this->createMock(OwnershipSearchRepository::class);

        $this->ownershipRepository = new InMemoryDocumentRepository();

        $this->getOwnershipRequestHandler = new SearchOwnershipRequestHandler(
            $this->ownershipSearchRepository,
            $this->ownershipRepository
        );

        parent::setUp();
    }

    /**
     * @test
     */
    public function it_handles_searching_ownerships_by_item_id(): void
    {
        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withUriFromString('?itemId=9e68dafc-01d8-4c1c-9612-599c918b981d')
            ->build('GET');

        $ownershipCollection = new OwnershipItemCollection(
            new OwnershipItem(
                'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'organizer',
                'auth0|63e22626e39a8ca1264bd29a',
                OwnershipState::approved()->toString()
            ),
            new OwnershipItem(
                '5c7dd3bb-fa44-4c84-b499-303ecc01cba1',
                '9e68dafc-01d8-4c1c-9612-599c918b981d',
                'organizer',
                'auth0|63e22626e39a8ca1264bd29b',
                OwnershipState::rejected()->toString()
            )
        );

        $jsonDocuments = [];
        /** @var OwnershipItem $ownership */
        foreach ($ownershipCollection as $ownership) {
            $jsonDocument = new JsonDocument(
                $ownership->getId(),
                Json::encode([
                    'id' => $ownership->getId(),
                    'itemId' => $ownership->getItemId(),
                    'ownerId' => $ownership->getOwnerId(),
                    'ownerType' => $ownership->getItemType(),
                    'status' => 'approved',
                ])
            );
            $jsonDocuments[] = $jsonDocument->getAssocBody();
            $this->ownershipRepository->save($jsonDocument);
        }

        $this->ownershipSearchRepository->expects($this->once())
            ->method('getByItemId')
            ->with('9e68dafc-01d8-4c1c-9612-599c918b981d')
            ->willReturn($ownershipCollection);

        $response = $this->getOwnershipRequestHandler->handle($getOwnershipRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode($jsonDocuments),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_returns_empty_collection_when_no_ownerships_found(): void
    {
        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withUriFromString('?itemId=9e68dafc-01d8-4c1c-9612-599c918b981d')
            ->build('GET');

        $this->ownershipSearchRepository->expects($this->once())
            ->method('getByItemId')
            ->with('9e68dafc-01d8-4c1c-9612-599c918b981d')
            ->willReturn(new OwnershipItemCollection());

        $response = $this->getOwnershipRequestHandler->handle($getOwnershipRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            '[]',
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_on_missing_item_id(): void
    {
        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterMissing('itemId'),
            fn () => $this->getOwnershipRequestHandler->handle($getOwnershipRequest)
        );
    }
}
