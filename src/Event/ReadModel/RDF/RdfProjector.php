<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Cdb\ExternalId\MappingServiceInterface;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\DummyLocationUpdated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\ExternalIdLocationUpdated;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\RDF\Editor\AddressEditor;
use CultuurNet\UDB3\RDF\Editor\GraphEditor;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use CultuurNet\UDB3\RDF\Editor\WorkflowStatusEditor;
use CultuurNet\UDB3\Timestamp;
use DateTime;
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class RdfProjector implements EventListener
{
    private MainLanguageRepository $mainLanguageRepository;
    private GraphRepository $graphRepository;
    private LocationIdRepository $locationIdRepository;
    private IriGeneratorInterface $iriGenerator;
    private IriGeneratorInterface $placesIriGenerator;
    private IriGeneratorInterface $termsIriGenerator;
    private AddressParser $addressParser;
    private MappingServiceInterface $mappingService;

    private const TYPE_ACTIVITEIT = 'cidoc:E7_Activity';
    private const TYPE_PERIOD = 'm8g:PeriodOfTime';
    private const TYPE_SPACE_TIME = 'cidoc:E92_Spacetime_Volume';
    private const TYPE_DATE_TIME = 'xsd:dateTime';

    private const PROPERTY_ACTIVITEIT_NAAM = 'dcterms:title';
    private const PROPERTY_ACTIVITEIT_DESCRIPTION = 'dcterms:description';
    private const PROPERTY_ACTVITEIT_LOCATIE = 'prov:atLocation';
    private const PROPERTY_ACTIVITEIT_TYPE = 'dcterms:type';

    private const PROPERTY_RUIMTE_TIJD = 'cp:ruimtetijd';
    private const PROPERTY_RUIMTE_TIJD_LOCATION = 'cidoc:P161_has_spatial_projection';
    private const PROPERTY_RUIMTE_TIJD_CALENDAR_TYPE = 'cidoc:P160_has_temporal_projection';

    private const PROPERTY_PERIOD_START = 'm8g:startTime';
    private const PROPERTY_PERIOD_END = 'm8g:endTime';

    public function __construct(
        MainLanguageRepository $mainLanguageRepository,
        GraphRepository $graphRepository,
        LocationIdRepository $locationIdRepository,
        IriGeneratorInterface $iriGenerator,
        IriGeneratorInterface $placesIriGenerator,
        IriGeneratorInterface $termsIriGenerator,
        AddressParser $addressParser,
        MappingServiceInterface $mappingService
    ) {
        $this->mainLanguageRepository = $mainLanguageRepository;
        $this->graphRepository = $graphRepository;
        $this->locationIdRepository = $locationIdRepository;
        $this->iriGenerator = $iriGenerator;
        $this->placesIriGenerator = $placesIriGenerator;
        $this->termsIriGenerator = $termsIriGenerator;
        $this->addressParser = $addressParser;
        $this->mappingService = $mappingService;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $payload = $domainMessage->getPayload();
        $granularEvents = $payload instanceof ConvertsToGranularEvents ? $payload->toGranularEvents() : [];
        $events = [$payload, ...$granularEvents];

        $iri = $this->iriGenerator->iri($domainMessage->getId());
        $graph = $this->graphRepository->get($iri);

        GraphEditor::for($graph)->setGeneralProperties(
            $iri,
            self::TYPE_ACTIVITEIT,
            $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM)
        );

        $eventClassToHandler = [
            MainLanguageDefined::class => fn ($e) => $this->handleMainLanguageDefined($e, $iri),
            TitleUpdated::class => fn ($e) => $this->handleTitleUpdated($e, $iri, $graph),
            TitleTranslated::class => fn ($e) => $this->handleTitleTranslated($e, $iri, $graph),
            Published::class => fn ($e) => $this->handlePublished($e, $iri, $graph),
            Approved::class => fn ($e) => $this->handleApproved($iri, $graph),
            Rejected::class => fn ($e) => $this->handleRejected($iri, $graph),
            FlaggedAsDuplicate::class => fn ($e) => $this->handleRejected($iri, $graph),
            FlaggedAsInappropriate::class => fn ($e) => $this->handleRejected($iri, $graph),
            EventDeleted::class => fn ($e) => $this->handleDeleted($iri, $graph),
            DescriptionUpdated::class => fn ($e) => $this->handleDescriptionUpdated($e, $iri, $graph),
            DescriptionTranslated::class => fn ($e) => $this->handleDescriptionTranslated($e, $iri, $graph),
            LocationUpdated::class => fn ($e) => $this->handleLocationUpdated($e, $iri, $graph),
            ExternalIdLocationUpdated::class => fn ($e) => $this->handleExternalIdLocationUpdated($e, $iri, $graph),
            DummyLocationUpdated::class => fn ($e) => $this->handleDummyLocationUpdated($e, $iri, $graph),
            CalendarUpdated::class => fn ($e) => $this->handleCalendarUpdated($e, $iri, $graph),
            TypeUpdated::class => fn ($e) => $this->handleTypeUpdated($e, $iri, $graph),
        ];

        foreach ($events as $event) {
            foreach ($eventClassToHandler as $class => $handler) {
                if ($event instanceof $class) {
                    $handler($event);
                }
            }
        }
    }

    private function handleMainLanguageDefined(MainLanguageDefined $event, string $iri): void
    {
        $this->mainLanguageRepository->save($iri, new Language($event->getMainLanguage()->getCode()));
    }

    private function handleTitleUpdated(TitleUpdated $event, string $iri, Graph $graph): void
    {
        $mainLanguage = $this->mainLanguageRepository->get($iri, new Language('nl'));

        GraphEditor::for($graph)->replaceLanguageValue(
            $iri,
            self::PROPERTY_ACTIVITEIT_NAAM,
            $event->getTitle()->toNative(),
            $mainLanguage->toString(),
        );

        $this->graphRepository->save($iri, $graph);
    }

    private function handleTitleTranslated(TitleTranslated $event, string $iri, Graph $graph): void
    {
        GraphEditor::for($graph)->replaceLanguageValue(
            $iri,
            self::PROPERTY_ACTIVITEIT_NAAM,
            $event->getTitle()->toNative(),
            $event->getLanguage()->getCode()
        );

        $this->graphRepository->save($iri, $graph);
    }

    private function handlePublished(Published $event, string $iri, Graph $graph): void
    {
        WorkflowStatusEditor::for($graph)->publish($iri, $event->getPublicationDate()->format(DateTime::ATOM));

        $this->graphRepository->save($iri, $graph);
    }

    private function handleApproved(string $iri, Graph $graph): void
    {
        WorkflowStatusEditor::for($graph)->approve($iri);

        $this->graphRepository->save($iri, $graph);
    }

    private function handleRejected(string $iri, Graph $graph): void
    {
        WorkflowStatusEditor::for($graph)->reject($iri);

        $this->graphRepository->save($iri, $graph);
    }

    private function handleDeleted(string $iri, Graph $graph): void
    {
        WorkflowStatusEditor::for($graph)->delete($iri);

        $this->graphRepository->save($iri, $graph);
    }

    private function handleDescriptionUpdated(DescriptionUpdated $event, string $iri, Graph $graph): void
    {
        $mainLanguage = $this->mainLanguageRepository->get($iri, new Language('nl'));

        GraphEditor::for($graph)->replaceLanguageValue(
            $iri,
            self::PROPERTY_ACTIVITEIT_DESCRIPTION,
            $event->getDescription()->toNative(),
            $mainLanguage->toString(),
        );

        $this->graphRepository->save($iri, $graph);
    }

    private function handleDescriptionTranslated(DescriptionTranslated $event, string $iri, Graph $graph): void
    {
        GraphEditor::for($graph)->replaceLanguageValue(
            $iri,
            self::PROPERTY_ACTIVITEIT_DESCRIPTION,
            $event->getDescription()->toNative(),
            $event->getLanguage()->getCode()
        );

        $this->graphRepository->save($iri, $graph);
    }

    private function handleLocationUpdated(LocationUpdated $event, string $iri, Graph $graph): void
    {
        $this->locationIdRepository->save($iri, $event->getLocationId());
        $locationIri = $this->placesIriGenerator->iri($event->getLocationId()->toString());

        AddressEditor::for($graph, $this->mainLanguageRepository, $this->addressParser)
            ->removeAddresses();

        $resource = $graph->resource($iri);

        if ($resource->hasProperty(self::PROPERTY_RUIMTE_TIJD)) {
            $spaceTimeResources = $resource->allResources(self::PROPERTY_RUIMTE_TIJD);
            foreach ($spaceTimeResources as $spaceTimeResource) {
                $spaceTimeResource->set(self::PROPERTY_RUIMTE_TIJD_LOCATION, new Resource($locationIri));
            }
        } else {
            $resource->set(self::PROPERTY_ACTVITEIT_LOCATIE, new Resource($locationIri));
        }

        $this->graphRepository->save($iri, $graph);
    }

    private function handleExternalIdLocationUpdated(ExternalIdLocationUpdated $event, string $iri, Graph $graph): void
    {
        $cdbid = $this->mappingService->getCdbId($event->getExternalId());

        if ($cdbid) {
            $this->handleLocationUpdated(
                new LocationUpdated($event->getEventId(), new LocationId($cdbid)),
                $iri,
                $graph
            );
        }
    }

    private function handleDummyLocationUpdated(DummyLocationUpdated $event, string $iri, Graph $graph): void
    {
        AddressEditor::for($graph, $this->mainLanguageRepository, $this->addressParser)
            ->addAddress($iri, $event->getDummyLocation()->getAddress(), self::PROPERTY_ACTVITEIT_LOCATIE);

        $this->graphRepository->save($iri, $graph);
    }

    private function handleCalendarUpdated(CalendarUpdated $event, string $iri, Graph $graph): void
    {
        $calendar = $event->getCalendar();

        $address = AddressEditor::for($graph, $this->mainLanguageRepository, $this->addressParser)
            ->getAddress();

        if ($calendar->getType()->sameAs(CalendarType::PERMANENT())) {
            $this->deleteAllSpaceTimeResources($iri, $graph);
            $this->addLocation($iri, $graph, $address);
            $this->graphRepository->save($iri, $graph);
            return;
        }

        $timestamps = $event->getCalendar()->getTimestamps();
        if ($calendar->getType()->sameAs(CalendarType::PERIODIC())) {
            $timestamps[] = new Timestamp(
                $calendar->getStartDate(),
                $calendar->getEndDate()
            );
        }

        $this->deleteLocation($iri, $graph);
        $this->deleteAllSpaceTimeResources($iri, $graph);

        foreach ($timestamps as $timestamp) {
            $spaceTimeResource = $this->createSpaceTimeResource($iri, $graph);
            $this->addLocationOnSpaceTimeResource($iri, $spaceTimeResource, $address);
            $this->addCalendarTypeOnSpaceTimeResource($spaceTimeResource, $timestamp);
        }

        $this->graphRepository->save($iri, $graph);
    }

    private function handleTypeUpdated(TypeUpdated $event, string $iri, Graph $graph): void
    {
        $resource = $graph->resource($iri);

        $terms = $this->termsIriGenerator->iri($event->getType()->getId());
        $resource->set(self::PROPERTY_ACTIVITEIT_TYPE, new Resource($terms));

        $this->graphRepository->save($iri, $graph);
    }

    private function deleteLocation(string $iri, Graph $graph): void
    {
        $resource = $graph->resource($iri);

        $resource->set(self::PROPERTY_ACTVITEIT_LOCATIE, null);
    }

    private function addLocation(string $iri, Graph $graph, ?Resource $address): void
    {
        $resource = $graph->resource($iri);

        $locationId = $this->locationIdRepository->get($iri);
        if ($locationId !== null) {
            $locationUri = $this->placesIriGenerator->iri($locationId->toString());
            $resource->set(self::PROPERTY_ACTVITEIT_LOCATIE, new Resource($locationUri));
            return;
        }

        if ($address !== null) {
            $resource->set(self::PROPERTY_ACTVITEIT_LOCATIE, $address);
        }
    }

    private function deleteAllSpaceTimeResources(string $iri, Graph $graph): void
    {
        $resource = $graph->resource($iri);

        /** @var Resource[] $spaceTimeResources */
        $spaceTimeResources = $resource->allResources(self::PROPERTY_RUIMTE_TIJD);
        foreach ($spaceTimeResources as $spaceTimeResource) {
            $spaceTimeResource->delete('rdf:type');
            $spaceTimeResource->set(self::PROPERTY_RUIMTE_TIJD_LOCATION, null);

            $calendarTypeResource = $spaceTimeResource->getResource(self::PROPERTY_RUIMTE_TIJD_CALENDAR_TYPE);
            $calendarTypeResource->delete('rdf:type');
            $calendarTypeResource->set(self::PROPERTY_PERIOD_START, null);
            $calendarTypeResource->set(self::PROPERTY_PERIOD_END, null);
            $spaceTimeResource->set(self::PROPERTY_RUIMTE_TIJD_CALENDAR_TYPE, null);

            $resource->delete(self::PROPERTY_RUIMTE_TIJD);
        }
    }

    private function createSpaceTimeResource(string $iri, Graph $graph): Resource
    {
        $resource = $graph->resource($iri);

        $spaceTimeResource = $resource->getGraph()->newBNode();
        $resource->add(self::PROPERTY_RUIMTE_TIJD, $spaceTimeResource);

        if ($spaceTimeResource->type() !== self::TYPE_SPACE_TIME) {
            $spaceTimeResource->setType(self::TYPE_SPACE_TIME);
        }

        return $spaceTimeResource;
    }

    private function addLocationOnSpaceTimeResource(string $iri, Resource $spaceTimeResource, ?Resource $address): void
    {
        $locationId = $this->locationIdRepository->get($iri);
        if ($locationId !== null) {
            $locationUri = $this->placesIriGenerator->iri($locationId->toString());
            $spaceTimeResource->set(self::PROPERTY_RUIMTE_TIJD_LOCATION, new Resource($locationUri));
            return;
        }

        if ($address !== null) {
            $spaceTimeResource->set(self::PROPERTY_RUIMTE_TIJD_LOCATION, $address);
        }
    }

    private function addCalendarTypeOnSpaceTimeResource(Resource $spaceTimeResource, Timestamp $timestamp): void
    {
        if (!$spaceTimeResource->hasProperty(self::PROPERTY_RUIMTE_TIJD_CALENDAR_TYPE)) {
            $spaceTimeResource->add(self::PROPERTY_RUIMTE_TIJD_CALENDAR_TYPE, $spaceTimeResource->getGraph()->newBNode());
        }

        $calendarTypeResource = $spaceTimeResource->getResource(self::PROPERTY_RUIMTE_TIJD_CALENDAR_TYPE);
        if ($calendarTypeResource->type() !== self::TYPE_PERIOD) {
            $calendarTypeResource->setType(self::TYPE_PERIOD);
        }

        $start = $timestamp->getStartDate()->format(DateTime::ATOM);
        $end = $timestamp->getEndDate()->format(DateTime::ATOM);

        $calendarTypeResource->set(self::PROPERTY_PERIOD_START, new Literal($start, null, self::TYPE_DATE_TIME));
        $calendarTypeResource->set(self::PROPERTY_PERIOD_END, new Literal($end, null, self::TYPE_DATE_TIME));
    }
}
