<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Event\Event;
use CultuurNet\UDB3\Model\Event\ImmutableEvent;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\RDF\Editor\GraphEditor;
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
    private IriGeneratorInterface $eventsIriGenerator;
    private IriGeneratorInterface $placesIriGenerator;
    private IriGeneratorInterface $termsIriGenerator;
    private DocumentRepository $documentRepository;
    private DenormalizerInterface $eventDenormalizer;

    private const TYPE_ACTIVITEIT = 'cidoc:E7_Activity';

    private const PROPERTY_ACTIVITEIT_NAAM = 'dcterms:title';
    private const PROPERTY_ACTIVITEIT_TYPE = 'dcterms:type';
    private const PROPERTY_ACTVITEIT_LOCATIE = 'prov:atLocation';

    public function __construct(
        GraphRepository $graphRepository,
        IriGeneratorInterface $eventsIriGenerator,
        IriGeneratorInterface $placesIriGenerator,
        IriGeneratorInterface $termsIriGenerator,
        DocumentRepository $documentRepository,
        DenormalizerInterface $eventDenormalizer
    ) {
        $this->graphRepository = $graphRepository;
        $this->eventsIriGenerator = $eventsIriGenerator;
        $this->placesIriGenerator = $placesIriGenerator;
        $this->termsIriGenerator = $termsIriGenerator;
        $this->documentRepository = $documentRepository;
        $this->eventDenormalizer = $eventDenormalizer;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        if (get_class($domainMessage->getPayload()) !== EventProjectedToJSONLD::class) {
            return;
        }

        $iri = $this->eventsIriGenerator->iri($domainMessage->getId());
        $graph = new Graph($iri);
        $resource = $graph->resource($iri);

        GraphEditor::for($graph)->setGeneralProperties(
            $iri,
            self::TYPE_ACTIVITEIT,
            $domainMessage->getRecordedOn()->toNative()->format(DateTime::ATOM)
        );

        $event = $this->getEvent($domainMessage);

        $this->setTitle($resource, $event->getTitle());

        $this->setTerms($resource, $event->getTerms());

        $this->setLocation($resource, $event->getPlaceReference());

        $this->graphRepository->save($iri, $graph);
    }

    private function getEvent(DomainMessage $domainMessage): Event
    {
        /** @var EventProjectedToJSONLD $eventProjectedToJSONLD */
        $eventProjectedToJSONLD = $domainMessage->getPayload();
        $jsonDocument = $this->documentRepository->fetch($eventProjectedToJSONLD->getItemId());

        /** @var ImmutableEvent $event */
        $event = $this->eventDenormalizer->denormalize($jsonDocument->getAssocBody(), ImmutableEvent::class);
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
        foreach ($terms as $term) {
            /** @var Category $term */
            if ($term->getDomain()->sameAs(new CategoryDomain('eventtype'))) {
                $terms = $this->termsIriGenerator->iri($term->getId()->toString());
                $resource->set(self::PROPERTY_ACTIVITEIT_TYPE, new Resource($terms));
            }
        }
    }

    private function setLocation(Resource $resource, PlaceReference $placeReference): void
    {
        $locationIri = $this->placesIriGenerator->iri($placeReference->getPlaceId()->toString());
        $resource->set(self::PROPERTY_ACTVITEIT_LOCATIE, new Resource($locationIri));
    }
}
