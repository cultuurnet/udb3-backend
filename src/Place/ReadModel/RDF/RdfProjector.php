<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\AddressFormatter;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Address\FullAddressFormatter;
use CultuurNet\UDB3\Address\ParsedAddress;
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
use CultuurNet\UDB3\RDF\GraphEditor;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;
use DateTime;

final class RdfProjector implements EventListener
{
    private MainLanguageRepository $mainLanguageRepository;
    private GraphRepository $graphRepository;
    private IriGeneratorInterface $iriGenerator;
    private AddressParser $addressParser;
    private AddressFormatter $addressFormatter;

    private const TYPE_LOCATIE = 'dcterms:Location';
    private const TYPE_ADRES = 'locn:Address';
    private const TYPE_GEOMETRIE = 'locn:Geometry';

    private const PROPERTY_LOCATIE_WORKFLOW_STATUS = 'udb:workflowStatus';
    private const PROPERTY_LOCATIE_WORKFLOW_STATUS_READY_FOR_VALIDATION = 'https://data.publiq.be/concepts/workflowStatus/ready-for-validation';
    private const PROPERTY_LOCATIE_WORKFLOW_STATUS_APPROVED = 'https://data.publiq.be/concepts/workflowStatus/approved';
    private const PROPERTY_LOCATIE_WORKFLOW_STATUS_REJECTED = 'https://data.publiq.be/concepts/workflowStatus/rejected';
    private const PROPERTY_LOCATIE_WORKFLOW_STATUS_DELETED = 'https://data.publiq.be/concepts/workflowStatus/deleted';
    private const PROPERTY_LOCATIE_AVAILABLE_FROM = 'udb:availableFrom';

    private const PROPERTY_LOCATIE_NAAM = 'locn:locatorName';
    private const PROPERTY_LOCATIE_ADRES = 'locn:address';
    private const PROPERTY_LOCATIE_GEOMETRIE = 'locn:geometry';

    private const PROPERTY_ADRES_STRAATNAAM = 'locn:thoroughfare';
    private const PROPERTY_ADRES_HUISNUMMER = 'locn:locatorDesignator';
    private const PROPERTY_ADRES_POSTCODE = 'locn:postcode';
    private const PROPERTY_ADRES_GEMEENTENAAM = 'locn:postName';
    private const PROPERTY_ADRES_LAND = 'locn:adminUnitL1';
    private const PROPERTY_ADRES_VOLLEDIG_ADRES = 'locn:fullAddress';

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
        $this->addressFormatter = new FullAddressFormatter();
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
            self::TYPE_LOCATIE,
            $this->iriGenerator->iri(''),
            $domainMessage->getId(),
            $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM)
        );

        $eventClassToHandler = [
            MainLanguageDefined::class => fn ($e) => $this->handleMainLanguageDefined($e, $uri),
            TitleUpdated::class => fn ($e) => $this->handleTitleUpdated($e, $uri, $graph),
            TitleTranslated::class => fn ($e) => $this->handleTitleTranslated($e, $uri, $graph),
            AddressUpdated::class => fn ($e) => $this->handleAddressUpdated($e, $uri, $graph),
            AddressTranslated::class => fn ($e) => $this->handleAddressTranslated($e, $uri, $graph),
            GeoCoordinatesUpdated::class => fn ($e) => $this->handleGeoCoordinatesUpdated($e, $uri, $graph),
            Published::class => fn ($e) => $this->handlePublished($e, $uri, $graph),
            Approved::class => fn ($e) => $this->handleApproved($uri, $graph),
            Rejected::class => fn ($e) => $this->handleRejected($uri, $graph),
            FlaggedAsDuplicate::class => fn ($e) => $this->handleRejected($uri, $graph),
            FlaggedAsInappropriate::class => fn ($e) => $this->handleRejected($uri, $graph),
            PlaceDeleted::class => fn ($e) => $this->handleDeleted($uri, $graph),
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
            self::PROPERTY_LOCATIE_NAAM,
            $event->getTitle()->toNative(),
            $mainLanguage->getCode()
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function handleTitleTranslated(TitleTranslated $event, string $uri, Graph $graph): void
    {
        GraphEditor::for($graph)->replaceLanguageValue(
            $uri,
            self::PROPERTY_LOCATIE_NAAM,
            $event->getTitle()->toNative(),
            $event->getLanguage()->getCode()
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function handleAddressUpdated(AddressUpdated $event, string $uri, Graph $graph): void
    {
        $resource = $graph->resource($uri);
        $address = $event->getAddress();

        if (!$resource->hasProperty(self::PROPERTY_LOCATIE_ADRES)) {
            $resource->add(self::PROPERTY_LOCATIE_ADRES, $resource->getGraph()->newBNode());
        }

        $addressResource = $resource->getResource(self::PROPERTY_LOCATIE_ADRES);
        if ($addressResource->type() !== self::TYPE_ADRES) {
            $addressResource->setType(self::TYPE_ADRES);
        }

        $countryCode = $address->getCountryCode()->toString();
        if ($addressResource->get(self::PROPERTY_ADRES_LAND) !== $countryCode) {
            $addressResource->set(self::PROPERTY_ADRES_LAND, $countryCode);
        }

        $postalCode = $address->getPostalCode()->toNative();
        if ($addressResource->get(self::PROPERTY_ADRES_POSTCODE) !== $postalCode) {
            $addressResource->set(self::PROPERTY_ADRES_POSTCODE, $postalCode);
        }

        $parsedAddress = $this->addressParser->parse($this->addressFormatter->format($address));
        $houseNumber = $parsedAddress ? $parsedAddress->getHouseNumber() : null;
        if ($houseNumber !== null) {
            $addressResource->set(self::PROPERTY_ADRES_HUISNUMMER, $houseNumber);
        }

        $mainLanguage = $this->mainLanguageRepository->get($uri, new Language('nl'))->toString();
        $this->updateTranslatableAddressProperties($resource, $address, $parsedAddress, $mainLanguage);

        $this->graphRepository->save($uri, $graph);
    }

    private function handleAddressTranslated(AddressTranslated $event, string $uri, Graph $graph): void
    {
        $address = $event->getAddress();

        // Only update the translatable address properties. We do not update other properties that are not translatable
        // in locn like postcode, locatorDesignator or adminUnitL1 here because we assume that the values from the main
        // language (set by handleAddressUpdated) are the source of truth, and any deviation in those propeties in
        // AddressTranslated is a mistake since those properties are not translatable in reality, but they are in UDB3
        // because of a historical design flaw.
        $this->updateTranslatableAddressProperties(
            $graph->resource($uri),
            $address,
            $this->addressParser->parse($this->addressFormatter->format($address)),
            $event->getLanguage()->getCode()
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function updateTranslatableAddressProperties(
        Resource $resource,
        Address $address,
        ?ParsedAddress $parsedAddress,
        string $language
    ): void {
        /** @var Resource|null $addressResource */
        $addressResource = $resource->getResource(self::PROPERTY_LOCATIE_ADRES);
        if ($addressResource === null) {
            // This is a case that should not happen in reality, since every new place should get a locn:Address via
            // handleAddressUpdated().
            return;
        }

        // The locn:fullAddress predicate is set per language since it contains language-specific info like the street
        // name and municipality name. It is included because not all addresses can be parsed into the expected
        // thoroughfare and house number, so in those cases at least the full address is completed and consumers can
        // always try to parse it themselves if wanted.
        $graphEditor = GraphEditor::for($addressResource->getGraph())->replaceLanguageValue(
            $addressResource->getUri(),
            self::PROPERTY_ADRES_VOLLEDIG_ADRES,
            $this->addressFormatter->format($address),
            $language
        );

        // Always set the locn:postName predicate based on the Address, not the ParsedAddress, because in some cases an
        // address cannot be parsed (e.g. it's outside of Belgium, or the street address could not be parsed/found), but
        // the original address always contains the right municipality in any case.
        $graphEditor->replaceLanguageValue(
            $addressResource->getUri(),
            self::PROPERTY_ADRES_GEMEENTENAAM,
            $address->getLocality()->toNative(),
            $language
        );

        // Only set the locn:thoroughfare predicate based on the ParsedAddress (if given), not the street in the
        // original Address, because locn:thoroughfare MUST NOT contain a house number. If there is no ParsedAddress
        // remove the value for the given language instead since it will probably be outdated (if set previously).
        // Keep in mind that locn:thoroughfare is optional.
        if ($parsedAddress) {
            $graphEditor->replaceLanguageValue(
                $addressResource->getUri(),
                self::PROPERTY_ADRES_STRAATNAAM,
                $parsedAddress->getThoroughfare(),
                $language
            );
        } else {
            $graphEditor->deleteLanguageValue(
                $addressResource->getUri(),
                self::PROPERTY_ADRES_STRAATNAAM,
                $language
            );
        }
    }

    private function handleGeoCoordinatesUpdated(GeoCoordinatesUpdated $event, string $uri, Graph $graph): void
    {
        $resource = $graph->resource($uri);
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

        $this->graphRepository->save($uri, $graph);
    }

    private function handlePublished(Published $event, string $uri, Graph $graph): void
    {
        $resource = $graph->resource($uri);
        $resource->set(self::PROPERTY_LOCATIE_WORKFLOW_STATUS, new Resource(self::PROPERTY_LOCATIE_WORKFLOW_STATUS_READY_FOR_VALIDATION));

        $resource->set(
            self::PROPERTY_LOCATIE_AVAILABLE_FROM,
            new Literal($event->getPublicationDate()->format(DateTime::ATOM), null, 'xsd:dateTime')
        );

        $this->graphRepository->save($uri, $graph);
    }

    private function handleApproved(string $uri, Graph $graph): void
    {
        $resource = $graph->resource($uri);
        $resource->set(self::PROPERTY_LOCATIE_WORKFLOW_STATUS, new Resource(self::PROPERTY_LOCATIE_WORKFLOW_STATUS_APPROVED));
        $this->graphRepository->save($uri, $graph);
    }

    private function handleRejected(string $uri, Graph $graph): void
    {
        $resource = $graph->resource($uri);
        $resource->set(self::PROPERTY_LOCATIE_WORKFLOW_STATUS, new Resource(self::PROPERTY_LOCATIE_WORKFLOW_STATUS_REJECTED));
        $this->graphRepository->save($uri, $graph);
    }

    private function handleDeleted(string $uri, Graph $graph): void
    {
        $resource = $graph->resource($uri);
        $resource->set(self::PROPERTY_LOCATIE_WORKFLOW_STATUS, new Resource(self::PROPERTY_LOCATIE_WORKFLOW_STATUS_DELETED));
        $this->graphRepository->save($uri, $graph);
    }
}
