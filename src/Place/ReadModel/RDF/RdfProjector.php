<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\RDF\MainLanguageRepository;

final class RdfProjector implements EventListener
{
    private MainLanguageRepository $mainLanguageRepository;

    public function __construct(MainLanguageRepository $mainLanguageRepository)
    {
        $this->mainLanguageRepository = $mainLanguageRepository;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $payload = $domainMessage->getPayload();
        $granularEvents = $payload instanceof ConvertsToGranularEvents ? $payload->toGranularEvents() : [];
        $events = [$payload, ...$granularEvents];

        $mapping = [
            MainLanguageDefined::class => fn ($e) => $this->handleMainLanguageDefined($e, $domainMessage),
            TitleUpdated::class => fn ($e) => $this->handleTitleUpdated($e, $domainMessage),
        ];

        foreach ($events as $event) {
            foreach ($mapping as $class => $handler) {
                if ($event instanceof $class) {
                    $handler($event);
                }
            }
        }
    }

    private function handleMainLanguageDefined(MainLanguageDefined $event, DomainMessage $domainMessage): void
    {
        $this->mainLanguageRepository->save(
            $domainMessage->getId(),
            new Language($event->getMainLanguage()->getCode())
        );
    }

    private function handleTitleUpdated(TitleUpdated $event, DomainMessage $domainMessage): void
    {
    }
}
