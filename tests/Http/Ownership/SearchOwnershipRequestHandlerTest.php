<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
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

    /** @var OwnershipSearchRepository&MockObject */
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
                    'status' => $ownership->getState(),
                ])
            );
            $jsonDocuments[] = $jsonDocument->getAssocBody();
            $this->ownershipRepository->save($jsonDocument);
        }

        $searchQuery = new SearchQuery([new SearchParameter('itemId', '9e68dafc-01d8-4c1c-9612-599c918b981d')]);

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with($searchQuery)
            ->willReturn($ownershipCollection);

        $this->ownershipSearchRepository->expects($this->once())
            ->method('searchTotal')
            ->with($searchQuery)
            ->willReturn(2);

        $response = $this->getOwnershipRequestHandler->handle($getOwnershipRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
                '@type' => 'PagedCollection',
                'itemsPerPage' => 2,
                'totalItems' => 2,
                'member' => $jsonDocuments,
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_handles_searching_ownerships_by_state(): void
    {
        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withUriFromString('?state=rejected')
            ->build('GET');

        $approvedOwnership = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29a',
            OwnershipState::approved()->toString()
        );

        $rejectedOwnership = new OwnershipItem(
            '5c7dd3bb-fa44-4c84-b499-303ecc01cba1',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::rejected()->toString()
        );

        $ownershipCollection = new OwnershipItemCollection(
            $approvedOwnership,
            $rejectedOwnership
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
                    'status' => $ownership->getState(),
                ])
            );
            if ($ownership->getState() === OwnershipState::rejected()->toString()) {
                $jsonDocuments[] = $jsonDocument->getAssocBody();
            }
            $this->ownershipRepository->save($jsonDocument);
        }

        $searchQuery = new SearchQuery([new SearchParameter('state', 'rejected')]);

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with($searchQuery)
            ->willReturn(new OwnershipItemCollection($rejectedOwnership));

        $this->ownershipSearchRepository->expects($this->once())
            ->method('searchTotal')
            ->with($searchQuery)
            ->willReturn(1);

        $response = $this->getOwnershipRequestHandler->handle($getOwnershipRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
                '@type' => 'PagedCollection',
                'itemsPerPage' => 1,
                'totalItems' => 1,
                'member' => $jsonDocuments,
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_handles_searching_ownerships_by_owner_id(): void
    {
        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withUriFromString('?ownerId=auth0|63e22626e39a8ca1264bd29b')
            ->build('GET');

        $ownershipForOtherUser = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29a',
            OwnershipState::approved()->toString()
        );

        $ownershipForSameUser = new OwnershipItem(
            '5c7dd3bb-fa44-4c84-b499-303ecc01cba1',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::rejected()->toString()
        );

        $anotherOwnershipForSameUser = new OwnershipItem(
            '4db19a63-44d3-4626-93fe-c53ccbe32762',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::approved()->toString()
        );

        $ownershipCollection = new OwnershipItemCollection(
            $ownershipForOtherUser,
            $ownershipForSameUser,
            $anotherOwnershipForSameUser
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
                    'status' => $ownership->getState(),
                ])
            );
            if ($ownership->getOwnerId() === 'auth0|63e22626e39a8ca1264bd29b') {
                $jsonDocuments[] = $jsonDocument->getAssocBody();
            }
            $this->ownershipRepository->save($jsonDocument);
        }

        $searchQuery = new SearchQuery([new SearchParameter('ownerId', 'auth0|63e22626e39a8ca1264bd29b')]);

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with($searchQuery)
            ->willReturn(new OwnershipItemCollection($ownershipForSameUser, $anotherOwnershipForSameUser));

        $this->ownershipSearchRepository->expects($this->once())
            ->method('searchTotal')
            ->with($searchQuery)
            ->willReturn(2);

        $response = $this->getOwnershipRequestHandler->handle($getOwnershipRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
                '@type' => 'PagedCollection',
                'itemsPerPage' => 2,
                'totalItems' => 2,
                'member' => $jsonDocuments,
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_handles_searching_ownerships_with_start_and_limit(): void
    {
        $getOwnershipRequest = (new Psr7RequestBuilder())
            ->withUriFromString('?state=approved&start=1&limit=1')
            ->build('GET');

        $approvedOwnership1 = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29a',
            OwnershipState::approved()->toString()
        );

        $approvedOwnership2 = new OwnershipItem(
            '5c7dd3bb-fa44-4c84-b499-303ecc01cba1',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::approved()->toString()
        );

        $approvedOwnership3 = new OwnershipItem(
            '4db19a63-44d3-4626-93fe-c53ccbe32762',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29c',
            OwnershipState::approved()->toString()
        );

        $ownershipCollection = new OwnershipItemCollection(
            $approvedOwnership1,
            $approvedOwnership2,
            $approvedOwnership3
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
                    'status' => $ownership->getState(),
                ])
            );
            if ($ownership->getId() === $approvedOwnership2->getId()) {
                $jsonDocuments[] = $jsonDocument->getAssocBody();
            }
            $this->ownershipRepository->save($jsonDocument);
        }

        $searchQuery = new SearchQuery(
            [
                new SearchParameter('state', 'approved'),
            ],
            1,
            1
        );

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with($searchQuery)
            ->willReturn(new OwnershipItemCollection($approvedOwnership2));

        $this->ownershipSearchRepository->expects($this->once())
            ->method('searchTotal')
            ->with($searchQuery)
            ->willReturn(1);

        $response = $this->getOwnershipRequestHandler->handle($getOwnershipRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
                '@type' => 'PagedCollection',
                'itemsPerPage' => 1,
                'totalItems' => 1,
                'member' => $jsonDocuments,
            ]),
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

        $searchQuery = new SearchQuery([new SearchParameter('itemId', '9e68dafc-01d8-4c1c-9612-599c918b981d')]);

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with($searchQuery)
            ->willReturn(new OwnershipItemCollection());

        $this->ownershipSearchRepository->expects($this->once())
            ->method('searchTotal')
            ->with($searchQuery)
            ->willReturn(0);

        $response = $this->getOwnershipRequestHandler->handle($getOwnershipRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
                '@type' => 'PagedCollection',
                'itemsPerPage' => 0,
                'totalItems' => 0,
                'member' => [],
            ]),
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
            ApiProblem::queryParameterMissing('itemId or state'),
            fn () => $this->getOwnershipRequestHandler->handle($getOwnershipRequest)
        );
    }
}
