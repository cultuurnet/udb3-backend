<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
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

    private const TYPE_ACTIVITEIT = 'cidoc:E7_Activity';
    private const TYPE_PERIOD = 'm8g:PeriodOfTime';
    private const TYPE_SPACE_TIME = 'cidoc:E92_Spacetime_Volume';
    private const TYPE_DATE_TIME = 'xsd:dateTime';

    private const PROPERTY_ACTIVITEIT_NAAM = 'dcterms:title';
    private const PROPERTY_ACTIVITEIT_DESCRIPTION = 'dcterms:description';
    private const PROPERTY_ACTVITEIT_LOCATIE = 'cpa:locatie';
    private const PROPERTY_ACTIVITEIT_TYPE = 'dcterms:type';

    private const PROPERTY_RUIMTE_TIJD = 'cp:ruimtetijd';
    private const PROPERTY_RUIMTE_TIJD_LOCATION = 'cidoc:P161_has_spatial_projection';
    private const PROPERTY_RUIMTE_TIJD_CALENDAR_TYPE = 'cidoc:P160';

    private const PROPERTY_PERIOD_START = 'm8g:startTime';
    private const PROPERTY_PERIOD_END = 'm8g:endTime';

    public function __construct(
        MainLanguageRepository $mainLanguageRepository,
        GraphRepository $graphRepository,
        LocationIdRepository $locationIdRepository,
        IriGeneratorInterface $iriGenerator,
        IriGeneratorInterface $placesIriGenerator,
        IriGeneratorInterface $termsIriGenerator
    ) {
        $this->mainLanguageRepository = $mainLanguageRepository;
        $this->graphRepository = $graphRepository;
        $this->locationIdRepository = $locationIdRepository;
        $this->iriGenerator = $iriGenerator;
        $this->placesIriGenerator = $placesIriGenerator;
        $this->termsIriGenerator = $termsIriGenerator;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $payload = $domainMessage->getPayload();
        $granularEvents = $payload instanceof ConvertsToGranularEvents ? $payload->toGranularEvents() : [];
        $events = [$payload, ...$granularEvents];

        $uri = $this->iriGenerator->iri($domainMessage->getId());
        $graph = $this->graphRepository->get($uri);

        GraphEditor::for($graph)->setGeneralProperties(
            $uri,
            self::TYPE_ACTIVITEIT,
            $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM)
        );

        $eventClassToHandler = [
            MainLanguageDefined::class => fn ($e) => $this->handleMainLanguageDefined($e, $uri),
            TitleUpdated::class => fn ($e) => $this->handleTitleUpdated($e, $uri, $graph),
            TitleTranslated::class => fn ($e) => $this->handleTitleTranslated($e, $uri, $graph),
            Published::class => fn ($e) => $this->handlePublished($e, $uri, $graph),
            Approved::class => fn ($e) => $this->handleApproved($uri, $graph),
            Rejected::class => fn ($e) => $this->handleRejected($uri, $graph),
            FlaggedAsDuplicate::class => fn ($e) => $this->handleRejected($uri, $graph),
            FlaggedAsInappropriate::class => fn ($e) => $this->handleRejected($uri, $graph),
            EventDeleted::class => fn ($e) => $this->handleDeleted($uri, $graph),
            DescriptionUpdated::class => fn ($e) => $this->handleDescriptionUpdated($e, $uri, $graph),
            DescriptionTranslated::class => fn ($e) => $this->handleDescriptionTranslated($e, $uri, $graph),
            LocationUpdated::class => fn ($e) => $this->handleLocationUpdated($e, $uri, $graph),
            CalendarUpdated::class => fn ($e) => $this->handleCalendarUpdated($e, $uri, $graph),
            TypeUpdated::class => fn ($e) => $this->handleTypeUpdated($e, $uri, $graph),
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

    private function handlePublished(Published $event, string $uri, Graph $graph): void
    {
        WorkflowStatusEditor::for($graph)->publish($uri, $event->getPublicationDate()->format(DateTime::ATOM));

        $this->graphRepository->save($uri, $graph);
    }

    private function handleApproved(string $uri, Graph $graph): void
    {
        WorkflowStatusEditor::for($graph)->approve($uri);

        $this->graphRepository->save($uri, $graph);
    }

    private function handleRejected(string $uri, Graph $graph): void
    {
        WorkflowStatusEditor::for($graph)->reject($uri);

        $this->graphRepository->save($uri, $graph);
    }

    private function handleDeleted(string $uri, Graph $graph): void
    {
        WorkflowStatusEditor::for($graph)->delete($uri);

        $this->graphRepository->save($uri, $graph);
    }

    private function handleDescriptionUpdated(DescriptionUpdated $event, string $uri, Graph $graph): void
    {
        $mainLanguage = $this->mainLanguageRepository->get($uri, new Language('nl'));

        GraphEditor::for($graph)->replaceLanguageValue(
            $uri,
            self::PROPERTY_ACTIVITEIT_DESCRIPTION,
            $event->getDescription()->toNative(),
            $mainLanguage->toString(),
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function handleDescriptionTranslated(DescriptionTranslated $event, string $uri, Graph $graph): void
    {
        GraphEditor::for($graph)->replaceLanguageValue(
            $uri,
            self::PROPERTY_ACTIVITEIT_DESCRIPTION,
            $event->getDescription()->toNative(),
            $event->getLanguage()->getCode()
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function handleLocationUpdated(LocationUpdated $event, string $uri, Graph $graph): void
    {
        $this->locationIdRepository->save($uri, $event->getLocationId());

        $resource = $graph->resource($uri);

        $locationUri = $this->placesIriGenerator->iri($event->getLocationId()->toString());
        $resource->set(self::PROPERTY_ACTVITEIT_LOCATIE, new Resource($locationUri));

        $this->graphRepository->save($uri, $graph);
    }

    private function handleCalendarUpdated(CalendarUpdated $event, string $uri, Graph $graph): void
    {
        $calendar = $event->getCalendar();

        $timestamps = $event->getCalendar()->getTimestamps();
        if ($calendar->getType()->sameAs(CalendarType::PERIODIC())) {
            $timestamps[] = new Timestamp(
                $calendar->getStartDate(),
                $calendar->getEndDate()
            );
        }

        if (!empty($timestamps)) {
            $this->deleteAllSpaceTimeResources($uri, $graph);

            foreach ($timestamps as $timestamp) {
                $spaceTimeResource = $this->createSpaceTimeResource($uri, $graph);

                $this->addLocation($uri, $spaceTimeResource);

                $this->addCalendarType($spaceTimeResource, $timestamp);
            }
        }

        $this->graphRepository->save($uri, $graph);
    }

    private function handleTypeUpdated(TypeUpdated $event, string $uri, Graph $graph): void
    {
        $resource = $graph->resource($uri);

        $terms = $this->termsIriGenerator->iri($event->getType()->getId());
        $resource->set(self::PROPERTY_ACTIVITEIT_TYPE, new Resource($terms));

        $this->graphRepository->save($uri, $graph);
    }

    private function deleteAllSpaceTimeResources(string $uri, Graph $graph): void
    {
        $resource = $graph->resource($uri);

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

    private function createSpaceTimeResource(string $uri, Graph $graph): Resource
    {
        $resource = $graph->resource($uri);

        $spaceTimeResource = $resource->getGraph()->newBNode();
        $resource->add(self::PROPERTY_RUIMTE_TIJD, $spaceTimeResource);

        if ($spaceTimeResource->type() !== self::TYPE_SPACE_TIME) {
            $spaceTimeResource->setType(self::TYPE_SPACE_TIME);
        }

        return $spaceTimeResource;
    }

    private function addLocation(string $uri, Resource $spaceTimeResource): void
    {
        $locationId = $this->locationIdRepository->get($uri);
        $locationUri = $this->placesIriGenerator->iri($locationId->toString());
        $spaceTimeResource->set(self::PROPERTY_RUIMTE_TIJD_LOCATION, new Resource($locationUri));
    }

    private function addCalendarType(Resource $spaceTimeResource, Timestamp $timestamp): void
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
