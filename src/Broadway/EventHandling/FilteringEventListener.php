<?php

namespace CultuurNet\UDB3\Broadway\EventHandling;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageSpecificationInterface;

class FilteringEventListener implements EventListenerInterface
{
    /**
     * @var EventListenerInterface
     */
    protected $eventListener;

    /**
     * @var DomainMessageSpecificationInterface
     */
    private $domainMessageSpecification;

    public function __construct(
        EventListenerInterface $eventListener,
        DomainMessageSpecificationInterface $domainMessageSpecification
    ) {
        $this->eventListener = $eventListener;
        $this->domainMessageSpecification = $domainMessageSpecification;
    }

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        if ($this->domainMessageSpecification->isSatisfiedBy($domainMessage)) {
            $this->eventListener->handle($domainMessage);
        }
    }
}
