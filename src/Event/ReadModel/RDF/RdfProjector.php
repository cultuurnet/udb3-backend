<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use DateTime;
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class RdfProjector implements EventListener
{
    private MainLanguageRepository $mainLanguageRepository;
    private GraphRepository $graphRepository;
    private IriGeneratorInterface $iriGenerator;

    private const TYPE_ACTIVITEIT = 'cidoc:E7_Activity';
    private const TYPE_IDENTIFICATOR = 'adms:Identifier';

    private const PROPERTY_ACTIVITEIT_NAAM = 'dcterms:title';
    private const PROPERTY_ACTIVITEIT_AANGEMAAKT_OP = 'dcterms:created';
    private const PROPERTY_ACTIVITEIT_LAATST_AANGEPAST = 'dcterms:modified';
    private const PROPERTY_ACTIVITEIT_IDENTIFICATOR = 'adms:identifier';

    private const PROPERTY_ACTIVITEIT_WORKFLOW_STATUS = 'udb:workflowStatus';
    private const PROPERTY_ACTIVITEIT_WORKFLOW_STATUS_DRAFT = 'https://data.publiq.be/concepts/workflowStatus/draft';

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
        $recordedOn = $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM);
        $resource = $graph->resource($uri);

        // Set the rdf:type property, but only if it is not set before to avoid needlessly shifting it to the end of the
        // list of properties in the serialized Turtle data, since set() and setType() actually do a delete() followed
        // by add().
        if ($resource->type() !== self::TYPE_ACTIVITEIT) {
            $resource->setType(self::TYPE_ACTIVITEIT);
        }

        // Set the udb:workflowStatus property to draft if not set yet.
        if (!$resource->hasProperty(self::PROPERTY_ACTIVITEIT_WORKFLOW_STATUS)) {
            $resource->set(
                self::PROPERTY_ACTIVITEIT_WORKFLOW_STATUS,
                new Resource(self::PROPERTY_ACTIVITEIT_WORKFLOW_STATUS_DRAFT)
            );
        }

        // Set the dcterms:created property if not set yet.
        // (Otherwise it would constantly update like dcterms:modified).
        if (!$resource->hasProperty(self::PROPERTY_ACTIVITEIT_AANGEMAAKT_OP)) {
            $resource->set(
                self::PROPERTY_ACTIVITEIT_AANGEMAAKT_OP,
                new Literal($recordedOn, null, 'xsd:dateTime')
            );
        }

        // Always update the dcterms:modified property since it should change on every update to the resource.
        $resource->set(
            self::PROPERTY_ACTIVITEIT_LAATST_AANGEPAST,
            new Literal($recordedOn, null, 'xsd:dateTime')
        );

        // Add an adms:Indentifier if not set yet. Like rdf:type we only do this once to avoid needlessly shifting it
        // to the end of the properties in the serialized Turtle data.
        if (!$resource->hasProperty(self::PROPERTY_ACTIVITEIT_IDENTIFICATOR)) {
            $identificator = $graph->newBNode();
            $identificator->setType(self::TYPE_IDENTIFICATOR);
            $identificator->add(self::PROPERTY_IDENTIFICATOR_NOTATION, new Literal($uri, null, 'xsd:anyUri'));
            $identificator->add(self::PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR, new Resource(self::PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR_AGENT));
            $identificator->add(self::PROPERTY_IDENTIFICATOR_NAAMRUIMTE, new Literal($this->iriGenerator->iri(''), null, 'xsd:string'));
            $identificator->add(self::PROPERTY_IDENTIFICATOR_LOKALE_IDENTIFICATOR, new Literal($domainMessage->getId(), null, 'xsd:string'));
            $resource->add(self::PROPERTY_ACTIVITEIT_IDENTIFICATOR, $identificator);
        }

        // Add/update the generiek:versieIdentificator inside the linked adms:Identifier on every change.
        $identificator = $resource->getResource(self::PROPERTY_ACTIVITEIT_IDENTIFICATOR);
        $identificator->set(
            self::PROPERTY_IDENTIFICATOR_VERSIE_ID,
            new Literal($recordedOn, null, 'xsd:string')
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

        $resource = $graph->resource($uri);

        $this->replaceLanguageValue(
            $resource,
            self::PROPERTY_ACTIVITEIT_NAAM,
            $event->getTitle()->toNative(),
            $mainLanguage->toString(),
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
