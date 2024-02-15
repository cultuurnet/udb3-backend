<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultureFeed_Cdb_Xml;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\Commands\UpdateAttendanceMode;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\AttendanceModeUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\OnlineUrlDeleted;
use CultuurNet\UDB3\Event\Events\OnlineUrlUpdated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Theme;

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
     * @bugfix https://jira.uitdatabank.be/browse/III-4702
     */
    public function it_handles_updating_the_attendanceMode_on_events_created_via_xml(): void
    {
        $eventId = '40021958-0ad8-46bd-8528-3ac3686818a1';

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    new EventImportedFromUDB2(
                        $eventId,
                        file_get_contents(__DIR__ . '/../samples/EventTest.cdbxml.xml'),
                        CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2')
                    ),
                ]
            )
            ->when(new UpdateAttendanceMode($eventId, AttendanceMode::online()))
            ->then(
                [
                    new AttendanceModeUpdated($eventId, AttendanceMode::online()->toString()),
                ]
            );
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
            'some representative title',
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('bfc60a14-6208-4372-942e-86e63744769a'),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.1.0.0', 'Rock')
        );
    }
}
