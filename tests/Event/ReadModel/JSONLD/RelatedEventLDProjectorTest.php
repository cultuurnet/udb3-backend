<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use CultuurNet\UDB3\Event\EventServiceInterface;
use CultuurNet\UDB3\Event\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\OrganizerService;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\PlaceService;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use ValueObjects\Web\Url;

class RelatedEventLDProjectorTest extends TestCase
{
    /**
     * @var DomainMessageBuilder
     */
    protected $domainMessageBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->documentRepository = new InMemoryDocumentRepository();

        $this->organizerService = $this->createMock(OrganizerService::class);

        $this->eventService = $this->createMock(EventServiceInterface::class);

        $this->placeService = $this->createMock(PlaceService::class);

        $this->iriOfferIdentifierFactory = $this->createMock(IriOfferIdentifierFactoryInterface::class);

        $this->projector = new RelatedEventLDProjector(
            $this->documentRepository,
            $this->eventService,
            $this->placeService,
            $this->organizerService,
            $this->iriOfferIdentifierFactory
        );

        $this->domainMessageBuilder = new DomainMessageBuilder();
        $this->domainMessageBuilder->setRecordedOnFromDateTimeString('2018-09-27T20:00:00+00:00');
    }

    /**
     * @test
     */
    public function it_embeds_the_projection_of_a_place_in_all_events_located_at_that_place()
    {
        $eventID = '468';
        $secondEventID = '579';

        $placeID = '101214';
        $placeIri = Url::fromNative('http://du.de/place/' . $placeID);

        $placeIdentifier = new IriOfferIdentifier(
            $placeIri,
            $placeID,
            OfferType::PLACE()
        );

        $this->iriOfferIdentifierFactory->expects($this->once())
            ->method('fromIri')
            ->with($placeIri)
            ->willReturn($placeIdentifier);

        $this->eventService
            ->expects($this->once())
            ->method('eventsLocatedAtPlace')
            ->with($placeID)
            ->willReturn(
                [
                    $eventID,
                    $secondEventID,
                ]
            );

        $placeJSONLD = json_encode(
            [
                'name' => "t,arsenaal mechelen",
                'address' => [
                    'addressCountry' => "BE",
                    'addressLocality' => "Mechelen",
                    'postalCode' => "2800",
                    'streetAddress' => "Hanswijkstraat 63",
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
            json_encode([
                'labels' => ['test 1', 'test 2'],
                'modified' => '2018-09-23T17:51:06+00:00',
            ])
        );

        $initialSecondEventDocument = new JsonDocument(
            $secondEventID,
            json_encode([
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
                'name' => "t,arsenaal mechelen",
                'address' => (object)[
                    'addressCountry' => "BE",
                    'addressLocality' => "Mechelen",
                    'postalCode' => "2800",
                    'streetAddress' => "Hanswijkstraat 63",
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
                'name' => "t,arsenaal mechelen",
                'address' => (object)[
                    'addressCountry' => "BE",
                    'addressLocality' => "Mechelen",
                    'postalCode' => "2800",
                    'streetAddress' => "Hanswijkstraat 63",
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
    public function it_embeds_the_projection_of_an_organizer_in_all_related_events()
    {
        $eventID = '468';
        $secondEventID = '579';

        $organizerId = '101214';

        $this->eventService
            ->expects($this->once())
            ->method('eventsOrganizedByOrganizer')
            ->with($organizerId)
            ->willReturn(
                [
                    $eventID,
                    $secondEventID,
                ]
            );

        $organizerJSONLD = json_encode(
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
            json_encode([
                'labels' => ['beweging', 'kanker'],
                'modified' => '2018-09-23T17:51:06+00:00',
            ])
        );

        $initialSecondEventDocument = new JsonDocument(
            $secondEventID,
            json_encode([
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

    /**
     * @param string $id
     * @return \stdClass
     */
    protected function getBody($id)
    {
        $document = $this->documentRepository->get($id);
        return $document->getBody();
    }
}
