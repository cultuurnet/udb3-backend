<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Event\Commands\UpdateSubEventsStatus;
use CultuurNet\UDB3\Event\Event;

final class UpdateSubEventsStatusHandler implements CommandHandler
{
    /**
     * @var Repository
     */
    protected $offerRepository;

    public function __construct(Repository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command)
    {
        if (!($command instanceof UpdateSubEventsStatus)) {
            return;
        }

        /** @var Event $event */
        $event = $this->offerRepository->load($command->getItemId());
        $event->updateSubEventsStatus($command->getStatuses());
        $this->offerRepository->save($event);
    }
}
