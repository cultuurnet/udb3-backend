<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\Moderation\Approved;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Place\Events\Moderation\Published;
use CultuurNet\UDB3\Place\Events\Moderation\Rejected;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\RDF\Editor\AddressEditor;
use CultuurNet\UDB3\RDF\Editor\GraphEditor;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use CultuurNet\UDB3\RDF\Editor\WorkflowStatusEditor;
use EasyRdf\Graph;
use EasyRdf\Literal;
use DateTime;

final class RdfProjector implements EventListener
{
    private MainLanguageRepository $mainLanguageRepository;
    private GraphRepository $graphRepository;
    private IriGeneratorInterface $iriGenerator;
    private AddressParser $addressParser;

    private const TYPE_LOCATIE = 'dcterms:Location';
    private const TYPE_GEOMETRIE = 'locn:Geometry';

    private const PROPERTY_LOCATIE_ADRES = 'locn:address';
    private const PROPERTY_LOCATIE_NAAM = 'locn:locatorName';
    private const PROPERTY_LOCATIE_GEOMETRIE = 'locn:geometry';

    private const PROPERTY_GEOMETRIE_GML = 'geosparql:asGML';

    public function __construct(
        MainLanguageRepository $mainLanguageRepository,
        GraphRepository $graphRepository,
        IriGeneratorInterface $iriGenerator,
        AddressParser $addressParser
    ) {
        $this->mainLanguageRepository = $mainLanguageRepository;
        $this->graphRepository = $graphRepository;
        $this->iriGenerator = $iriGenerator;
        $this->addressParser = $addressParser;
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
            self::TYPE_LOCATIE,
            $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM)
        );

        $eventClassToHandler = [
            MainLanguageDefined::class => fn ($e) => $this->handleMainLanguageDefined($e, $iri),
            TitleUpdated::class => fn ($e) => $this->handleTitleUpdated($e, $iri, $graph),
            TitleTranslated::class => fn ($e) => $this->handleTitleTranslated($e, $iri, $graph),
            AddressUpdated::class => fn ($e) => $this->handleAddressUpdated($e, $iri, $graph),
            AddressTranslated::class => fn ($e) => $this->handleAddressTranslated($e, $iri, $graph),
            GeoCoordinatesUpdated::class => fn ($e) => $this->handleGeoCoordinatesUpdated($e, $iri, $graph),
            Published::class => fn ($e) => $this->handlePublished($e, $iri, $graph),
            Approved::class => fn ($e) => $this->handleApproved($iri, $graph),
            Rejected::class => fn ($e) => $this->handleRejected($iri, $graph),
            FlaggedAsDuplicate::class => fn ($e) => $this->handleRejected($iri, $graph),
            FlaggedAsInappropriate::class => fn ($e) => $this->handleRejected($iri, $graph),
            PlaceDeleted::class => fn ($e) => $this->handleDeleted($iri, $graph),
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
            self::PROPERTY_LOCATIE_NAAM,
            $event->getTitle()->toNative(),
            $mainLanguage->getCode()
        );

        $this->graphRepository->save($iri, $graph);
    }

    private function handleTitleTranslated(TitleTranslated $event, string $iri, Graph $graph): void
    {
        GraphEditor::for($graph)->replaceLanguageValue(
            $iri,
            self::PROPERTY_LOCATIE_NAAM,
            $event->getTitle()->toNative(),
            $event->getLanguage()->getCode()
        );

        $this->graphRepository->save($iri, $graph);
    }

    private function handleAddressUpdated(AddressUpdated $event, string $iri, Graph $graph): void
    {
        AddressEditor::for($graph, $this->mainLanguageRepository, $this->addressParser)
            ->addAddress($iri, AddressEditor::fromLegacyAddress($event->getAddress()), self::PROPERTY_LOCATIE_ADRES);

        $this->graphRepository->save($iri, $graph);
    }

    private function handleAddressTranslated(AddressTranslated $event, string $iri, Graph $graph): void
    {
        // Only update the translatable address properties. We do not update other properties that are not translatable
        // in locn like postcode, locatorDesignator or adminUnitL1 here because we assume that the values from the main
        // language (set by handleAddressUpdated) are the source of truth, and any deviation in those propeties in
        // AddressTranslated is a mistake since those properties are not translatable in reality, but they are in UDB3
        // because of a historical design flaw.
        AddressEditor::for($graph, $this->mainLanguageRepository, $this->addressParser)
            ->updateTranslatableAddress(
                $iri,
                AddressEditor::fromLegacyAddress($event->getAddress()),
                $event->getLanguage()->getCode(),
                self::PROPERTY_LOCATIE_ADRES
            );

        $this->graphRepository->save($iri, $graph);
    }

    private function handleGeoCoordinatesUpdated(GeoCoordinatesUpdated $event, string $iri, Graph $graph): void
    {
        $resource = $graph->resource($iri);
        $coordinates = $event->getCoordinates();

        $gmlTemplate = '<gml:Point srsName=\'http://www.opengis.net/def/crs/OGC/1.3/CRS84\'><gml:coordinates>%s, %s</gml:coordinates></gml:Point>';
        $gmlCoordinate = sprintf($gmlTemplate, $coordinates->getLongitude()->toDouble(), $coordinates->getLatitude()->toDouble());

        if (!$resource->hasProperty(self::PROPERTY_LOCATIE_GEOMETRIE)) {
            $resource->add(self::PROPERTY_LOCATIE_GEOMETRIE, $resource->getGraph()->newBNode());
        }

        $geometryResource = $resource->getResource(self::PROPERTY_LOCATIE_GEOMETRIE);
        if ($geometryResource->type() !== self::TYPE_GEOMETRIE) {
            $geometryResource->setType(self::TYPE_GEOMETRIE);
        }

        $geometryResource->set(self::PROPERTY_GEOMETRIE_GML, new Literal($gmlCoordinate, null, 'geosparql:gmlLiteral'));

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
}
