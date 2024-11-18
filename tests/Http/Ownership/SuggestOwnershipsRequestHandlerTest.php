<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifiers;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepositoryMockFactory;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemCollection;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Results;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class SuggestOwnershipsRequestHandlerTest extends TestCase
{
    /**
     * @var SearchServiceInterface&MockObject
     */
    private $searchService;
    private CurrentUser $currentUser;
    /**
     * @var UserIdentityResolver&MockObject
     */
    private $userIdentityResolver;
    private OfferJsonDocumentReadRepositoryMockFactory $offerRepositoryFactory;
    private OfferJsonDocumentReadRepository $offerRepository;
    private UserIdentityDetails $user;
    /**
     * @var OwnershipSearchRepository&MockObject
     */
    private $ownershipSearchRepository;
    private SuggestOwnershipsRequestHandler $suggestOwnershipsRequestHandler;
    private string $expectedQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchService = $this->createMock(SearchServiceInterface::class);

        CurrentUser::configureGodUserIds([]);
        $this->currentUser = new CurrentUser(Uuid::uuid4()->toString());
        $this->userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $this->offerRepositoryFactory = new OfferJsonDocumentReadRepositoryMockFactory();
        $this->offerRepository = $this->offerRepositoryFactory->create();
        $this->user = new UserIdentityDetails(
            $this->currentUser->getId(),
            'John Doe',
            'john@doe.com'
        );
        $this->ownershipSearchRepository = $this->createMock(OwnershipSearchRepository::class);

        $this->expectedQuery = "_exists_:organizer.id AND address.\*.addressCountry:* AND workflowStatus:(DRAFT OR READY_FOR_VALIDATION OR APPROVED) AND creator:(auth0|{$this->user->getUserId()} OR {$this->user->getUserId()} OR {$this->user->getEmailAddress()})";

        $this->suggestOwnershipsRequestHandler = new SuggestOwnershipsRequestHandler(
            $this->searchService,
            $this->offerRepository,
            $this->currentUser,
            $this->userIdentityResolver,
            $this->ownershipSearchRepository,
            new OrganizerIDParser()
        );
    }

    /**
     * @test
     */
    public function it_suggests_ownerships(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('?itemType=organizer')
            ->build('GET');

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with($this->currentUser->getId())
            ->willReturn($this->user);

        $organizer = $this->givenThereIsAnOrganizer();
        $event = $this->givenThereIsAnEventWithOrganizer($organizer);

        $this->searchService->expects($this->once())
            ->method('search')
            ->with($this->expectedQuery, 10, 0, ['modified' => 'desc'])
            ->willReturn(new Results(
                new ItemIdentifiers(
                    new ItemIdentifier(new Url($event['body']['@id']), $event['id'], ItemType::event()),
                ),
                2
            ));

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with(new SearchQuery([
                new SearchParameter('ownerId', $this->currentUser->getId()),
                new SearchParameter('state', OwnershipState::requested()->toString()),
                new SearchParameter('state', OwnershipState::approved()->toString()),
            ]))
            ->willReturn(new OwnershipItemCollection());

        $response = $this->suggestOwnershipsRequestHandler->handle($request);

        $expected = Json::encode([
            'member' => [
                $organizer['body'],
            ],
        ]);

        $this->assertEquals($expected, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_does_not_suggest_ownerships_that_already_exist(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('?itemType=organizer')
            ->build('GET');

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with($this->currentUser->getId())
            ->willReturn($this->user);

        $organizer = $this->givenThereIsAnOrganizer();
        $event = $this->givenThereIsAnEventWithOrganizer($organizer);

        $this->searchService->expects($this->once())
            ->method('search')
            ->with($this->expectedQuery, 10, 0, ['modified' => 'desc'])
            ->willReturn(new Results(
                new ItemIdentifiers(
                    new ItemIdentifier(new Url($event['body']['@id']), $event['id'], ItemType::event()),
                ),
                2
            ));

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with(new SearchQuery([
                new SearchParameter('ownerId', $this->currentUser->getId()),
                new SearchParameter('state', OwnershipState::requested()->toString()),
                new SearchParameter('state', OwnershipState::approved()->toString()),
            ]))
            ->willReturn(new OwnershipItemCollection(
                new OwnershipItem(Uuid::uuid4()->toString(), $organizer['id'], ItemType::organizer()->toString(), $this->currentUser->getId(), OwnershipState::requested()->toString()),
            ));

        $response = $this->suggestOwnershipsRequestHandler->handle($request);

        $expected = Json::encode([
            'member' => [],
        ]);

        $this->assertEquals($expected, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_suggests_ownerships_without_duplicates(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('?itemType=organizer')
            ->build('GET');

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with($this->currentUser->getId())
            ->willReturn($this->user);

        $organizer = $this->givenThereIsAnOrganizer();
        $place = $this->givenThereIsAnPlaceWithOrganizer($organizer);
        $event = $this->givenThereIsAnEventWithOrganizer($organizer);

        $this->searchService->expects($this->once())
            ->method('search')
            ->with($this->expectedQuery, 10, 0, ['modified' => 'desc'])
            ->willReturn(new Results(
                new ItemIdentifiers(
                    new ItemIdentifier(new Url($place['body']['@id']), $place['id'], ItemType::place()),
                    new ItemIdentifier(new Url($event['body']['@id']), $event['id'], ItemType::event()),
                ),
                2
            ));

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with(new SearchQuery([
                new SearchParameter('ownerId', $this->currentUser->getId()),
                new SearchParameter('state', OwnershipState::requested()->toString()),
                new SearchParameter('state', OwnershipState::approved()->toString()),
            ]))
            ->willReturn(new OwnershipItemCollection());

        $response = $this->suggestOwnershipsRequestHandler->handle($request);

        $expected = Json::encode([
            'member' => [
                $organizer['body'],
            ],
        ]);

        $this->assertEquals($expected, $response->getBody()->getContents());
    }


    private function givenThereIsAnOrganizer(): array
    {
        $id = Uuid::uuid4()->toString();
        return [
            'id' => $id,
            'body' => [
                '@id' => 'https://mock.io.uitdatabank.be/organizers/' . $id,
                '@type' => 'Organizer',
            ],
        ];
    }

    private function givenThereIsAnEventWithOrganizer(array $organizer): array
    {
        $id = Uuid::uuid4()->toString();
        $body = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $id,
            'organizer' => $organizer['body'],
        ];

        $this->offerRepositoryFactory->expectEventDocument(new JsonDocument($id, Json::encode($body)));

        return [
            'id' => $id,
            'body' => $body,
        ];
    }


    private function givenThereIsAnPlaceWithOrganizer(array $organizer): array
    {
        $id = Uuid::uuid4()->toString();
        $body = [
            '@id' => 'https://mock.io.uitdatabank.be/places/' . $id,
            'organizer' => $organizer['body'],
        ];

        $this->offerRepositoryFactory->expectPlaceDocument(new JsonDocument($id, Json::encode($body)));

        return [
            'id' => $id,
            'body' => $body,
        ];
    }
}
