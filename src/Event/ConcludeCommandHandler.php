<?php

namespace CultuurNet\UDB3\Event;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Event\Commands\Conclude;

class ConcludeCommandHandler extends Udb3CommandHandler
{
    /**
     * @var Repository
     */
    protected $offerRepository;

    public function __construct(Repository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    /**
     * @param Conclude $conclude
     */
    public function handleConclude(Conclude $conclude)
    {
        /** @var Event $event */
        $event = $this->offerRepository->load($conclude->getItemId());

        $event->conclude();

        $this->offerRepository->save($event);
    }
}
