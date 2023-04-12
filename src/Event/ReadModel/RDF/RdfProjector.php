<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\RDF\GraphEditor;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use DateTime;
use EasyRdf\Graph;

final class RdfProjector implements EventListener
{
    private MainLanguageRepository $mainLanguageRepository;
    private GraphRepository $graphRepository;
    private IriGeneratorInterface $iriGenerator;

    private const TYPE_ACTIVITEIT = 'cidoc:E7_Activity';

    private const PROPERTY_ACTIVITEIT_NAAM = 'dcterms:title';

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
        $graph = $this->graphRepository->get($uri);
        $graph = $this->setGeneralProperties($graph, $uri, $domainMessage);

        $eventClassToHandler = [
            MainLanguageDefined::class => fn ($e) => $this->handleMainLanguageDefined($e, $uri),
            TitleUpdated::class => fn ($e) => $this->handleTitleUpdated($e, $uri, $graph),
            TitleTranslated::class => fn ($e) => $this->handleTitleTranslated($e, $uri, $graph),
        ];

        foreach ($events as $event) {
            foreach ($eventClassToHandler as $class => $handler) {
                if ($event instanceof $class) {
                    $handler($event);
                }
            }
        }
    }

    private function setGeneralProperties(Graph $graph, string $uri, DomainMessage $domainMessage): Graph
    {
        GraphEditor::for($graph)->setGeneralProperties(
            $uri,
            self::TYPE_ACTIVITEIT,
            $this->iriGenerator->iri(''),
            $domainMessage->getId(),
            $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM)
        );

        return $graph;
    }

    private function handleMainLanguageDefined(MainLanguageDefined $event, string $uri): void
    {
        $this->mainLanguageRepository->save($uri, new Language($event->getMainLanguage()->getCode()));
    }

    private function handleTitleUpdated(TitleUpdated $event, string $uri, Graph $graph): void
    {
        $mainLanguage = $this->mainLanguageRepository->get($uri, new Language('nl'));

        GraphEditor::for($graph)->replaceLanguageValue(
            $uri,
            self::PROPERTY_ACTIVITEIT_NAAM,
            $event->getTitle()->toNative(),
            $mainLanguage->toString(),
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function handleTitleTranslated(TitleTranslated $event, string $uri, Graph $graph): void
    {
        GraphEditor::for($graph)->replaceLanguageValue(
            $uri,
            self::PROPERTY_ACTIVITEIT_NAAM,
            $event->getTitle()->toNative(),
            $event->getLanguage()->getCode()
        );

        $this->graphRepository->save($uri, $graph);
    }
}
