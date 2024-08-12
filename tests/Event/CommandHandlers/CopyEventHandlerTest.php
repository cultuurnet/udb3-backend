<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Commands\CopyEvent;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\Productions\Production;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Calendar\Timestamp;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CopyEventHandlerTest extends TestCase
{
    private MockObject $eventRepository;
    private MockObject $productionRepository;
    private CopyEventHandler $copyEventHandler;

    private CopyEventHandler $disabledCopyEventHandler;

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(EventRepository::class);
        $this->productionRepository = $this->createMock(ProductionRepository::class);
        $this->copyEventHandler = new CopyEventHandler(
            $this->eventRepository,
            $this->productionRepository,
            true
        );
        $this->disabledCopyEventHandler = new CopyEventHandler(
            $this->eventRepository,
            $this->productionRepository,
            false
        );
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
                        DateTimeFactory::fromAtom('2022-01-01T12:00:00+01:00'),
                        DateTimeFactory::fromAtom('2022-01-02T12:00:00+01:00')
                    ),
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

        $this->productionRepository->expects($this->once())
            ->method('findProductionForEventId')
            ->with($originalEventId)
            ->willThrowException(new EntityNotFoundException('No production found for given event id'));

        $this->productionRepository->expects($this->never())
            ->method('addEvent');

        $this->copyEventHandler->handle($command);

        $this->assertEquals($expectedEvents, $actualEvents);
    }

    /**
     * @test
     */
    public function it_adds_the_new_event_to_the_same_production_as_the_original_event(): void
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
                        DateTimeFactory::fromAtom('2022-01-01T12:00:00+01:00'),
                        DateTimeFactory::fromAtom('2022-01-02T12:00:00+01:00')
                    ),
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

        $production = new Production(
            ProductionId::fromNative('159abe3a-bd77-4284-8623-17bae202c63b'),
            'Mock production',
            [$originalEventId]
        );

        $this->productionRepository->expects($this->once())
            ->method('findProductionForEventId')
            ->with($originalEventId)
            ->willReturn($production);

        $this->productionRepository->expects($this->once())
            ->method('addEvent')
            ->with($newEventId, $production);

        $this->copyEventHandler->handle($command);

        $this->assertEquals($expectedEvents, $actualEvents);
    }

    /**
     * @test
     */
    public function it_does_not_add_the_new_event_to_the_same_production_if_disabled(): void
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
                        DateTimeFactory::fromAtom('2022-01-01T12:00:00+01:00'),
                        DateTimeFactory::fromAtom('2022-01-02T12:00:00+01:00')
                    ),
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

        $this->productionRepository->expects($this->never())
            ->method('findProductionForEventId');

        $this->productionRepository->expects($this->never())
            ->method('addEvent');

        $this->disabledCopyEventHandler->handle($command);

        $this->assertEquals($expectedEvents, $actualEvents);
    }
}
