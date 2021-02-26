<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventListener;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\DomainMessage\DomainMessageEnricherInterface;

class EnrichingEventListenerDecorator implements EventListener
{
    /**
     * @var EventListener
     */
    private $decoratee;

    /**
     * @var DomainMessageEnricherInterface
     */
    private $enricher;

    public function __construct(
        EventListener $decoratee,
        DomainMessageEnricherInterface $enricher
    ) {
        $this->decoratee = $decoratee;
        $this->enricher = $enricher;
    }


    public function handle(DomainMessage $domainMessage): void
    {
        if ($this->enricher->supports($domainMessage)) {
            $domainMessage = $this->enricher->enrich($domainMessage);
        }

        $this->decoratee->handle($domainMessage);
    }
}
