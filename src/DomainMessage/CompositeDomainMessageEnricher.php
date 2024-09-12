<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\DomainMessage;

use Broadway\Domain\DomainMessage;

class CompositeDomainMessageEnricher implements DomainMessageEnricherInterface
{
    /**
     * @var DomainMessageEnricherInterface[]
     */
    private array $enrichers;

    public function __construct()
    {
        $this->enrichers = [];
    }

    public function withEnricher(DomainMessageEnricherInterface $domainMessageEnricher): CompositeDomainMessageEnricher
    {
        $c = clone $this;
        $c->enrichers[] = $domainMessageEnricher;
        return $c;
    }

    public function supports(DomainMessage $domainMessage): bool
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

    public function enrich(DomainMessage $domainMessage): DomainMessage
    {
        foreach ($this->enrichers as $enricher) {
            if ($enricher->supports($domainMessage)) {
                $domainMessage = $enricher->enrich($domainMessage);
            }
        }

        return $domainMessage;
    }
}
