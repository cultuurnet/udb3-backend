<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\Commands\UpdateOnlineUrl;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;

final class UpdateOnlineUrlHandler implements CommandHandler
{
    private EventRepository $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateOnlineUrl) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->getItemId());

        $event->updateOnlineUrl($command->getOnlineUrl());

        $this->eventRepository->save($event);
    }
}
