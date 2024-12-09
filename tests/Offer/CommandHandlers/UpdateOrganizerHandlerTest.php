<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultureFeed_Uitpas;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\UiTPAS\Validation\ChangeNotAllowedByTicketSales;
use CultuurNet\UDB3\UiTPAS\Validation\EventHasTicketSalesGuard;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class UpdateOrganizerHandlerTest extends CommandHandlerScenarioTestCase
{
    /** @var CultureFeed_Uitpas&MockObject */
    private $cultureFeedUitpas;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): UpdateOrganizerHandler
    {
        $this->cultureFeedUitpas = $this->createMock(\CultureFeed_Uitpas::class);

        $eventRepository = new EventRepository($eventStore, $eventBus);

        return new UpdateOrganizerHandler(
            new OfferRepository(
                $eventRepository,
                new PlaceRepository($eventStore, $eventBus)
            ),
            new EventHasTicketSalesGuard(
                $this->cultureFeedUitpas,
                $eventRepository,
                $this->createMock(LoggerInterface::class)
            )
        );
    }

    /**
     * @test
     */
    public function it_handles_update_organizer(): void
    {
        $eventId = '39007d2d-acec-438d-a687-f2d8400d4c1e';
        $organizerId = '30145054-1af7-4b08-8502-9b38e38e97ac';

        $this->scenario
            ->withAggregateId($eventId)
            ->given([
                $this->eventCreated($eventId),
            ])
            ->when(new UpdateOrganizer($eventId, $organizerId))
            ->then([
                new OrganizerUpdated($eventId, $organizerId),
            ]);
    }

    /**
     * @test
     */
    public function it_ignores_updating_same_organizer(): void
    {
        $eventId = '39007d2d-acec-438d-a687-f2d8400d4c1e';
        $organizerId = '30145054-1af7-4b08-8502-9b38e38e97ac';

        $this->scenario
            ->withAggregateId($eventId)
            ->given([
                $this->eventCreated($eventId),
                new OrganizerUpdated($eventId, $organizerId),
            ])
            ->when(new UpdateOrganizer($eventId, $organizerId))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_throws_when_event_has_ticket_sales(): void
    {
        $eventId = '39007d2d-acec-438d-a687-f2d8400d4c1e';
        $organizerId = '30145054-1af7-4b08-8502-9b38e38e97ac';
        $otherOrganizerId = 'ee024421-6abc-4412-a8b7-e9b507f71f02';

        $this->cultureFeedUitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->willReturn(true);

        $this->expectException(ChangeNotAllowedByTicketSales::class);

        $this->scenario
            ->withAggregateId($eventId)
            ->given([
                $this->eventCreated($eventId),
                new OrganizerUpdated($eventId, $organizerId),
            ])
            ->when(new UpdateOrganizer($eventId, $otherOrganizerId))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_check_ticket_sales_when_organizer_is_the_same(): void
    {
        $eventId = '39007d2d-acec-438d-a687-f2d8400d4c1e';
        $organizerId = '30145054-1af7-4b08-8502-9b38e38e97ac';

        $this->cultureFeedUitpas->expects($this->never())
            ->method('eventHasTicketSales');

        $this->scenario
            ->withAggregateId($eventId)
            ->given([
                $this->eventCreated($eventId),
                new OrganizerUpdated($eventId, $organizerId),
            ])
            ->when(new UpdateOrganizer($eventId, $organizerId))
            ->then([]);
    }

    private function eventCreated(string $id): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new PermanentCalendar(new OpeningHours())
        );
    }
}
