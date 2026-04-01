<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\Commands\UpdateDeparturePlaces;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;

final class UpdateDeparturePlacesHandler implements CommandHandler
{
    public function __construct(private readonly EventRepository $eventRepository)
    {
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateDeparturePlaces) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->getItemId());

        $event->updateDeparturePlaces($command->departurePlaces);

        $this->eventRepository->save($event);
    }
}
