<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\EventHandling;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsNot;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsReplayed;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

final class ReplayFilteringEventListener extends FilteringEventListener implements LoggerAwareInterface
{
    public function __construct(EventListener $eventListener)
    {
        parent::__construct(
            $eventListener,
            new DomainMessageIsNot(
                new DomainMessageIsReplayed()
            )
        );
    }

    public function setLogger(LoggerInterface $logger): void
    {
        if ($this->eventListener instanceof LoggerAwareInterface) {
            $this->eventListener->setLogger($logger);
        }
    }
}
