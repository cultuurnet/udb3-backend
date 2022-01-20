<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;

final class UpdateAudienceHandler implements CommandHandler
{
    private EventRepository $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateAudience) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->getItemId());

        $event->updateAudience(
            new Audience(new AudienceType($command->getAudienceType()->toString()))
        );

        $this->eventRepository->save($event);
    }
}
