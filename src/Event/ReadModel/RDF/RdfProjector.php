<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Event\Event;
use CultuurNet\UDB3\Model\Event\ImmutableEvent;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\RDF\Editor\AddressEditor;
use CultuurNet\UDB3\RDF\Editor\GraphEditor;
use CultuurNet\UDB3\RDF\Editor\OpeningHoursEditor;
use CultuurNet\UDB3\RDF\Editor\WorkflowStatusEditor;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use DateTime;
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class RdfProjector implements EventListener
{
    private GraphRepository $graphRepository;
    private IriGeneratorInterface $eventsIriGenerator;
    private IriGeneratorInterface $placesIriGenerator;
    private IriGeneratorInterface $termsIriGenerator;
    private DocumentRepository $documentRepository;
    private DenormalizerInterface $eventDenormalizer;
    private AddressParser $addressParser;
    private LoggerInterface $logger;

    private const TYPE_ACTIVITEIT = 'cidoc:E7_Activity';
    private const TYPE_SPACE_TIME = 'cidoc:E92_Spacetime_Volume';
    private const TYPE_PERIOD = 'm8g:PeriodOfTime';
    private const TYPE_DATE_TIME = 'xsd:dateTime';
    private const TYPE_VIRTUAL_LOCATION = 'm8g:VirtualLocation';
    private const TYPE_VIRTUAL_LOCATION_URL = 'xsd:anyURI';

    private const PROPERTY_ACTIVITEIT_NAAM = 'dcterms:title';
    private const PROPERTY_ACTIVITEIT_TYPE = 'dcterms:type';
    private const PROPERTY_ACTIVITEIT_THEMA = 'cp:thema';
    private const PROPERTY_ACTVITEIT_LOCATIE = 'prov:atLocation';
    private const PROPERTY_ACTIVITEIT_DESCRIPTION = 'dcterms:description';

    private const PROPERTY_RUIMTE_TIJD = 'cp:ruimtetijd';
    private const PROPERTY_RUIMTE_TIJD_LOCATION = 'cidoc:P161_has_spatial_projection';
    private const PROPERTY_RUIMTE_TIJD_CALENDAR_TYPE = 'cidoc:P160_has_temporal_projection';

    private const PROPERTY_VIRTUAL_LOCATION = 'm8g:virtualLocation';
    private const PROPERTY_VIRTUAL_LOCATION_URL = 'schema:url';

    private const PROPERTY_PERIOD_START = 'm8g:startTime';
    private const PROPERTY_PERIOD_END = 'm8g:endTime';

    public function __construct(
        GraphRepository $graphRepository,
        IriGeneratorInterface $eventsIriGenerator,
        IriGeneratorInterface $placesIriGenerator,
        IriGeneratorInterface $termsIriGenerator,
        DocumentRepository $documentRepository,
        DenormalizerInterface $eventDenormalizer,
        AddressParser $addressParser,
        LoggerInterface $logger
    ) {
        $this->graphRepository = $graphRepository;
        $this->eventsIriGenerator = $eventsIriGenerator;
        $this->placesIriGenerator = $placesIriGenerator;
        $this->termsIriGenerator = $termsIriGenerator;
        $this->documentRepository = $documentRepository;
        $this->eventDenormalizer = $eventDenormalizer;
        $this->addressParser = $addressParser;
        $this->logger = $logger;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        if (get_class($domainMessage->getPayload()) !== EventProjectedToJSONLD::class) {
            return;
        }

        $eventId = $domainMessage->getPayload()->getItemId();
        $iri = $this->eventsIriGenerator->iri($eventId);
        $graph = new Graph($iri);
        $resource = $graph->resource($iri);

        $eventData = $this->fetchEventData($domainMessage);
        try {
            $event = $this->getEvent($eventData);
        } catch (\Throwable $throwable) {
            $this->logger->warning(
                'Unable to project event ' . $eventId . ' with invalid JSON to RDF.',
                [
                    'id' => $eventId,
                    'type' => 'event',
                    'exception' => $throwable,
                ]
            );
            return;
        }

        GraphEditor::for($graph)->setGeneralProperties(
            $iri,
            self::TYPE_ACTIVITEIT,
            DateTimeFactory::fromISO8601($eventData['created'])->format(DateTime::ATOM),
            $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM)
        );

        $this->setTitle($resource, $event->getTitle());

        $this->setTerms($resource, $event->getTerms());

        (new WorkflowStatusEditor())->setWorkflowStatus($resource, $event->getWorkflowStatus());
        if ($event->getAvailableFrom()) {
            (new WorkflowStatusEditor())->setAvailableFrom($resource, $event->getAvailableFrom());
        }

        $this->setCalendarWithLocation($resource, $event);
        (new OpeningHoursEditor())->setOpeningHours($resource, $event->getCalendar());

        if ($event->getDescription()) {
            $this->setDescription($resource, $event->getDescription());
        }

        $this->graphRepository->save($iri, $graph);
    }

    private function fetchEventData(DomainMessage $domainMessage): array
    {
        /** @var EventProjectedToJSONLD $eventProjectedToJSONLD */
        $eventProjectedToJSONLD = $domainMessage->getPayload();
        $jsonDocument = $this->documentRepository->fetch($eventProjectedToJSONLD->getItemId());

        return $jsonDocument->getAssocBody();
    }

    private function getEvent(array $eventData): Event
    {
        /** @var ImmutableEvent $event */
        $event = $this->eventDenormalizer->denormalize($eventData, ImmutableEvent::class);
        return $event;
    }

    private function setTitle(Resource $resource, TranslatedTitle $translatedTitle): void
    {
        foreach ($translatedTitle->getLanguages() as $language) {
            $resource->addLiteral(
                self::PROPERTY_ACTIVITEIT_NAAM,
                new Literal($translatedTitle->getTranslation($language)->toString(), $language->toString())
            );
        }
    }

    private function setTerms(Resource $resource, Categories $terms): void
    {
        /** @var Category $term */
        foreach ($terms as $term) {
            if ($term->getDomain()->sameAs(new CategoryDomain('eventtype'))) {
                $terms = $this->termsIriGenerator->iri($term->getId()->toString());
                $resource->set(self::PROPERTY_ACTIVITEIT_TYPE, new Resource($terms));
            }

            if ($term->getDomain()->sameAs(new CategoryDomain('theme'))) {
                $terms = $this->termsIriGenerator->iri($term->getId()->toString());
                $resource->set(self::PROPERTY_ACTIVITEIT_THEMA, new Resource($terms));
            }
        }
    }

    private function setCalendarWithLocation(Resource $resource, Event $event): void
    {
        $calendar = $event->getCalendar();
        $placeReference = $event->getPlaceReference();

        if ($calendar->getType()->sameAs(CalendarType::permanent())) {
            if ($event->getAttendanceMode()->sameAs(AttendanceMode::online())) {
                $this->setVirtualLocation($resource, $event->getOnlineUrl());
            } else {
                $this->setLocation($resource, self::PROPERTY_ACTVITEIT_LOCATIE, $placeReference);
            }

            return;
        }

        $subEvents = [];

        if ($calendar instanceof PeriodicCalendar) {
            $subEvents[] = new SubEvent(
                new DateRange(
                    $calendar->getStartDate(),
                    $calendar->getEndDate()
                ),
                $calendar->getStatus(),
                $calendar->getBookingAvailability(),
            );
        }

        if ($calendar instanceof SingleSubEventCalendar || $calendar instanceof MultipleSubEventsCalendar) {
            $subEvents = $calendar->getSubEvents();
        }

        $addressResource = null;

        foreach ($subEvents as $subEvent) {
            $spaceTimeResource = $resource->getGraph()->newBNode([self::TYPE_SPACE_TIME]);
            $resource->add(self::PROPERTY_RUIMTE_TIJD, $spaceTimeResource);

            if ($addressResource === null) {
                $addressResource = $this->setLocation($spaceTimeResource, self::PROPERTY_RUIMTE_TIJD_LOCATION, $placeReference);
            } else {
                $spaceTimeResource->add(self::PROPERTY_RUIMTE_TIJD_LOCATION, $addressResource);
            }

            $calendarTypeResource = $spaceTimeResource->getGraph()->newBNode([self::TYPE_PERIOD]);
            $spaceTimeResource->add(self::PROPERTY_RUIMTE_TIJD_CALENDAR_TYPE, $calendarTypeResource);

            $calendarTypeResource->set(
                self::PROPERTY_PERIOD_START,
                new Literal($subEvent->getDateRange()->getFrom()->format(DateTime::ATOM), null, self::TYPE_DATE_TIME)
            );
            $calendarTypeResource->set(
                self::PROPERTY_PERIOD_END,
                new Literal($subEvent->getDateRange()->getTo()->format(DateTime::ATOM), null, self::TYPE_DATE_TIME)
            );
        }
    }

    private function setLocation(Resource $resource, string $property, PlaceReference $placeReference): ?Resource
    {
        if ($placeReference->getPlaceId()) {
            $locationIri = $this->placesIriGenerator->iri($placeReference->getPlaceId()->toString());
            $resource->set($property, new Resource($locationIri));
        }

        if ($placeReference->getAddress()) {
            return (new AddressEditor($this->addressParser))->setAddress(
                $resource,
                $property,
                $placeReference->getAddress()
            );
        }

        return null;
    }

    private function setVirtualLocation(Resource $resource, ?Url $onlineUrl): void
    {
        $virtualLocationResource = $resource->getGraph()->newBNode([self::TYPE_VIRTUAL_LOCATION]);

        if ($onlineUrl) {
            $virtualLocationResource->add(
                self::PROPERTY_VIRTUAL_LOCATION_URL,
                new Literal($onlineUrl->toString(), null, self::TYPE_VIRTUAL_LOCATION_URL)
            );
        }

        $resource->add(self::PROPERTY_VIRTUAL_LOCATION, $virtualLocationResource);
    }

    private function setDescription(Resource $resource, TranslatedDescription $translatedDescription): void
    {
        foreach ($translatedDescription->getLanguages() as $language) {
            $resource->addLiteral(
                self::PROPERTY_ACTIVITEIT_DESCRIPTION,
                new Literal($translatedDescription->getTranslation($language)->toString(), $language->toString())
            );
        }
    }
}
