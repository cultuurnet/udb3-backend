<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Commands\CopyEvent;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CopyEventHandlerTest extends TestCase
{
    /**
     * @var MockObject|EventRepository $eventRepository
     */
    private MockObject $eventRepository;
    private CopyEventHandler $copyEventHandler;

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(EventRepository::class);
        $this->copyEventHandler = new CopyEventHandler($this->eventRepository);
    }

    /**
     * @test
     */
    public function it_handles_copy_event(): void
    {
        $originalEventId = '83a12220-9459-4fc8-b1ed-71b3d1668e65';
        $newEventId = '5b9158c6-47ec-4f98-8d9e-edafc89870bc';
        $newCalendar = new PermanentCalendar(new OpeningHours());

        $command = new CopyEvent($originalEventId, $newEventId, $newCalendar);

        $originalEvent = Event::create(
            $originalEventId,
            new Language('nl'),
            new Title('Mock event'),
            new EventType('0.0.0.0.1', 'Mock type'),
            new LocationId('8aa2d316-0f5a-495d-9832-46fc22eeaa89'),
            new Calendar(
                CalendarType::SINGLE(),
                null,
                null,
                [
                    new Timestamp(
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2022-01-01T12:00:00+01:00'),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2022-01-02T12:00:00+01:00')
                    )
                ]
            )
        );

        // Fake a save to the event store.
        // Otherwise there are uncommitted events on the original event, and it will refuse to copy itself.
        $originalEvent->getUncommittedEvents();

        $this->eventRepository->expects($this->once())
            ->method('load')
            ->with($originalEventId)
            ->willReturn($originalEvent);

        $expectedEvents = [
            new EventCopied($newEventId, $originalEventId, Calendar::fromUdb3ModelCalendar($newCalendar)),
        ];
        $actualEvents = [];

        $this->eventRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(
                function (Event $newEvent) use (&$actualEvents): void {
                    $actualEvents = array_map(
                        fn (DomainMessage $domainMessage) => $domainMessage->getPayload(),
                        $newEvent->getUncommittedEvents()->getIterator()->getArrayCopy()
                    );
                }
            );

        $this->copyEventHandler->handle($command);

        $this->assertEquals($expectedEvents, $actualEvents);
    }
}
