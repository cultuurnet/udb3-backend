<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\Commands\DeleteOnlineUrl;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;

final class DeleteOnlineUrlHandler implements CommandHandler
{
    private EventRepository $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof DeleteOnlineUrl) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->getItemId());

        $event->deleteOnlineUrl();

        $this->eventRepository->save($event);
    }
}
