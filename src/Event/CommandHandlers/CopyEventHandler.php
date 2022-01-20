<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\Commands\CopyEvent;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;

final class CopyEventHandler implements CommandHandler
{
    private EventRepository $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof CopyEvent) {
            return;
        }

        $originalEventId = $command->getOriginalEventId();
        $newEventId = $command->getNewEventId();
        $calendar = Calendar::fromUdb3ModelCalendar($command->getCalendar());

        /** @var Event $event */
        $event = $this->eventRepository->load($originalEventId);
        $newEvent = $event->copy($newEventId, $calendar);
        $this->eventRepository->save($newEvent);
    }
}
