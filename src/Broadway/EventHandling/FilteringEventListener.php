<?php

namespace CultuurNet\Broadway\EventHandling;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\Broadway\Domain\DomainMessageSpecificationInterface;

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

    /**
     * @param EventListenerInterface $eventListener
     * @param DomainMessageSpecificationInterface $domainMessageSpecification
     */
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
