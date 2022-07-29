<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\OSLO;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use EasyRdf\Graph;
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
}
