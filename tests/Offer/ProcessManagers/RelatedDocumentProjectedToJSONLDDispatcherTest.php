<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ProcessManagers;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\SimpleEventBus;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Event\ReadModel\Relations\InMemoryEventRelationsRepository;
use CultuurNet\UDB3\EventBus\TraceableEventBus;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\Place\ReadModel\Relations\InMemoryPlaceRelationsRepository;
use PHPUnit\Framework\TestCase;
use stdClass;

final class RelatedDocumentProjectedToJSONLDDispatcherTest extends TestCase
{
    private TraceableEventBus $eventBus;
    private RelatedDocumentProjectedToJSONLDDispatcher $relatedDocumentProjectedToJSONLDDispatcher;
    private InMemoryEventRelationsRepository $eventRelationsRepository;
    private InMemoryPlaceRelationsRepository $placeRelationsRepository;

    protected function setUp(): void
    {
        $this->eventBus = new TraceableEventBus(new SimpleEventBus());
        $this->eventBus->trace();

        $this->eventRelationsRepository = new InMemoryEventRelationsRepository();
        $this->placeRelationsRepository = new InMemoryPlaceRelationsRepository();

        $this->relatedDocumentProjectedToJSONLDDispatcher = new RelatedDocumentProjectedToJSONLDDispatcher(
            $this->eventBus,
            $this->eventRelationsRepository,
            $this->placeRelationsRepository,
            new CallableIriGenerator(fn (string $eventId): string => 'https://io.uitdatabank.dev/events/' . $eventId),
            new CallableIriGenerator(fn (string $placeId): string => 'https://io.uitdatabank.dev/places/' . $placeId)
        );
    }

    /**
     * @test
     */
    public function it_dispatches_event_projected_to_jsonld_for_every_event_related_to_an_updated_place(): void
    {
        $placeId = 'de61f344-fd6f-48c3-9bab-1aa2cc29741d';

        $this->eventRelationsRepository->storePlace('435f6748-1fee-492f-82dd-24288f880810', $placeId);
        $this->eventRelationsRepository->storePlace('6dce1066-fbcd-4f6e-b222-160573d5bebd', $placeId);
        $this->eventRelationsRepository->storePlace('ed6a8bb2-66bb-450c-938f-a969354775b3', $placeId);

        $expectedMessages = [
            new EventProjectedToJSONLD(
                '435f6748-1fee-492f-82dd-24288f880810',
                'https://io.uitdatabank.dev/events/435f6748-1fee-492f-82dd-24288f880810'
            ),
            new EventProjectedToJSONLD(
                '6dce1066-fbcd-4f6e-b222-160573d5bebd',
                'https://io.uitdatabank.dev/events/6dce1066-fbcd-4f6e-b222-160573d5bebd'
            ),
            new EventProjectedToJSONLD(
                'ed6a8bb2-66bb-450c-938f-a969354775b3',
                'https://io.uitdatabank.dev/events/ed6a8bb2-66bb-450c-938f-a969354775b3'
            ),
        ];

        $this->relatedDocumentProjectedToJSONLDDispatcher->handle(
            DomainMessage::recordNow(
                $placeId,
                0,
                new Metadata(),
                new PlaceProjectedToJSONLD($placeId, 'https://io.uitdatabank.dev/places/' . $placeId)
            )
        );

        $actualMessages = $this->eventBus->getEvents();
        $this->assertEquals($expectedMessages, $actualMessages);

        $domainMessages = $this->eventBus->getDomainMessages();
        foreach ($domainMessages as $domainMessage) {
            $this->assertTrue(RelatedDocumentProjectedToJSONLDDispatcher::hasDispatchedMessage($domainMessage));
        }
    }

    /**
     * @test
     */
    public function it_does_not_dispatch_event_projected_to_jsonld_when_disabled(): void
    {
        $placeId = 'de61f344-fd6f-48c3-9bab-1aa2cc29741d';
        $disabledPlaceId = 'ac04aa1e-fc5b-4873-bb47-c721e765a446';
        $placeIds = [$placeId, $disabledPlaceId];

        $this->eventRelationsRepository->storePlace('ed6a8bb2-66bb-450c-938f-a969354775b3', $placeId);
        $this->eventRelationsRepository->storePlace('8dc2f84d-46c4-4098-946d-a8feb56f8e03', $disabledPlaceId);

        $expectedMessages = [
            new EventProjectedToJSONLD(
                'ed6a8bb2-66bb-450c-938f-a969354775b3',
                'https://io.uitdatabank.dev/events/ed6a8bb2-66bb-450c-938f-a969354775b3'
            ),
        ];

        foreach ($placeIds as $placeId) {
            $placeProjectedToJSONLD = new PlaceProjectedToJSONLD(
                $placeId,
                'https://io.uitdatabank.dev/places/' . $placeId
            );

            if ($placeId === $disabledPlaceId) {
                $placeProjectedToJSONLD = $placeProjectedToJSONLD->disableUpdatingEventsLocatedAtPlace();
            }

            $this->relatedDocumentProjectedToJSONLDDispatcher->handle(
                DomainMessage::recordNow(
                    $placeId,
                    0,
                    new Metadata(),
                    $placeProjectedToJSONLD
                )
            );
        }

        $actualMessages = $this->eventBus->getEvents();
        $this->assertEquals($expectedMessages, $actualMessages);

        $domainMessages = $this->eventBus->getDomainMessages();
        foreach ($domainMessages as $domainMessage) {
            $this->assertTrue(RelatedDocumentProjectedToJSONLDDispatcher::hasDispatchedMessage($domainMessage));
        }
    }

    /**
     * @test
     */
    public function it_dispatches_a_projected_to_jsonld_message_for_every_event_and_place_related_to_an_updated_organizer(): void
    {
        $organizerId = 'd5c2ccca-1b60-4bd1-87a7-fa0373090723';

        $placeId1 = '27d1037a-d9aa-44d0-b4af-5dcba1d3da77';
        $placeId2 = 'f9da48ac-e667-4a22-af0e-f4143d1d40b2';

        $eventId1 = '8b63783c-35b2-4b4c-8a58-83ffbc69968e';
        $eventId2 = '4010fe80-255c-49aa-8a7b-73dc4d255de5';
        $eventId3 = '1960de85-70db-4392-b063-f9c171483cb3';
        $eventId4 = '7ce2a203-849f-4878-8ed7-edbb902765df';
        $eventId5 = 'd0e9f2ec-30f3-4c2a-89e5-d57c065c4203';
        $eventId6 = '1306b872-900d-4994-985b-f3d1c0c44ca2';

        // Places 1 and 2 are both linked to the organizer
        $this->placeRelationsRepository->storeRelations($placeId1, $organizerId);
        $this->placeRelationsRepository->storeRelations($placeId2, $organizerId);

        // Event 1 is linked to the organizer and place 1
        $this->eventRelationsRepository->storeOrganizer($eventId1, $organizerId);
        $this->eventRelationsRepository->storePlace($eventId1, $placeId1);

        // Event 2 is linked to the organizer alone
        $this->eventRelationsRepository->storeOrganizer($eventId2, $organizerId);

        // Event 3 is linked to the organizer and place 2
        $this->eventRelationsRepository->storeOrganizer($eventId3, $organizerId);
        $this->eventRelationsRepository->storePlace($eventId3, $placeId2);

        // Event 4 is linked to the organizer alone
        $this->eventRelationsRepository->storeOrganizer($eventId4, $organizerId);

        // Event 5 is linked to place 1 and a random organizer
        $this->eventRelationsRepository->storeOrganizer($eventId5, '1b023ee2-af03-4729-b1fa-114ed19ac83c');
        $this->eventRelationsRepository->storePlace($eventId5, $placeId1);

        // Event 6 is linked to the organizer, and a random place
        $this->eventRelationsRepository->storeOrganizer($eventId6, $organizerId);
        $this->eventRelationsRepository->storePlace($eventId6, 'a8cb0b45-7db1-4a86-9052-a25065a271c5');

        $expectedEvents = [
            new PlaceProjectedToJSONLD($placeId1, 'https://io.uitdatabank.dev/places/' . $placeId1),
            new PlaceProjectedToJSONLD($placeId2, 'https://io.uitdatabank.dev/places/' . $placeId2),
            new EventProjectedToJSONLD($eventId1, 'https://io.uitdatabank.dev/events/' . $eventId1),
            new EventProjectedToJSONLD($eventId2, 'https://io.uitdatabank.dev/events/' . $eventId2),
            new EventProjectedToJSONLD($eventId3, 'https://io.uitdatabank.dev/events/' . $eventId3),
            new EventProjectedToJSONLD($eventId4, 'https://io.uitdatabank.dev/events/' . $eventId4),
            new EventProjectedToJSONLD($eventId5, 'https://io.uitdatabank.dev/events/' . $eventId5),
            new EventProjectedToJSONLD($eventId6, 'https://io.uitdatabank.dev/events/' . $eventId6),
        ];

        $this->relatedDocumentProjectedToJSONLDDispatcher->handle(
            DomainMessage::recordNow(
                $organizerId,
                0,
                new Metadata(),
                new OrganizerProjectedToJSONLD($organizerId, 'https://io.uitdatabank.dev/organizers/' . $organizerId)
            )
        );

        $actualEvents = $this->eventBus->getEvents();
        sort($expectedEvents);
        sort($actualEvents);
        $this->assertEquals($expectedEvents, $actualEvents);

        $domainMessages = $this->eventBus->getDomainMessages();
        foreach ($domainMessages as $domainMessage) {
            $this->assertTrue(RelatedDocumentProjectedToJSONLDDispatcher::hasDispatchedMessage($domainMessage));
        }
    }

    /**
     * @test
     */
    public function it_does_not_recognize_a_random_domain_message_as_dispatched_for_related_document_updates(): void
    {
        $domainMessage = DomainMessage::recordNow(
            '01baa547-e3a0-419a-9f7b-9c9e42f223e3',
            0,
            new Metadata(),
            new stdClass()
        );

        $this->assertFalse(RelatedDocumentProjectedToJSONLDDispatcher::hasDispatchedMessage($domainMessage));
    }
}
