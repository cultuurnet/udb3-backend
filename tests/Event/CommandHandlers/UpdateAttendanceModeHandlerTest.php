<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Commands\UpdateAttendanceMode;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\AttendanceModeUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\OnlineUrlDeleted;
use CultuurNet\UDB3\Event\Events\OnlineUrlUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Virtual\AttendanceMode;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;

final class UpdateAttendanceModeHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateAttendanceModeHandler(new EventRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_updating_the_attendanceMode(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId)])
            ->when(new UpdateAttendanceMode($eventId, AttendanceMode::online()))
            ->then([new AttendanceModeUpdated($eventId, AttendanceMode::online()->toString())]);
    }

    /**
     * @test
     */
    public function it_removes_onlineUrl_for_offline_attendanceMode(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';

        $this->scenario
            ->withAggregateId($eventId)
            ->given([
                $this->getEventCreated($eventId),
                new AttendanceModeUpdated($eventId, AttendanceMode::online()->toString()),
                new OnlineUrlUpdated($eventId, 'https://www.publiq.be/livestream'),
            ])
            ->when(new UpdateAttendanceMode($eventId, AttendanceMode::offline()))
            ->then([
                new AttendanceModeUpdated($eventId, AttendanceMode::offline()->toString()),
                new OnlineUrlDeleted($eventId),
            ]);
    }

    /**
     * @test
     */
    public function it_ignores_same_attendanceMode(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId)])
            ->when(new UpdateAttendanceMode($eventId, AttendanceMode::offline()))
            ->then([]);
    }


    private function getEventCreated(string $id): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('bfc60a14-6208-4372-942e-86e63744769a'),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.1.0.0', 'Rock')
        );
    }
}
