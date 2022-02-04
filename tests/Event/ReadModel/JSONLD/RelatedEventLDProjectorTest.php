<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\OrganizerService;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\Place\LocalPlaceService;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidFactory;
use stdClass;
use ValueObjects\Web\Url;

final class RelatedEventLDProjectorTest extends TestCase
{
    private DomainMessageBuilder $domainMessageBuilder;

    private InMemoryDocumentRepository $documentRepository;

    /**
     * @var OrganizerService|MockObject
     */
    private $organizerService;

    /**
     * @var RepositoryInterface|MockObject
     */
    private $relationsRepository;

    /**
     * @var LocalPlaceService|MockObject
     */
    private $placeService;

    /**
     * @var IriOfferIdentifierFactoryInterface|MockObject
     */
    private $iriOfferIdentifierFactory;

    private RelatedEventLDProjector $projector;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->documentRepository = new InMemoryDocumentRepository();

        $this->organizerService = $this->createMock(OrganizerService::class);

        $this->relationsRepository = $this->createMock(RepositoryInterface::class);

        $this->placeService = $this->createMock(LocalPlaceService::class);

        $this->iriOfferIdentifierFactory = $this->createMock(IriOfferIdentifierFactoryInterface::class);

        $this->projector = new RelatedEventLDProjector(
            $this->documentRepository,
            $this->relationsRepository,
            $this->placeService,
            $this->organizerService,
            $this->iriOfferIdentifierFactory
        );

        $this->domainMessageBuilder = new DomainMessageBuilder(new UuidFactory());
        $this->domainMessageBuilder->setRecordedOnFromDateTimeString('2018-09-27T20:00:00+00:00');
    }

    /**
     * @test
     */
    public function it_embeds_the_projection_of_a_place_in_all_events_located_at_that_place(): void
    {
        $eventID = '468';
        $secondEventID = '579';

        $placeID = '101214';
        $placeIri = Url::fromNative('http://du.de/place/' . $placeID);

        $placeIdentifier = new IriOfferIdentifier(
            $placeIri,
            $placeID,
            OfferType::place()
        );

        $this->iriOfferIdentifierFactory->expects($this->once())
            ->method('fromIri')
            ->with($placeIri)
            ->willReturn($placeIdentifier);

        $this->relationsRepository
            ->expects($this->once())
            ->method('getEventsLocatedAtPlace')
            ->with($placeID)
            ->willReturn(
                [
                    $eventID,
                    $secondEventID,
                ]
            );

        $placeJSONLD = Json::encode(
            [
                'name' => 't,arsenaal mechelen',
                'address' => [
                    'addressCountry' => 'BE',
                    'addressLocality' => 'Mechelen',
                    'postalCode' => '2800',
                    'streetAddress' => 'Hanswijkstraat 63',
                ],
            ]
        );

        $this->placeService
            ->expects($this->once())
            ->method('getEntity')
            ->with($placeID)
            ->willReturn($placeJSONLD);

        $initialEventDocument = new JsonDocument(
            $eventID,
            Json::encode([
                'labels' => ['test 1', 'test 2'],
                'modified' => '2018-09-23T17:51:06+00:00',
            ])
        );

        $initialSecondEventDocument = new JsonDocument(
            $secondEventID,
            Json::encode([
                'name' => [
                    'nl' => 'Quicksand Valley',
                ],
                'languages' => ['nl'],
                'completedLanguages' => ['nl'],
                'modified' => '2018-09-23T17:51:06+00:00',
            ])
        );

        $this->documentRepository->save($initialEventDocument);
        $this->documentRepository->save($initialSecondEventDocument);

        $expectedEventBody = (object)[
            'labels' => ['test 1', 'test 2'],
            'location' => (object)[
                'name' => 't,arsenaal mechelen',
                'address' => (object)[
                    'addressCountry' => 'BE',
                    'addressLocality' => 'Mechelen',
                    'postalCode' => '2800',
                    'streetAddress' => 'Hanswijkstraat 63',
                ],
            ],
            'modified' => '2018-09-23T17:51:06+00:00',
        ];

        $expectedSecondEventBody = (object)[
            'name' => (object)[
                'nl' => 'Quicksand Valley',
            ],
            'languages' => ['nl'],
            'completedLanguages' => ['nl'],
            'location' => (object)[
                'name' => 't,arsenaal mechelen',
                'address' => (object)[
                    'addressCountry' => 'BE',
                    'addressLocality' => 'Mechelen',
                    'postalCode' => '2800',
                    'streetAddress' => 'Hanswijkstraat 63',
                ],
            ],
            'modified' => '2018-09-23T17:51:06+00:00',
        ];

        $placeProjectedToJSONLD = new PlaceProjectedToJSONLD(
            $placeID,
            (string)$placeIri
        );

        $this->projector->handle(
            $this->domainMessageBuilder->create($placeProjectedToJSONLD)
        );

        $this->assertEquals(
            $expectedEventBody,
            $this->getBody($eventID)
        );

        $this->assertEquals(
            $expectedSecondEventBody,
            $this->getBody($secondEventID)
        );
    }

    /**
     * @test
     */
    public function it_embeds_the_projection_of_an_organizer_in_all_related_events(): void
    {
        $eventID = '468';
        $secondEventID = '579';

        $organizerId = '101214';

        $this->relationsRepository
            ->expects($this->once())
            ->method('getEventsOrganizedByOrganizer')
            ->with($organizerId)
            ->willReturn(
                [
                    $eventID,
                    $secondEventID,
                ]
            );

        $organizerJSONLD = Json::encode(
            [
                'name' => 'stichting tegen Kanker',
                'email' => [
                    'kgielens@stichtingtegenkanker.be',
                ],
            ]
        );

        $this->organizerService
            ->expects($this->once())
            ->method('getEntity')
            ->with($organizerId)
            ->willReturn($organizerJSONLD);

        $initialEventDocument = new JsonDocument(
            $eventID,
            Json::encode([
                'labels' => ['beweging', 'kanker'],
                'modified' => '2018-09-23T17:51:06+00:00',
            ])
        );

        $initialSecondEventDocument = new JsonDocument(
            $secondEventID,
            Json::encode([
                'name' => [
                    'nl' => 'Rekanto - TaiQi',
                    'fr' => 'Raviva - TaiQi',
                ],
                'languages' => ['nl', 'fr'],
                'completedLanguages' => ['nl', 'fr'],
                'modified' => '2018-09-23T17:51:06+00:00',
            ])
        );

        $this->documentRepository->save($initialEventDocument);
        $this->documentRepository->save($initialSecondEventDocument);

        $expectedEventBody = (object)[
            'labels' => ['beweging', 'kanker'],
            'organizer' => (object)[
                'name' => 'stichting tegen Kanker',
                'email' => [
                    'kgielens@stichtingtegenkanker.be',
                ],
            ],
            'modified' => '2018-09-23T17:51:06+00:00',
        ];

        $expectedSecondEventBody = (object)[
            'name' => (object)[
                'nl' => 'Rekanto - TaiQi',
                'fr' => 'Raviva - TaiQi',
            ],
            'languages' => ['nl', 'fr'],
            'completedLanguages' => ['nl', 'fr'],
            'organizer' => (object)[
                'name' => 'stichting tegen Kanker',
                'email' => [
                    'kgielens@stichtingtegenkanker.be',
                ],
            ],
            'modified' => '2018-09-23T17:51:06+00:00',
        ];

        $organizerProjectedToJSONLD = new OrganizerProjectedToJSONLD(
            $organizerId,
            'organizers/' . $organizerId
        );

        $this->projector->handle(
            $this->domainMessageBuilder->create($organizerProjectedToJSONLD)
        );

        $this->assertEquals(
            $expectedEventBody,
            $this->getBody($eventID)
        );

        $this->assertEquals(
            $expectedSecondEventBody,
            $this->getBody($secondEventID)
        );
    }

    private function getBody(string $id): stdClass
    {
        return $this->documentRepository->fetch($id)->getBody();
    }
}
