<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\CommandHandlers;

use Broadway\CommandHandling\CommandHandlerInterface;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Place\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\Place\Events\CalendarUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use DateTimeImmutable;
use ValueObjects\Geography\Country;

class UpdateStatusHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus
    ): CommandHandlerInterface {
        $repository = new PlaceRepository(
            $eventStore,
            $eventBus
        );

        return new UpdateStatusHandler($repository);
    }

    /**
     * @test
     */
    public function it_will_handle_update_status_for_permanent_place(): void
    {
        $id = '1';
        $initialCalendar = new Calendar(CalendarType::PERMANENT());

        $newStatus = new Status(StatusType::temporarilyUnavailable(), []);
        $expectedCalendar = (new Calendar(CalendarType::PERMANENT()))->withStatus($newStatus);

        $command = new UpdateStatus($id, $newStatus);

        $expectedEvent = new CalendarUpdated(
            $id,
            $expectedCalendar
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getPlaceCreated($id, $initialCalendar)])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_will_update_status_of_sub_events(): void
    {
        $id = '1';
        $startDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-12-24');
        $endDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-12-24');

        $initialTimestamps = [new Timestamp($startDate, $endDate)];
        $initialCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $initialTimestamps);

        $newStatus = new Status(StatusType::unavailable(), []);

        $expectedTimestamps = [new Timestamp($startDate, $endDate, new Status(StatusType::unavailable(), []))];
        $expectedCalendar = (new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $expectedTimestamps, []))->withStatus($newStatus);

        $command = new UpdateStatus($id, $newStatus);

        $expectedEvent = new CalendarUpdated(
            $id,
            $expectedCalendar
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getPlaceCreated($id, $initialCalendar)])
            ->when($command)
            ->then([$expectedEvent]);
    }

    private function getPlaceCreated(string $id, Calendar $calendar): PlaceCreated
    {
        return new PlaceCreated(
            $id,
            new Language('nl'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new Address(new Street('Some street 101'), new PostalCode('1000'), new Locality('Brussels'), Country::fromNative('BE')),
            $calendar
        );
    }
}
