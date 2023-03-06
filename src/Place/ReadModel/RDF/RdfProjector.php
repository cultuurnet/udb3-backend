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
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class RdfProjector implements EventListener
{
    private MainLanguageRepository $mainLanguageRepository;
    private GraphRepository $graphRepository;
    private IriGeneratorInterface $iriGenerator;

    private const TYPE_LOCATIE = 'dcterms:Location';
    private const TYPE_IDENTIFICATOR = 'adms:Identifier';

    private const PROPERTY_LOCATIE_AANGEMAAKT_OP = 'dcterms:created';
    private const PROPERTY_LOCATIE_LAATST_AANGEPAST = 'dcterms:modified';
    private const PROPERTY_LOCATIE_IDENTIFICATOR = 'adms:identifier';
    private const PROPERTY_LOCATIE_NAAM = 'locn:geographicName';

    private const PROPERTY_IDENTIFICATOR_NOTATION = 'skos:notation';
    private const PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR = 'dcterms:creator';
    private const PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR_AGENT = 'https://fixme.com/example/dataprovider/publiq';
    private const PROPERTY_IDENTIFICATOR_NAAMRUIMTE = 'generiek:naamruimte';
    private const PROPERTY_IDENTIFICATOR_LOKALE_IDENTIFICATOR = 'generiek:lokaleIdentificator';
    private const PROPERTY_IDENTIFICATOR_VERSIE_ID = 'generiek:versieIdentificator';

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
        $resource = $graph->resource($uri);

        if ($resource->type() !== self::TYPE_LOCATIE) {
            $resource->setType(self::TYPE_LOCATIE);
        }

        $recordedOn = $domainMessage->getRecordedOn()->toNative();
        $recordedOnLiteral = new Literal($recordedOn->format('Y-m-d\TH:i:s'), null, 'xsd:dateTime');
        if (!$resource->hasProperty(self::PROPERTY_LOCATIE_AANGEMAAKT_OP)) {
            $resource->set(self::PROPERTY_LOCATIE_AANGEMAAKT_OP, $recordedOnLiteral);
        }
        $resource->set(self::PROPERTY_LOCATIE_LAATST_AANGEPAST, $recordedOnLiteral);

        if (!$resource->hasProperty(self::PROPERTY_LOCATIE_IDENTIFICATOR)) {
            $identificator = $graph->newBNode();
            $identificator->setType(self::TYPE_IDENTIFICATOR);
            $identificator->add(self::PROPERTY_IDENTIFICATOR_NOTATION, $uri);
            $identificator->add(self::PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR, self::PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR_AGENT);
            $identificator->add(self::PROPERTY_IDENTIFICATOR_NAAMRUIMTE, new Literal($this->iriGenerator->iri(''), null, 'xsd:string'));
            $identificator->add(self::PROPERTY_IDENTIFICATOR_LOKALE_IDENTIFICATOR, new Literal($domainMessage->getId(), null, 'xsd:string'));
            $resource->add(self::PROPERTY_LOCATIE_IDENTIFICATOR, $identificator);
        }

        $identificator = $resource->getResource(self::PROPERTY_LOCATIE_IDENTIFICATOR);
        $identificator->set(self::PROPERTY_IDENTIFICATOR_VERSIE_ID, $recordedOnLiteral);

        return $graph;
    }

    private function handleMainLanguageDefined(MainLanguageDefined $event, string $uri): void
    {
        $this->mainLanguageRepository->save($uri, new Language($event->getMainLanguage()->getCode()));
    }

    private function handleTitleUpdated(TitleUpdated $event, string $uri, Graph $graph): void
    {
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

    private function handleTitleTranslated(TitleTranslated $event, string $uri, Graph $graph): void
    {
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

        // Override or add the value for the updated language.
        // If the value was set before, it will keep its original position in the list. If the value was not set before
        // it will be appended at the end of the list.
        $literalValuePerLanguage[$language] = new Literal($value, $language);

        // Remove all existing values of the property, then (re)add them in the intended order.
        $resource->delete($property);
        $resource->addLiteral($property, array_values($literalValuePerLanguage));
    }
}
