<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Http\RDF\JsonToTurtleConverter;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\RDF\Editor\AddressEditor;
use CultuurNet\UDB3\RDF\Editor\GeometryEditor;
use CultuurNet\UDB3\RDF\Editor\GraphEditor;
use CultuurNet\UDB3\RDF\Editor\LabelEditor;
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

final class PlaceJsonToTurtleConverter implements JsonToTurtleConverter
{
    private IriGeneratorInterface $iriGenerator;
    private IriGeneratorInterface $termsIriGenerator;
    private DocumentRepository $documentRepository;
    private DenormalizerInterface $denormalizer;
    private AddressParser $addressParser;
    private RdfResourceFactory $rdfResourceFactory;
    private LoggerInterface $logger;

    private const TYPE_LOCATIE = 'dcterms:Location';

    private const PROPERTY_LOCATIE_TYPE = 'dcterms:type';

    private const PROPERTY_LOCATIE_NAAM = 'locn:locatorName';
    private const PROPERTY_LOCATIE_ADRES = 'locn:address';

    public function __construct(
        IriGeneratorInterface $iriGenerator,
        IriGeneratorInterface $termsIriGenerator,
        DocumentRepository $documentRepository,
        DenormalizerInterface $denormalizer,
        AddressParser $addressParser,
        RdfResourceFactory $rdfResourceFactory,
        LoggerInterface $logger
    ) {
        $this->iriGenerator = $iriGenerator;
        $this->termsIriGenerator = $termsIriGenerator;
        $this->documentRepository = $documentRepository;
        $this->denormalizer = $denormalizer;
        $this->addressParser = $addressParser;
        $this->rdfResourceFactory = $rdfResourceFactory;
        $this->logger = $logger;
    }

    public function convert(string $id): string
    {
        $iri = $this->iriGenerator->iri($id);

        $graph = new Graph($iri);
        $resource = $graph->resource($iri);

        $placeData = $this->fetchPlaceData($id);
        try {
            $place = $this->getPlace($placeData);
        } catch (\Throwable $throwable) {
            $this->logger->warning(
                'Unable to project place ' . $id . ' with invalid JSON to RDF.',
                [
                    'id' => $id,
                    'type' => 'place',
                    'exception' => $throwable->getMessage(),
                ]
            );
            throw new JsonDataCouldNotBeConverted($throwable->getMessage());
        }

        GraphEditor::for($graph, $this->rdfResourceFactory)->setGeneralProperties(
            $iri,
            self::TYPE_LOCATIE,
            DateTimeFactory::fromISO8601($placeData['created'])->format(DateTime::ATOM),
            DateTimeFactory::fromISO8601($placeData['modified'])->format(DateTime::ATOM),
        );

        $workflowStatusEditor = new WorkflowStatusEditor();
        $workflowStatusEditor->setWorkflowStatus($resource, $place->getWorkflowStatus());
        if ($place->getAvailableFrom()) {
            $workflowStatusEditor->setAvailableFrom($resource, $place->getAvailableFrom());
        }

        $this->setTitle($resource, $place->getTitle());

        $this->setTerms($resource, $place->getTerms());

        (new AddressEditor($this->addressParser, $this->rdfResourceFactory))->setAddress($resource, self::PROPERTY_LOCATIE_ADRES, $place->getAddress());

        if ($place->getGeoCoordinates()) {
            (new GeometryEditor($this->rdfResourceFactory))->setCoordinates($resource, $place->getGeoCoordinates());
        }

        if ($place->getLabels()->count() > 0) {
            (new LabelEditor())->setLabels($resource, $place->getLabels());
        }

        return trim((new Turtle())->serialise($graph, 'turtle'));
    }

    private function fetchPlaceData(string $placeId): array
    {
        $jsonDocument = $this->documentRepository->fetch($placeId);
        return $jsonDocument->getAssocBody();
    }

    private function getPlace(array $placeData): Place
    {
        /** @var ImmutablePlace $place */
        $place = $this->denormalizer->denormalize($placeData, ImmutablePlace::class);
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
}
