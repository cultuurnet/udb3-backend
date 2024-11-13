<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifiers;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Results;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
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
    private ResultsGenerator $resultsGenerator;
    private CurrentUser $currentUser;
    private UserIdentityResolver $userIdentityResolver;
    private UserIdentityDetails $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchService = $this->createMock(SearchServiceInterface::class);
        $this->resultsGenerator = new ResultsGenerator($this->searchService, new Sorting('modified', 'desc'));
        $this->currentUser = new CurrentUser(Uuid::uuid4()->toString());
        $this->userIdentityResolver = $this->createMock(UserIdentityResolver::class);
        $this->user = new UserIdentityDetails(
            $this->currentUser->getId(),
            'John Doe',
            'john@doe.com'
        );
    }

    /**
     * @test
     */
    public function it_works(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('?itemType=organizer')
            ->build('GET');

        $expectedQuery = "_exists_organizer.id&addressCountry=*&workflowStatus=DRAFT,READY_FOR_VALIDATION,APPROVED&creator:(auth0|{$this->user->getUserId()} OR {$this->user->getUserId()} OR {$this->user->getEmailAddress()})";

        $this->userIdentityResolver->expects($this->once())
            ->method('getUserById')
            ->with($this->currentUser->getId())
            ->willReturn($this->user);

        $offersRepository = new InMemoryDocumentRepository();

        $placeId = Uuid::uuid4()->toString();
        $place = [
            '@id' => 'https://mock.io.uitdatabank.be/places/' . $placeId,
        ];

        $eventId = Uuid::uuid4()->toString();
        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
        ];

        $organizerId = Uuid::uuid4()->toString();
        $organizer = [
            '@id' => 'https://mock.io.uitdatabank.be/organizers/' . $organizerId,
        ];

        $offersRepository->save(new JsonDocument($placeId, Json::encode($place)));
        $offersRepository->save(new JsonDocument($eventId, Json::encode($event)));
        $offersRepository->save(new JsonDocument($organizerId, Json::encode($organizer)));

        $this->searchService->expects($this->once())
            ->method('search')
            ->with($expectedQuery, 10, 0, ['modified' => 'desc'])
            ->willReturn(new Results(
                new ItemIdentifiers(
                    new ItemIdentifier(new Url($organizer['@id']), $organizerId, ItemType::organizer()),
                ),
                2
            ));

        $handler = new SuggestOwnershipsRequestHandler($this->resultsGenerator, $offersRepository, $this->currentUser, $this->userIdentityResolver);

        $response = $handler->handle($request);

        $expected = Json::encode([
            $organizer,
        ]);

        $this->assertEquals($expected, $response->getBody()->getContents());
    }
}
