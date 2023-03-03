<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class RdfProjector implements EventListener
{
    private MainLanguageRepository $mainLanguageRepository;
    private GraphRepository $graphRepository;
    private IriGeneratorInterface $iriGenerator;

    private const PROPERTY_LOCATIE_NAAM = 'locn:geographicName';

    public function __construct(
        MainLanguageRepository $mainLanguageRepository,
        GraphRepository $graphRepository,
        IriGeneratorInterface $iriGenerator
    ) {
        $this->mainLanguageRepository = $mainLanguageRepository;
        $this->graphRepository = $graphRepository;
        $this->iriGenerator = $iriGenerator;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $payload = $domainMessage->getPayload();
        $granularEvents = $payload instanceof ConvertsToGranularEvents ? $payload->toGranularEvents() : [];
        $events = [$payload, ...$granularEvents];

        $uri = $this->iriGenerator->iri($domainMessage->getId());

        $eventClassToHandler = [
            MainLanguageDefined::class => fn ($e) => $this->handleMainLanguageDefined($e, $uri),
            TitleUpdated::class => fn ($e) => $this->handleTitleUpdated($e, $uri),
            TitleTranslated::class => fn ($e) => $this->handleTitleTranslated($e, $uri),
        ];

        foreach ($events as $event) {
            foreach ($eventClassToHandler as $class => $handler) {
                if ($event instanceof $class) {
                    $handler($event);
                }
            }
        }
    }

    private function handleMainLanguageDefined(MainLanguageDefined $event, string $uri): void
    {
        $this->mainLanguageRepository->save($uri, new Language($event->getMainLanguage()->getCode()));
    }

    private function handleTitleUpdated(TitleUpdated $event, string $uri): void
    {
        $graph = $this->graphRepository->get($uri);
        $mainLanguage = $this->mainLanguageRepository->get($uri, new Language('nl'));

        $resource = $graph->resource($uri);

        $this->replaceLanguageValue(
            $resource,
            self::PROPERTY_LOCATIE_NAAM,
            $event->getTitle()->toNative(),
            $mainLanguage->toString(),
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function handleTitleTranslated(TitleTranslated $event, string $uri): void
    {
        $graph = $this->graphRepository->get($uri);

        $resource = $graph->resource($uri);

        $this->replaceLanguageValue(
            $resource,
            self::PROPERTY_LOCATIE_NAAM,
            $event->getTitle()->toNative(),
            $event->getLanguage()->getCode()
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function replaceLanguageValue(
        Resource $resource,
        string $property,
        string $value,
        string $language
    ): void {
        // Get all literal values for the property, and key them by their language tag.
        // This will be an empty list if no value(s) were set before for this property.
        $literalValues = $resource->allLiterals($property);
        $languages = array_map(fn (Literal $literal): string => $literal->getLang(), $literalValues);
        $literalValuePerLanguage = array_combine($languages, $literalValues);

        // Override or add the new or updated value for the language.
        // If the language was set before, it will keep its original position in the list. If the language was not set
        // before it will be appended at the end of the list.
        $literalValuePerLanguage[$language] = new Literal($value, $language);

        // Remove all existing values of the property, then (re)add them in the intended order.
        $resource->delete($property);
        $resource->addLiteral($property, array_values($literalValuePerLanguage));
    }
}
