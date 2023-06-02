<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Address\FullAddressFormatter;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\RDF\Editor\GraphEditor;
use CultuurNet\UDB3\RDF\Editor\WorkflowStatusEditor;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use DateTime;
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class RdfProjector implements EventListener
{
    private GraphRepository $graphRepository;
    private IriGeneratorInterface $iriGenerator;
    private DocumentRepository $documentRepository;
    private DenormalizerInterface $placeDenormalizer;
    private AddressParser $addressParser;

    private const TYPE_LOCATIE = 'dcterms:Location';
    private const TYPE_ADRES = 'locn:Address';
    private const TYPE_GEOMETRIE = 'locn:Geometry';

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
        GraphRepository $graphRepository,
        IriGeneratorInterface $iriGenerator,
        DocumentRepository $documentRepository,
        DenormalizerInterface $placeDenormalizer,
        AddressParser $addressParser
    ) {
        $this->graphRepository = $graphRepository;
        $this->iriGenerator = $iriGenerator;
        $this->documentRepository = $documentRepository;
        $this->placeDenormalizer = $placeDenormalizer;
        $this->addressParser = $addressParser;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        if (get_class($domainMessage->getPayload()) !== PlaceProjectedToJSONLD::class) {
            return;
        }

        $iri = $this->iriGenerator->iri($domainMessage->getPayload()->getItemId());
        $graph = new Graph($iri);
        $resource = $graph->resource($iri);

        GraphEditor::for($graph)->setGeneralProperties(
            $iri,
            self::TYPE_LOCATIE,
            $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM)
        );

        $place = $this->getPlace($domainMessage);

        (new WorkflowStatusEditor())->setWorkflowStatus($resource, $place->getWorkflowStatus());
        if ($place->getAvailableFrom()) {
            (new WorkflowStatusEditor())->setAvailableFrom($resource, $place->getAvailableFrom());
        }

        $this->setTitle($resource, $place->getTitle());

        $this->setAddress($resource, $place->getAddress());

        if ($place->getGeoCoordinates()) {
            $this->setCoordinates($resource, $place->getGeoCoordinates());
        }

        $this->graphRepository->save($iri, $graph);
    }

    private function getPlace(DomainMessage $domainMessage): Place
    {
        /** @var PlaceProjectedToJSONLD $placeProjected */
        $placeProjected = $domainMessage->getPayload();
        $jsonDocument = $this->documentRepository->fetch($placeProjected->getItemId());

        /** @var ImmutablePlace $place */
        $place = $this->placeDenormalizer->denormalize($jsonDocument->getAssocBody(), ImmutablePlace::class);
        return $place;
    }

    private function setTitle(Resource $resource, TranslatedTitle $translatedTitle): void
    {
        foreach ($translatedTitle->getLanguages() as $language) {
            $resource->addLiteral(
                self::PROPERTY_LOCATIE_NAAM,
                new Literal($translatedTitle->getTranslation($language)->toString(), $language->toString())
            );
        }
    }

    private function setAddress(Resource $resource, TranslatedAddress $translatedAddress): void
    {
        foreach ($translatedAddress->getLanguages() as $language) {
            $address = $translatedAddress->getTranslation($language);

            if (!$resource->hasProperty(self::PROPERTY_LOCATIE_ADRES)) {
                $resource->add(self::PROPERTY_LOCATIE_ADRES, $resource->getGraph()->newBNode([self::TYPE_ADRES]));
            }
            $addressResource = $resource->getResource(self::PROPERTY_LOCATIE_ADRES);

            $countryCode = $address->getCountryCode()->toString();
            if ($addressResource->get(self::PROPERTY_ADRES_LAND) !== $countryCode) {
                $addressResource->set(self::PROPERTY_ADRES_LAND, $countryCode);
            }

            $postalCode = $address->getPostalCode()->toString();
            if ($addressResource->get(self::PROPERTY_ADRES_POSTCODE) !== $postalCode) {
                $addressResource->set(self::PROPERTY_ADRES_POSTCODE, $postalCode);
            }

            $addressFormatter = new FullAddressFormatter();
            $formattedAddress = $addressFormatter->format(LegacyAddress::fromUdb3ModelAddress($address));
            $parsedAddress = $this->addressParser->parse($formattedAddress);

            $houseNumber = $parsedAddress ? $parsedAddress->getHouseNumber() : null;
            if ($houseNumber !== null) {
                $addressResource->set(self::PROPERTY_ADRES_HUISNUMMER, $houseNumber);
            }

            $addressResource->addLiteral(
                self::PROPERTY_ADRES_VOLLEDIG_ADRES,
                new Literal($formattedAddress, $language->toString())
            );

            $addressResource->addLiteral(
                self::PROPERTY_ADRES_GEMEENTENAAM,
                new Literal($address->getLocality()->toString(), $language->toString())
            );

            if ($parsedAddress && $parsedAddress->getThoroughfare() !== null) {
                $addressResource->addLiteral(
                    self::PROPERTY_ADRES_STRAATNAAM,
                    new Literal($parsedAddress->getThoroughfare(), $language->toString())
                );
            }
        }
    }

    private function setCoordinates(Resource $resource, Coordinates $coordinates): void
    {
        $gmlTemplate = '<gml:Point srsName=\'http://www.opengis.net/def/crs/OGC/1.3/CRS84\'><gml:coordinates>%s, %s</gml:coordinates></gml:Point>';
        $gmlCoordinate = sprintf($gmlTemplate, $coordinates->getLongitude()->toDouble(), $coordinates->getLatitude()->toDouble());

        if (!$resource->hasProperty(self::PROPERTY_LOCATIE_GEOMETRIE)) {
            $resource->add(self::PROPERTY_LOCATIE_GEOMETRIE, $resource->getGraph()->newBNode([self::TYPE_GEOMETRIE]));
        }
        $geometryResource = $resource->getResource(self::PROPERTY_LOCATIE_GEOMETRIE);

        $geometryResource->set(self::PROPERTY_GEOMETRIE_GML, new Literal($gmlCoordinate, null, 'geosparql:gmlLiteral'));
    }
}
