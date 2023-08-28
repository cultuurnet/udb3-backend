<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\RDF\Editor\AddressEditor;
use CultuurNet\UDB3\RDF\Editor\GraphEditor;
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
    private IriGeneratorInterface $iriGenerator;
    private IriGeneratorInterface $termsIriGenerator;
    private DocumentRepository $documentRepository;
    private DenormalizerInterface $placeDenormalizer;
    private AddressParser $addressParser;
    private LoggerInterface $logger;

    private const TYPE_LOCATIE = 'dcterms:Location';
    private const TYPE_GEOMETRIE = 'locn:Geometry';

    private const PROPERTY_LOCATIE_TYPE = 'dcterms:type';

    private const PROPERTY_LOCATIE_NAAM = 'locn:locatorName';
    private const PROPERTY_LOCATIE_ADRES = 'locn:address';
    private const PROPERTY_LOCATIE_GEOMETRIE = 'locn:geometry';
    private const PROPERTY_GEOMETRIE_GML = 'geosparql:asGML';

    public function __construct(
        GraphRepository $graphRepository,
        IriGeneratorInterface $iriGenerator,
        IriGeneratorInterface $termsIriGenerator,
        DocumentRepository $documentRepository,
        DenormalizerInterface $placeDenormalizer,
        AddressParser $addressParser,
        LoggerInterface $logger
    ) {
        $this->graphRepository = $graphRepository;
        $this->iriGenerator = $iriGenerator;
        $this->termsIriGenerator = $termsIriGenerator;
        $this->documentRepository = $documentRepository;
        $this->placeDenormalizer = $placeDenormalizer;
        $this->addressParser = $addressParser;
        $this->logger = $logger;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        if (get_class($domainMessage->getPayload()) !== PlaceProjectedToJSONLD::class) {
            return;
        }

        $placeId = $domainMessage->getPayload()->getItemId();
        $iri = $this->iriGenerator->iri($placeId);
        $graph = new Graph($iri);
        $resource = $graph->resource($iri);

        $placeData = $this->fetchPlaceData($domainMessage);
        try {
            $place = $this->getPlace($placeData);
        } catch (\Throwable $throwable) {
            $this->logger->warning(
                'Unable to project place ' . $placeId . ' with invalid JSON to RDF.',
                [
                    'id' => $placeId,
                    'type' => 'place',
                    'exception' => $throwable,
                ]
            );
            return;
        }

        GraphEditor::for($graph)->setGeneralProperties(
            $iri,
            self::TYPE_LOCATIE,
            DateTimeFactory::fromISO8601($placeData['created'])->format(DateTime::ATOM),
            $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM)
        );

        (new WorkflowStatusEditor())->setWorkflowStatus($resource, $place->getWorkflowStatus());
        if ($place->getAvailableFrom()) {
            (new WorkflowStatusEditor())->setAvailableFrom($resource, $place->getAvailableFrom());
        }

        $this->setTitle($resource, $place->getTitle());

        $this->setTerms($resource, $place->getTerms());

        (new AddressEditor($this->addressParser))->setAddress($resource, self::PROPERTY_LOCATIE_ADRES, $place->getAddress());

        if ($place->getGeoCoordinates()) {
            $this->setCoordinates($resource, $place->getGeoCoordinates());
        }

        $this->graphRepository->save($iri, $graph);
    }

    private function fetchPlaceData(DomainMessage $domainMessage): array
    {
        /** @var PlaceProjectedToJSONLD $placeProjectedToJSONLD */
        $placeProjectedToJSONLD = $domainMessage->getPayload();
        $jsonDocument = $this->documentRepository->fetch($placeProjectedToJSONLD->getItemId());

        return $jsonDocument->getAssocBody();
    }

    private function getPlace(array $placeData): Place
    {
        /** @var ImmutablePlace $place */
        $place = $this->placeDenormalizer->denormalize($placeData, ImmutablePlace::class);
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

    private function setTerms(Resource $resource, Categories $terms): void
    {
        foreach ($terms as $term) {
            /** @var Category $term */
            if ($term->getDomain()->sameAs(new CategoryDomain('eventtype'))) {
                $terms = $this->termsIriGenerator->iri($term->getId()->toString());
                $resource->set(self::PROPERTY_LOCATIE_TYPE, new Resource($terms));
            }
        }
    }

    private function setCoordinates(Resource $resource, Coordinates $coordinates): void
    {
        $gmlTemplate = '<gml:Point srsName=\'http://www.opengis.net/def/crs/OGC/1.3/CRS84\'><gml:coordinates>%s, %s</gml:coordinates></gml:Point>';
        $gmlCoordinate = sprintf($gmlTemplate, $coordinates->getLongitude()->toFloat(), $coordinates->getLatitude()->toFloat());

        $geometryResource = $resource->getGraph()->newBNode([self::TYPE_GEOMETRIE]);
        $resource->add(self::PROPERTY_LOCATIE_GEOMETRIE, $geometryResource);

        $geometryResource->set(self::PROPERTY_GEOMETRIE_GML, new Literal($gmlCoordinate, null, 'geosparql:gmlLiteral'));
    }
}
