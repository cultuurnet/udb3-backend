<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\OSLO;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use EasyRdf\Graph;
use EasyRdf\Parser\JsonLd as JsonLdParser;
use EasyRdf\Resource;
use EasyRdf\Serialiser\JsonLd as JsonLdSerializer;
use Exception;

final class EventOSLOProjector implements EventListener
{
    public const BASE_URI = 'https://data.vlaanderen.be/ns/cultuurparticipatie#';
    private const JSON_LD_CONTEXT = [
        'Activiteit' => 'https://cidoc-crm.org/html/cidoc_crm_v7.1.1.html#E7',
        'Activiteit.naam' => 'https://data.vlaanderen.be/ns/cultuurparticipatie#Activiteit.naam',
        'Activiteit.taal' => 'https://data.vlaanderen.be/ns/cultuurparticipatie#Activiteit.taal',
    ];

    private const TYPE_ACTIVITEIT = 'https://cidoc-crm.org/html/cidoc_crm_v7.1.1.html#E7';
    private const PROPERTY_ACTIVITEIT_NAAM = 'https://data.vlaanderen.be/ns/cultuurparticipatie#Activiteit.naam';
    private const PROPERTY_ACTIVITEIT_TAAL = 'https://data.vlaanderen.be/ns/cultuurparticipatie#Activiteit.taal';

    private DocumentRepository $osloDocumentRepository;
    private IriGeneratorInterface $eventIriGenerator;

    public function __construct(
        DocumentRepository $osloDocumentRepository,
        IriGeneratorInterface $eventIriGenerator
    ) {
        $this->osloDocumentRepository = $osloDocumentRepository;
        $this->eventIriGenerator = $eventIriGenerator;
    }

    /**
     * @uses handleEventCreated
     * @uses handleTitleUpdated
     * @uses handleMajorInfoUpdated
     */
    public function handle(DomainMessage $domainMessage): void
    {
        $handlers = [
            EventCreated::class => 'handleEventCreated',
            TitleUpdated::class => 'handleTitleUpdated',
            MajorInfoUpdated::class => 'handleMajorInfoUpdated',
        ];

        $payload = $domainMessage->getPayload();
        $payloadClassName = get_class($payload);

        if (isset($handlers[$payloadClassName])) {
            $handler = $handlers[$payloadClassName];
            $this->{$handler}($payload);
        }
    }

    private function handleEventCreated(EventCreated $eventCreated): void
    {
        $eventId = $eventCreated->getEventId();
        $uri = $this->eventIriGenerator->iri($eventId);
        $mainLanguage = $eventCreated->getMainLanguage()->getCode();
        $title = $eventCreated->getTitle()->toNative();

        $graph = new Graph();

        $activity = $graph->resource($uri);
        $activity->setType(self::TYPE_ACTIVITEIT);

        $activity->addLiteral(self::PROPERTY_ACTIVITEIT_TAAL, $mainLanguage);
        $activity->addLiteral(self::PROPERTY_ACTIVITEIT_NAAM, $title, $mainLanguage);

        $this->saveGraph($eventId, $graph);
    }

    private function handleTitleUpdated(TitleUpdated $titleUpdated): void
    {
        $eventId = $titleUpdated->getItemId();
        $uri = $this->eventIriGenerator->iri($eventId);
        $title = $titleUpdated->getTitle()->toNative();

        $graph = $this->loadGraph($eventId);
        if ($graph === null) {
            return;
        }

        $activity = $graph->resource($uri);

        // @todo Why do we need to wrap the property URI in a Resource class here to make it work?
        $lang = $activity->getLiteral(new Resource(self::PROPERTY_ACTIVITEIT_TAAL));
        if ($lang === null) {
            // @todo handle better but for now it's good to see this immediately when it happens
            throw new Exception('Could not determine "taal" of the "Activiteit"!');
            return;
        }

        $activity->set(
            self::PROPERTY_ACTIVITEIT_NAAM,
            ['type' => 'literal', 'value' => $title, 'lang' => $lang]
        );

        $this->saveGraph($eventId, $graph);
    }

    private function handleMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated): void
    {
        $titleUpdated = $majorInfoUpdated->toTitleUpdated();
        $this->handleTitleUpdated($titleUpdated);
    }

    private function saveGraph(string $eventId, Graph $graph): void
    {
        $serializer = new JsonLdSerializer();
        $jsonLd = $serializer->serialise(
            $graph,
            'jsonld',
            [
                'compact' => true,
                'context' => (object) self::JSON_LD_CONTEXT,
            ]
        );
        $document = new JsonDocument($eventId, $jsonLd);
        $this->osloDocumentRepository->save($document);
    }

    private function loadGraph(string $eventId): ?Graph
    {
        try {
            $document = $this->osloDocumentRepository->fetch($eventId);
        } catch (DocumentDoesNotExist $e) {
            return null;
        }

        $graph = new Graph(self::BASE_URI);
        $parser = new JsonLdParser();
        $parser->parse($graph, $document->getRawBody(), 'jsonld', self::BASE_URI);
        return $graph;
    }
}
