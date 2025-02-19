<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\RDF\JsonToTurtleConverter;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Event\Event;
use CultuurNet\UDB3\Model\Event\ImmutableEvent;
use CultuurNet\UDB3\Model\Organizer\OrganizerReference;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\ContactPointDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\TranslatedAddressNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\MoneyNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\TariffNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\RDF\Editor\AddressEditor;
use CultuurNet\UDB3\RDF\Editor\ContactPointEditor;
use CultuurNet\UDB3\RDF\Editor\GraphEditor;
use CultuurNet\UDB3\RDF\Editor\ImageEditor;
use CultuurNet\UDB3\RDF\Editor\LabelEditor;
use CultuurNet\UDB3\RDF\Editor\OpeningHoursEditor;
use CultuurNet\UDB3\RDF\Editor\VideoEditor;
use CultuurNet\UDB3\RDF\Editor\WorkflowStatusEditor;
use CultuurNet\UDB3\RDF\JsonDataCouldNotBeConverted;
use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\RdfResourceFactory;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use DateTime;
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;
use EasyRdf\Serialiser\Turtle;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class EventJsonToTurtleConverter implements JsonToTurtleConverter
{
    private IriGeneratorInterface $eventsIriGenerator;
    private IriGeneratorInterface $placesIriGenerator;
    private IriGeneratorInterface $organizersIriGenerator;
    private IriGeneratorInterface $termsIriGenerator;
    private DocumentRepository $documentRepository;
    private DenormalizerInterface $eventDenormalizer;
    private AddressParser $addressParser;
    private LoggerInterface $logger;
    private RdfResourceFactory $rdfResourceFactory;
    private VideoNormalizer $videoNormalizer;
    private NormalizerInterface $imageNormalizer;

    private const TYPE_ACTIVITEIT = 'cidoc:E7_Activity';
    private const TYPE_SPACE_TIME = 'cidoc:E92_Spacetime_Volume';
    private const TYPE_PERIOD = 'm8g:PeriodOfTime';
    private const TYPE_DATE_TIME = 'xsd:dateTime';
    private const TYPE_LOCATIE = 'dcterms:Location';
    private const TYPE_VIRTUAL_LOCATION = 'schema:VirtualLocation';
    private const TYPE_VIRTUAL_LOCATION_URL = 'schema:URL';
    private const TYPE_BOEKINGSINFO = 'cpa:Boekingsinfo';
    private const TYPE_ORGANISATOR = 'cp:Organisator';
    private const TYPE_PRICE_SPECIFICATION = 'schema:PriceSpecification';
    private const TYPE_MONETARY_AMOUNT = 'schema:MonetaryAmount';

    private const PROPERTY_ACTIVITEIT_NAAM = 'dcterms:title';
    private const PROPERTY_ACTIVITEIT_TYPE = 'dcterms:type';
    private const PROPERTY_ACTIVITEIT_THEMA = 'cp:thema';
    private const PROPERTY_ACTVITEIT_LOCATIE = 'prov:atLocation';
    private const PROPERTY_ACTIVITEIT_DESCRIPTION = 'dcterms:description';

    private const PROPERTY_CARRIED_OUT_BY = 'cidoc:P14_carried_out_by';

    private const PROPERTY_RUIMTE_TIJD = 'cp:ruimtetijd';
    private const PROPERTY_RUIMTE_TIJD_LOCATION = 'cidoc:P161_has_spatial_projection';
    private const PROPERTY_RUIMTE_TIJD_CALENDAR_TYPE = 'cidoc:P160_has_temporal_projection';
    private const PROPERTY_LOCATIE_ADRES = 'locn:address';
    private const PROPERTY_LOCATIE_NAAM = 'locn:locatorName';
    private const PROPERTY_VIRTUAL_LOCATION = 'platform:virtueleLocatie';
    private const PROPERTY_VIRTUAL_LOCATION_URL = 'schema:url';
    private const PROPERTY_LOCATIE_TYPE = 'cpa:locatieType';

    private const PROPERTY_PERIOD_START = 'm8g:startTime';
    private const PROPERTY_PERIOD_END = 'm8g:endTime';

    private const PROPERTY_BOEKINGSINFO = 'cpa:boeking';

    private const PROPERTY_REALISATOR_NAAM = 'cpr:naam';

    private const PROPERTY_PRIJS = 'cpa:prijs';
    private const PROPERTY_PRICE = 'schema:price';
    private const PROPERTY_CURRENCY = 'schema:currency';
    private const PROPERTY_VALUE = 'schema:value';
    private const PROPERTY_PRIJS_CATEGORY = 'cpp:prijscategorie';
    private const PROPERTY_PREF_LABEL = 'skos:prefLabel';

    public function __construct(
        IriGeneratorInterface $eventsIriGenerator,
        IriGeneratorInterface $placesIriGenerator,
        IriGeneratorInterface $organizersIriGenerator,
        IriGeneratorInterface $termsIriGenerator,
        DocumentRepository $documentRepository,
        DenormalizerInterface $eventDenormalizer,
        AddressParser $addressParser,
        RdfResourceFactory $resourceFactory,
        VideoNormalizer $videoNormalizer,
        NormalizerInterface $imageNormalizer,
        LoggerInterface $logger
    ) {
        $this->eventsIriGenerator = $eventsIriGenerator;
        $this->placesIriGenerator = $placesIriGenerator;
        $this->organizersIriGenerator = $organizersIriGenerator;
        $this->termsIriGenerator = $termsIriGenerator;
        $this->documentRepository = $documentRepository;
        $this->eventDenormalizer = $eventDenormalizer;
        $this->addressParser = $addressParser;
        $this->logger = $logger;
        $this->rdfResourceFactory = $resourceFactory;
        $this->videoNormalizer = $videoNormalizer;
        $this->imageNormalizer = $imageNormalizer;
    }

    public function convert(string $id): string
    {
        $iri = $this->eventsIriGenerator->iri($id);

        $graph = new Graph($iri);
        $resource = $graph->resource($iri);

        $eventData = $this->fetchEventData($id);
        try {
            $event = $this->getEvent($eventData);
        } catch (\Throwable $throwable) {
            $this->logger->warning(
                'Unable to project event ' . $id . ' with invalid JSON to RDF.',
                [
                    'id' => $id,
                    'type' => 'event',
                    'exception' => $throwable->getMessage(),
                ]
            );
            throw new JsonDataCouldNotBeConverted($throwable->getMessage());
        }

        if (!isset($eventData['created'])) {
            $this->logger->warning(
                'Unable to project event ' . $id . ' without created date to RDF.',
                [
                    'id' => $id,
                    'type' => 'event',
                ]
            );
            throw new JsonDataCouldNotBeConverted('Event ' . $id . ' has no created date.');
        }

        GraphEditor::for($graph, $this->rdfResourceFactory)->setGeneralProperties(
            $iri,
            self::TYPE_ACTIVITEIT,
            DateTimeFactory::fromISO8601($eventData['created'])->format(DateTime::ATOM),
            DateTimeFactory::fromISO8601($eventData['modified'])->format(DateTime::ATOM),
        );

        $this->setTitle($resource, $event->getTitle());

        $this->setTerms($resource, $event->getTerms());

        if ($event->getOrganizerReference()) {
            $this->setOrganizer($resource, $event->getOrganizerReference());
        }

        if ($this->hasDummyOrganizer($event, $eventData)) {
            $organizerResource = $this->rdfResourceFactory->create($resource, self::TYPE_ORGANISATOR, $eventData['organizer']);

            $dummyOrganizerName = $this->getDummyOrganizerName($eventData['organizer'], $event->getMainLanguage());
            if (!empty($dummyOrganizerName)) {
                $this->setDummyOrganizerName($organizerResource, $dummyOrganizerName);
            }

            $this->setDummyOrganizerContactPoint($organizerResource, $eventData['organizer']);

            $resource->add(self::PROPERTY_CARRIED_OUT_BY, $organizerResource);
        }

        $workflowStatusEditor = new WorkflowStatusEditor();
        $workflowStatusEditor->setWorkflowStatus($resource, $event->getWorkflowStatus());
        if ($event->getAvailableFrom()) {
            $workflowStatusEditor->setAvailableFrom($resource, $event->getAvailableFrom());
        }

        $this->setLocatieType($resource, $event->getAttendanceMode());

        if (!$event->getAttendanceMode()->sameAs(AttendanceMode::offline())) {
            $this->setVirtualLocation($resource, $event->getOnlineUrl());
        }

        $this->setCalendarWithLocation($resource, $event, $eventData['location']);
        (new OpeningHoursEditor())->setOpeningHours($resource, $event->getCalendar(), $this->rdfResourceFactory);

        if ($event->getDescription()) {
            $this->setDescription($resource, $event->getDescription());
        }

        if (!$event->getContactPoint()->isEmpty()) {
            (new ContactPointEditor($this->rdfResourceFactory))->setContactPoint($resource, $event->getContactPoint());
        }

        if (!$event->getBookingInfo()->isEmpty()) {
            $this->setBookingInfo($resource, $event->getBookingInfo());
        }

        if ($event->getLabels()->count() > 0) {
            (new LabelEditor())->setLabels($resource, $event->getLabels());
        }

        if ($event->getPriceInfo()) {
            $this->setPriceInfo($resource, $event->getPriceInfo());
        }

        if (!$event->getVideos()->isEmpty()) {
            (new VideoEditor(
                $this->rdfResourceFactory,
                $this->videoNormalizer
            ))->setVideos($resource, $event->getVideos());
        }

        if (!$event->getImages()->isEmpty()) {
            (new ImageEditor($this->imageNormalizer, $this->rdfResourceFactory))->setImages(
                $resource,
                $event->getImages()
            );
        }

        return trim((new Turtle())->serialise($graph, 'turtle'));
    }

    private function fetchEventData(string $id): array
    {
        $jsonDocument = $this->documentRepository->fetch($id);
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

    private function setOrganizer(Resource $resource, OrganizerReference $organizerReference): void
    {
        $organizerIri = $this->organizersIriGenerator->iri($organizerReference->getOrganizerId()->toString());
        $resource->addResource(self::PROPERTY_CARRIED_OUT_BY, $organizerIri);
    }

    private function setCalendarWithLocation(Resource $resource, Event $event, array $locationData): void
    {
        $calendar = $event->getCalendar();
        $placeReference = $event->getPlaceReference();
        $mainLanguage = $event->getMainLanguage();

        if ($calendar->getType()->sameAs(CalendarType::permanent())) {
            $this->setLocation(
                $resource,
                self::PROPERTY_ACTVITEIT_LOCATIE,
                $placeReference,
                $locationData,
                $mainLanguage
            );

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
            $spaceTimeResource = $this->rdfResourceFactory->create(
                $resource,
                self::TYPE_SPACE_TIME,
                array_merge(
                    $this->getLocationResourceData($this->getDummyLocationName($locationData, $mainLanguage), $placeReference),
                    $this->getFromTo($subEvent)
                )
            );

            $resource->add(self::PROPERTY_RUIMTE_TIJD, $spaceTimeResource);

            if ($addressResource === null) {
                $addressResource = $this->setLocation(
                    $spaceTimeResource,
                    self::PROPERTY_RUIMTE_TIJD_LOCATION,
                    $placeReference,
                    $locationData,
                    $mainLanguage
                );
            } else {
                $spaceTimeResource->add(self::PROPERTY_RUIMTE_TIJD_LOCATION, $addressResource);
            }

            $calendarTypeResource = $this->rdfResourceFactory->create(
                $resource,
                self::TYPE_PERIOD,
                $this->getFromTo($subEvent)
            );

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

    private function setLocation(
        Resource $resource,
        string $property,
        PlaceReference $placeReference,
        array $locationData,
        Language $mainLanguage
    ): ?Resource {
        if ($placeReference->getPlaceId()) {
            $locationId = new LocationId($placeReference->getPlaceId()->toString());
            if (!$locationId->isNilLocation()) {
                $locationIri = $this->placesIriGenerator->iri($placeReference->getPlaceId()->toString());
                $resource->set($property, new Resource($locationIri));
            }
        }

        if ($placeReference->getAddress()) {
            $dummyLocationName = $this->getDummyLocationName($locationData, $mainLanguage);

            $locationResource = $this->rdfResourceFactory->create(
                $resource,
                self::TYPE_LOCATIE,
                $this->getLocationResourceData($dummyLocationName, $placeReference)
            );

            if ($dummyLocationName !== '') {
                $locationResource->addLiteral(
                    self::PROPERTY_LOCATIE_NAAM,
                    $dummyLocationName,
                    $mainLanguage->toString()
                );
            }

            (new AddressEditor($this->addressParser, $this->rdfResourceFactory))->setAddress(
                $locationResource,
                self::PROPERTY_LOCATIE_ADRES,
                $placeReference->getAddress()
            );

            return $locationResource;
        }

        return null;
    }

    private function getDummyLocationName(array $locationData, Language $mainLanguage): string
    {
        if (!isset($locationData['name'])) {
            return '';
        }

        if (is_string($locationData['name'])) {
            return $locationData['name'];
        }

        if (is_array($locationData['name'])) {
            $name = $locationData['name'][$mainLanguage->toString()] ?? reset($locationData['name']);
            if (is_string($name)) {
                return $name;
            }
        }

        return '';
    }

    private function setVirtualLocation(Resource $resource, ?Url $onlineUrl): void
    {
        $virtualLocationResource = $this->rdfResourceFactory->create($resource, self::TYPE_VIRTUAL_LOCATION, [
            $onlineUrl ? $onlineUrl->toString() : '',
        ]);

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

    private function setBookingInfo(Resource $resource, BookingInfo $bookingInfo): void
    {
        $bookingInfoResource = $this->rdfResourceFactory->create($resource, self::TYPE_BOEKINGSINFO, (new BookingInfoNormalizer())->normalize($bookingInfo));

        (new ContactPointEditor($this->rdfResourceFactory))->setBookingInfo($bookingInfoResource, $bookingInfo);

        $resource->add(self::PROPERTY_BOEKINGSINFO, $bookingInfoResource);
    }

    private function hasDummyOrganizer(Event $event, array $eventData): bool
    {
        return $event->getOrganizerReference() === null && isset($eventData['organizer']['name']);
    }

    private function getDummyOrganizerName(array $organizerData, Language $mainLanguage): string
    {
        if (is_string($organizerData['name'])) {
            return $organizerData['name'];
        }

        if (is_array($organizerData['name'])) {
            return $organizerData['name'][$mainLanguage->toString()] ?? reset($organizerData['name']);
        }

        return '';
    }

    private function setDummyOrganizerName(Resource $resource, string $name): void
    {
        $resource->addLiteral(self::PROPERTY_REALISATOR_NAAM, new Literal($name, 'nl'));
    }

    private function setDummyOrganizerContactPoint(Resource $organizerResource, array $contactPointData): void
    {
        (new ContactPointEditor($this->rdfResourceFactory))->setContactPoint(
            $organizerResource,
            (new ContactPointDenormalizer())->denormalize(
                $contactPointData,
                ContactPoint::class
            )
        );
    }

    private function setLocatieType(Resource $resource, AttendanceMode $attendanceMode): void
    {
        $locatieTypeTemplate = 'https://data.cultuurparticipatie.be/id/concept/Aanwezigheidsmodus/%s';
        $locatieType = sprintf($locatieTypeTemplate, 'fysiek');

        if ($attendanceMode->sameAs(AttendanceMode::online())) {
            $locatieType = sprintf($locatieTypeTemplate, 'online');
        }

        if ($attendanceMode->sameAs(AttendanceMode::mixed())) {
            $locatieType = sprintf($locatieTypeTemplate, 'hybride');
        }

        $resource->set(self::PROPERTY_LOCATIE_TYPE, new Resource($locatieType));
    }

    private function setPriceInfo(Resource $resource, PriceInfo $priceInfo): void
    {
        $basePriceResource = $this->createPrijsResource($resource, $priceInfo->getBasePrice());
        $basePriceResource->set(
            self::PROPERTY_PRIJS_CATEGORY,
            new Resource('<https://data.cultuurparticipatie.be/id/concept/PrijsCategorieType/basis>')
        );
        $resource->add(self::PROPERTY_PRIJS, $basePriceResource);

        foreach ($priceInfo->getTariffs() as $tariff) {
            $priceResource = $this->createPrijsResource($resource, $tariff);
            $priceResource->set(
                self::PROPERTY_PRIJS_CATEGORY,
                new Resource('<https://data.cultuurparticipatie.be/id/concept/PrijsCategorieType/tarief>')
            );
            $resource->add(self::PROPERTY_PRIJS, $priceResource);
        }
    }

    private function createPrijsResource(Resource $resource, Tariff $tariff): Resource
    {
        $priceSpecificationResource = $this->rdfResourceFactory->create($resource, self::TYPE_PRICE_SPECIFICATION, (new TariffNormalizer())->normalize($tariff));

        $monetaryAmountResource = $this->rdfResourceFactory->create($resource, self::TYPE_MONETARY_AMOUNT, (new MoneyNormalizer())->normalize($tariff->getPrice()));
        $monetaryAmountResource->set(
            self::PROPERTY_CURRENCY,
            new Literal($tariff->getPrice()->getCurrency()->getName(), null)
        );
        $monetaryAmountResource->set(
            self::PROPERTY_VALUE,
            new Literal((string)($tariff->getPrice()->getAmount() / 100), null, 'schema:Number')
        );
        $priceSpecificationResource->set(self::PROPERTY_PRICE, $monetaryAmountResource);

        foreach ($tariff->getName()->getLanguages() as $language) {
            /** @var TariffName $name */
            $name = $tariff->getName()->getTranslation($language);
            $priceSpecificationResource->add(
                self::PROPERTY_PREF_LABEL,
                new Literal($name->toString(), $language->toString())
            );
        }

        return $priceSpecificationResource;
    }

    private function getLocationResourceData(string $dummyLocationName, PlaceReference $placeReference): array
    {
        if ($dummyLocationName !== '' && $placeReference->getAddress() !== null) {
            return array_merge(
                ['locationName' => $dummyLocationName],
                (new TranslatedAddressNormalizer())->normalize($placeReference->getAddress())
            );
        }

        if ($dummyLocationName !== '') {
            return ['locationName' => $dummyLocationName];
        }

        if ($placeReference->getAddress() !== null) {
            return (new TranslatedAddressNormalizer())->normalize($placeReference->getAddress());
        }

        return [];
    }

    private function getFromTo(SubEvent $subEvent): array
    {
        return [
            'from' => $subEvent->getDateRange()->getFrom()->format(DateTime::ATOM),
            'to' => $subEvent->getDateRange()->getTo()->format(DateTime::ATOM),
        ];
    }
}
