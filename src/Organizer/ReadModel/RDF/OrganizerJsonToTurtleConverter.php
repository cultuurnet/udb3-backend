<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\RDF;

use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Http\RDF\JsonToTurtleConverter;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Organizer\ImmutableOrganizer;
use CultuurNet\UDB3\Model\Organizer\Organizer;
use CultuurNet\UDB3\Model\ValueObject\Moderation\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\RDF\Editor\AddressEditor;
use CultuurNet\UDB3\RDF\Editor\ContactPointEditor;
use CultuurNet\UDB3\RDF\Editor\GeometryEditor;
use CultuurNet\UDB3\RDF\Editor\GraphEditor;
use CultuurNet\UDB3\RDF\Editor\LabelEditor;
use CultuurNet\UDB3\RDF\JsonDataCouldNotBeConverted;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use DateTime;
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;
use EasyRdf\Serialiser\Turtle;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class OrganizerJsonToTurtleConverter implements JsonToTurtleConverter
{
    private IriGeneratorInterface $iriGenerator;
    private DocumentRepository $documentRepository;
    private DenormalizerInterface $denormalizer;
    private AddressParser $addressParser;
    private LoggerInterface $logger;

    private const TYPE_ORGANISATOR = 'cp:Organisator';

    private const PROPERTY_REALISATOR_NAAM = 'cpr:naam';
    private const PROPERTY_HOMEPAGE = 'foaf:homepage';
    private const PROPERTY_LOCATIE_ADRES = 'locn:address';
    private const PROPERTY_WORKFLOW_STATUS = 'udb:workflowStatus';

    public function __construct(
        IriGeneratorInterface $iriGenerator,
        DocumentRepository $documentRepository,
        DenormalizerInterface $denormalizer,
        AddressParser $addressParser,
        LoggerInterface $logger
    ) {
        $this->iriGenerator = $iriGenerator;
        $this->documentRepository = $documentRepository;
        $this->denormalizer = $denormalizer;
        $this->addressParser = $addressParser;
        $this->logger = $logger;
    }

    public function convert(string $id): string
    {
        $iri = $this->iriGenerator->iri($id);

        $graph = new Graph($iri);
        $resource = $graph->resource($iri);

        $organizerData = $this->fetchOrganizerData($id);
        try {
            $organizer = $this->getOrganizer($organizerData);
        } catch (\Throwable $throwable) {
            $this->logger->warning(
                'Unable to project organizer ' . $id . ' with invalid JSON to RDF.',
                [
                    'id' => $id,
                    'type' => 'organizer',
                    'exception' => $throwable->getMessage(),
                ]
            );

            throw new JsonDataCouldNotBeConverted($throwable->getMessage());
        }

        $modified = DateTimeFactory::fromISO8601($organizerData['modified'])->format(DateTime::ATOM);
        GraphEditor::for($graph)->setGeneralProperties(
            $iri,
            self::TYPE_ORGANISATOR,
            isset($organizerData['created']) ?
                DateTimeFactory::fromISO8601($organizerData['created'])->format(DateTime::ATOM) : $modified,
            $modified
        );

        $this->setWorkflowStatus($resource, $organizer->getWorkflowStatus());

        $this->setName($resource, $organizer->getName());

        if ($organizer->getUrl()) {
            $this->setHomepage($resource, $organizer->getUrl());
        }

        if ($organizer->getDescription()) {
            $this->setDescription($resource, $organizer->getDescription());
        }

        if ($organizer->getAddress()) {
            (new AddressEditor($this->addressParser))
                ->setAddress($resource, self::PROPERTY_LOCATIE_ADRES, $organizer->getAddress());
        }

        if ($organizer->getGeoCoordinates()) {
            (new GeometryEditor())
                ->setCoordinates($resource, $organizer->getGeoCoordinates());
        }

        if (!$organizer->getContactPoint()->isEmpty()) {
            (new ContactPointEditor())->setContactPoint($resource, $organizer->getContactPoint());
        }

        if ($organizer->getLabels()->count() > 0) {
            (new LabelEditor())->setLabels($resource, $organizer->getLabels());
        }

        return trim((new Turtle())->serialise($graph, 'turtle'));
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

    private function fetchOrganizerData(string $organizerId): array
    {
        $organizerDocument = $this->documentRepository->fetch($organizerId);
        return $organizerDocument->getAssocBody();
    }

    private function getOrganizer(array $organizerData): Organizer
    {
        /** @var ImmutableOrganizer $organizer */
        $organizer = $this->denormalizer->denormalize($organizerData, Organizer::class);
        return $organizer;
    }

    private function setWorkflowStatus(Resource $resource, WorkflowStatus $workflowStatus): void
    {
        $statusTemplate = 'https://data.publiq.be/concepts/workflowStatus/%s';
        $status = sprintf($statusTemplate, 'active');

        if ($workflowStatus->sameAs(WorkflowStatus::DELETED())) {
            $status = sprintf($statusTemplate, 'deleted');
        }

        $resource->set(self::PROPERTY_WORKFLOW_STATUS, new Resource($status));
    }

    private function setName(Resource $resource, TranslatedTitle $translatedTitle): void
    {
        foreach ($translatedTitle->getLanguages() as $language) {
            $resource->addLiteral(
                self::PROPERTY_REALISATOR_NAAM,
                new Literal($translatedTitle->getTranslation($language)->toString(), $language->toString())
            );
        }
    }

    private function setHomepage(Resource $resource, Url $url): void
    {
        $resource->addLiteral(self::PROPERTY_HOMEPAGE, new Literal($url->toString()));
    }
}
