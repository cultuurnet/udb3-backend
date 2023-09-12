<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Organizer\ImmutableOrganizer;
use CultuurNet\UDB3\Model\Organizer\Organizer;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\RDF\Editor\GraphEditor;
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
    private DocumentRepository $documentRepository;
    private DenormalizerInterface $organizerDenormalizer;
    private LoggerInterface $logger;

    private const TYPE_ORGANISATOR = 'cp:Organisator';

    private const PROPERTY_REALISATOR_NAAM = 'cpr:naam';
    private const PROPERTY_HOMEPAGE = 'foaf:homepage';

    public function __construct(
        GraphRepository $graphRepository,
        IriGeneratorInterface $iriGenerator,
        DocumentRepository $documentRepository,
        DenormalizerInterface $organizerDenormalizer,
        LoggerInterface $logger
    ) {
        $this->graphRepository = $graphRepository;
        $this->iriGenerator = $iriGenerator;
        $this->documentRepository = $documentRepository;
        $this->organizerDenormalizer = $organizerDenormalizer;
        $this->logger = $logger;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        if (get_class($domainMessage->getPayload()) !== OrganizerProjectedToJSONLD::class) {
            return;
        }

        $organizerId = $domainMessage->getPayload()->getId();
        $iri = $this->iriGenerator->iri($organizerId);
        $graph = new Graph($iri);
        $resource = $graph->resource($iri);

        $organizerData = $this->fetchOrganizerData($domainMessage);
        try {
            $organizer = $this->getOrganizer($organizerData);
        } catch (\Throwable $throwable) {
            $this->logger->warning(
                'Unable to project organizer ' . $organizerId . ' with invalid JSON to RDF.',
                [
                    'id' => $organizerId,
                    'type' => 'organizer',
                    'exception' => $throwable,
                ]
            );
            return;
        }

        GraphEditor::for($graph)->setGeneralProperties(
            $iri,
            self::TYPE_ORGANISATOR,
            DateTimeFactory::fromISO8601($organizerData['created'])->format(DateTime::ATOM),
            $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM)
        );

        $this->setName($resource, $organizer->getName());

        $this->setHomepage($resource, $organizer->getUrl());

        $this->graphRepository->save($iri, $graph);
    }

    private function fetchOrganizerData(DomainMessage $domainMessage): array
    {
        $organizerId = $domainMessage->getPayload()->getId();
        $organizerDocument = $this->documentRepository->fetch($organizerId);

        return $organizerDocument->getAssocBody();
    }

    private function getOrganizer(array $organizerData): Organizer
    {
        /** @var ImmutableOrganizer $organizer */
        $organizer = $this->organizerDenormalizer->denormalize($organizerData, Organizer::class);
        return $organizer;
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
