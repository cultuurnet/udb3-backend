<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Commands\CopyEvent;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;

final class CopyEventHandler implements CommandHandler
{
    private EventRepository $eventRepository;
    private ProductionRepository $productionRepository;

    private bool $copyProduction;

    public function __construct(
        EventRepository $eventRepository,
        ProductionRepository $productionRepository,
        bool $copyProduction
    ) {
        $this->eventRepository = $eventRepository;
        $this->productionRepository = $productionRepository;
        $this->copyProduction = $copyProduction;
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

        // Add the new event to the same production as the original one.
        // This is not done as part of Event::copy() because productions are not event-sourced.
        if ($this->copyProduction) {
            try {
                $production = $this->productionRepository->findProductionForEventId($originalEventId);
                $this->productionRepository->addEvent($newEventId, $production);
            } catch (EntityNotFoundException $e) {
                return;
            }
        }
    }
}
