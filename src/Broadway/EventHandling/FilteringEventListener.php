<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\EventHandling;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageSpecificationInterface;

class FilteringEventListener implements EventListener
{
    protected EventListener $eventListener;

    private DomainMessageSpecificationInterface $domainMessageSpecification;

    public function __construct(
        EventListener $eventListener,
        DomainMessageSpecificationInterface $domainMessageSpecification
    ) {
        $this->eventListener = $eventListener;
        $this->domainMessageSpecification = $domainMessageSpecification;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        if ($this->domainMessageSpecification->isSatisfiedBy($domainMessage)) {
            $this->eventListener->handle($domainMessage);
        }
    }
}
