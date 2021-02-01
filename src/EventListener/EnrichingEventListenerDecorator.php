<?php

namespace CultuurNet\UDB3\EventListener;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\DomainMessage\DomainMessageEnricherInterface;

class EnrichingEventListenerDecorator implements EventListenerInterface
{
    /**
     * @var EventListenerInterface
     */
    private $decoratee;

    /**
     * @var DomainMessageEnricherInterface
     */
    private $enricher;

    /**
     * @param EventListenerInterface $decoratee
     * @param DomainMessageEnricherInterface $enricher
     */
    public function __construct(
        EventListenerInterface $decoratee,
        DomainMessageEnricherInterface $enricher
    ) {
        $this->decoratee = $decoratee;
        $this->enricher = $enricher;
    }

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage): void
    {
        if ($this->enricher->supports($domainMessage)) {
            $domainMessage = $this->enricher->enrich($domainMessage);
        }

        $this->decoratee->handle($domainMessage);
    }
}
