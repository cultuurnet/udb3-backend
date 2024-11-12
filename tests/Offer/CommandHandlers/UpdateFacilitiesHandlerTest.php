<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryNotFound;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateFacilities;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;

class UpdateFacilitiesHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): UpdateFacilitiesHandler
    {
        return new UpdateFacilitiesHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            )
        );
    }

    /**
     * @test
     */
    public function it_handles_update_facilities_command_and_records_a_new_event_if_the_facilities_have_changed(): void
    {
        $id = '1';

        $facilityIds = [
            '3.13.1.0.0',
            '3.27.0.0.0',
        ];

        $facilities = [
            new Facility('3.13.1.0.0', 'Voorzieningen voor assistentiehonden'),
            new Facility('3.27.0.0.0', 'Rolstoeltoegankelijk'),
        ];

        $command = new UpdateFacilities($id, $facilityIds);

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id)])
            ->when($command)
            ->then([new FacilitiesUpdated($id, $facilities)])
            ->when($command)
            ->then([]);
    }

    /**
     * @test
     */
    public function it_throws_if_a_facility_is_invalid(): void
    {
        $id = '1';

        $facilityIds = [
            '3.13.1.0.0',
            'foobar',
        ];

        $command = new UpdateFacilities($id, $facilityIds);

        $this->expectException(CategoryNotFound::class);

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id)])
            ->when($command)
            ->then([]);
    }

    private function getEventCreated(string $id): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            'some representative title',
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::permanent())
        );
    }
}
