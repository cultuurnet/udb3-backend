<?php

namespace CultuurNet\UDB3\DomainMessage;

use Broadway\Domain\DomainMessage;

class CompositeDomainMessageEnricher implements DomainMessageEnricherInterface
{
    /**
     * @var DomainMessageEnricherInterface[]
     */
    private $enrichers;

    public function __construct()
    {
        $this->enrichers = [];
    }

    /**
     * @param DomainMessageEnricherInterface $domainMessageEnricher
     * @return CompositeDomainMessageEnricher
     */
    public function withEnricher(DomainMessageEnricherInterface $domainMessageEnricher)
    {
        $c = clone $this;
        $c->enrichers[] = $domainMessageEnricher;
        return $c;
    }

    /**
     * @param DomainMessage $domainMessage
     * @return bool
     */
    public function supports(DomainMessage $domainMessage)
    {
        $supports = false;

        foreach ($this->enrichers as $enricher) {
            if ($enricher->supports($domainMessage)) {
                $supports = true;
                break;
            }
        }

        return $supports;
    }

    /**
     * @param DomainMessage $domainMessage
     * @return DomainMessage
     */
    public function enrich(DomainMessage $domainMessage)
    {
        foreach ($this->enrichers as $enricher) {
            if ($enricher->supports($domainMessage)) {
                $domainMessage = $enricher->enrich($domainMessage);
            }
        }

        return $domainMessage;
    }
}
