<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Completeness\Completeness;
use CultuurNet\UDB3\CulturefeedSlugger;
use CultuurNet\UDB3\Event\Events\Concluded;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\OrganizerServiceInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\ContactPointNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\TranslatedTariffNameNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Moderation\AvailableTo;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Offer\Events\AbstractAvailableFromUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractBookingInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractCalendarUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractContactPointUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Offer\Events\AbstractFacilitiesUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved;
use CultuurNet\UDB3\Offer\Events\AbstractLabelsImported;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractPriceInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTitleTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractTitleUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTypeUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractVideoDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractVideoEvent;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageAdded;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageRemoved;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesEvent;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesImportedFromUDB2;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageUpdated;
use CultuurNet\UDB3\Offer\Events\Image\AbstractMainImageSelected;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractApproved;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractFlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractFlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractPublished;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractRejected;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentMetaDataEnricherInterface;
use CultuurNet\UDB3\ReadModel\MultilingualJsonLDProjectorTrait;
use CultuurNet\UDB3\RecordedOn;
use CultuurNet\UDB3\SluggerInterface;
use DateTimeInterface;
use JsonException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class OfferLDProjector implements OrganizerServiceInterface
{
    use LoggerAwareTrait;

    use MultilingualJsonLDProjectorTrait;
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleUnknownEvents;
    }

    protected DocumentRepository $repository;

    protected IriGeneratorInterface $iriGenerator;

    protected IriGeneratorInterface $organizerIriGenerator;

    protected DocumentRepository $organizerRepository;

    protected JsonDocumentMetaDataEnricherInterface $jsonDocumentMetaDataEnricher;

    protected MediaObjectSerializer $mediaObjectSerializer;

    private int $nrOfRetries;

    private int $timeBetweenRetries;

    /**
     * Associative array of bases prices.
     * Key is the language, value is the translated string.
     *
     * @var string[]
     */
    private array $basePriceTranslations;

    protected SluggerInterface $slugger;

    protected VideoNormalizer $videoNormalizer;

    private Completeness $completeness;

    private ?int $playhead = null;

    /**
     * @param string[] $basePriceTranslations
     */
    public function __construct(
        DocumentRepository $repository,
        IriGeneratorInterface $iriGenerator,
        IriGeneratorInterface $organizerIriGenerator,
        DocumentRepository $organizerRepository,
        MediaObjectSerializer $mediaObjectSerializer,
        JsonDocumentMetaDataEnricherInterface $jsonDocumentMetaDataEnricher,
        array $basePriceTranslations,
        VideoNormalizer $videoNormalizer,
        Completeness $completeness
    ) {
        $this->repository = $repository;
        $this->iriGenerator = $iriGenerator;
        $this->organizerIriGenerator = $organizerIriGenerator;
        $this->organizerRepository = $organizerRepository;
        $this->jsonDocumentMetaDataEnricher = $jsonDocumentMetaDataEnricher;
        $this->mediaObjectSerializer = $mediaObjectSerializer;
        $this->basePriceTranslations = $basePriceTranslations;
        $this->videoNormalizer = $videoNormalizer;
        $this->completeness = $completeness;

        $this->slugger = new CulturefeedSlugger();

        $this->logger = new NullLogger();

        $this->nrOfRetries = 3;
        $this->timeBetweenRetries = 500;
    }

    public function setNrOfRetries(int $nrOfRetries): void
    {
        $this->nrOfRetries = $nrOfRetries;
    }

    public function setTimeBetweenRetries(int $timeBetweenRetries): void
    {
        $this->timeBetweenRetries = $timeBetweenRetries;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $this->playhead = $domainMessage->getPlayhead();

        $event = $domainMessage->getPayload();

        $eventName = get_class($event);
        $eventHandlers = $this->getEventHandlers();

        if (isset($eventHandlers[$eventName])) {
            $handler = $eventHandlers[$eventName];
            $jsonDocuments = call_user_func([$this, $handler], $event, $domainMessage);
        } elseif ($methodName = $this->getHandleMethodName($event)) {
            $jsonDocuments = $this->{$methodName}($event, $domainMessage);
        } else {
            return;
        }

        if (!$jsonDocuments) {
            return;
        }

        if (!is_array($jsonDocuments)) {
            $jsonDocuments = [$jsonDocuments];
        }

        foreach ($jsonDocuments as $jsonDocument) {
            $jsonDocument = $this->jsonDocumentMetaDataEnricher->enrich($jsonDocument, $domainMessage->getMetadata());
            $jsonDocument = $this->updateModified($jsonDocument, $domainMessage);
            $jsonDocument = $this->updateCompleteness($jsonDocument);
            $jsonDocument = $this->updatePlayhead($jsonDocument, $domainMessage);

            $this->repository->save($jsonDocument);
        }
    }

    /**
     * @return array<string,string>
     */
    private function getEventHandlers(): array
    {
        $events = [];

        foreach (get_class_methods($this) as $method) {
            $matches = [];

            if (preg_match('/^apply(.+)$/', $method, $matches)) {
                $event = $matches[1];
                $classNameMethod = 'get' . $event . 'ClassName';

                if (method_exists($this, $classNameMethod)) {
                    $eventFullClassName = call_user_func([$this, $classNameMethod]);
                    $events[$eventFullClassName] = $method;
                }
            }
        }

        return $events;
    }

    abstract protected function getLabelAddedClassName(): string;

    abstract protected function getLabelRemovedClassName(): string;

    abstract protected function getLabelsImportedClassName(): string;

    abstract protected function getImageAddedClassName(): string;

    abstract protected function getImageRemovedClassName(): string;

    abstract protected function getImageUpdatedClassName(): string;

    abstract protected function getMainImageSelectedClassName(): string;

    abstract protected function getVideoAddedClassName(): string;

    abstract protected function getVideoDeletedClassName(): string;

    abstract protected function getVideoUpdatedClassName(): string;

    abstract protected function getTitleTranslatedClassName(): string;

    abstract protected function getTitleUpdatedClassName(): string;

    abstract protected function getDescriptionTranslatedClassName(): string;

    abstract protected function getOrganizerUpdatedClassName(): string;

    abstract protected function getOrganizerDeletedClassName(): string;

    abstract protected function getBookingInfoUpdatedClassName(): string;

    abstract protected function getPriceInfoUpdatedClassName(): string;

    abstract protected function getContactPointUpdatedClassName(): string;

    abstract protected function getDescriptionUpdatedClassName(): string;

    abstract protected function getDescriptionDeletedClassName(): string;

    abstract protected function getCalendarUpdatedClassName(): string;

    abstract protected function getTypicalAgeRangeUpdatedClassName(): string;

    abstract protected function getTypicalAgeRangeDeletedClassName(): string;

    abstract protected function getAvailableFromUpdatedClassName(): string;

    abstract protected function getPublishedClassName(): string;

    abstract protected function getApprovedClassName(): string;

    abstract protected function getRejectedClassName(): string;

    abstract protected function getFlaggedAsDuplicateClassName(): string;

    abstract protected function getFlaggedAsInappropriateClassName(): string;

    abstract protected function getImagesImportedFromUdb2ClassName(): string;

    abstract protected function getImagesUpdatedFromUdb2ClassName(): string;

    abstract protected function getTypeUpdatedClassName(): string;

    abstract protected function getFacilitiesUpdatedClassName(): string;

    protected function applyTypeUpdated(AbstractTypeUpdated $typeUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($typeUpdated);

        return $this->updateTerm($document, $typeUpdated->getType());
    }

    protected function updateTerm(JsonDocument $document, Category $category): JsonDocument
    {
        $offerLD = $document->getBody();

        $oldTerms = property_exists($offerLD, 'terms') ? $offerLD->terms : [];
        $newTerm = (object)(new CategoryNormalizer())->normalize($category);

        $newTerms = array_filter(
            $oldTerms,
            function ($term) use ($category) {
                return !property_exists($term, 'domain') || $term->domain !== $category->getDomain()->toString();
            }
        );

        array_push($newTerms, $newTerm);

        $offerLD->terms = array_values($newTerms);

        return $document->withBody($offerLD);
    }

    protected function applyFacilitiesUpdated(AbstractFacilitiesUpdated $facilitiesUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($facilitiesUpdated);

        $offerLd = $document->getBody();

        $terms = isset($offerLd->terms) ? $offerLd->terms : [];

        // Remove all old facilities + get numeric keys.
        $terms = array_values(array_filter(
            $terms,
            function ($term) {
                return $term->domain !== CategoryDomain::facility()->toString();
            }
        ));

        // Add the new facilities.
        foreach ($facilitiesUpdated->getFacilities() as $facility) {
            $terms[] = (object)(new CategoryNormalizer())->normalize($facility);
        }

        $offerLd->terms = $terms;

        return $document->withBody($offerLd);
    }

    protected function applyLabelAdded(AbstractLabelAdded $labelAdded): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($labelAdded);

        $offerLd = $document->getBody();

        // Check the visibility of the label to update the right property.
        $labelsProperty = $labelAdded->isLabelVisible() ? 'labels' : 'hiddenLabels';

        $labels = isset($offerLd->{$labelsProperty}) ? $offerLd->{$labelsProperty} : [];
        $label = $labelAdded->getLabelName();

        $labels[] = $label;
        $offerLd->{$labelsProperty} = array_unique($labels);

        return $document->withBody($offerLd);
    }

    protected function applyLabelRemoved(AbstractLabelRemoved $labelRemoved): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($labelRemoved);

        $offerLd = $document->getBody();

        // Don't presume that the label visibility is correct when removing.
        // So iterate over both the visible and invisible labels.
        $labelsProperties = ['labels', 'hiddenLabels'];

        foreach ($labelsProperties as $labelsProperty) {
            if (isset($offerLd->{$labelsProperty}) && is_array($offerLd->{$labelsProperty})) {
                $offerLd->{$labelsProperty} = array_filter(
                    $offerLd->{$labelsProperty},
                    function ($label) use ($labelRemoved) {
                        return strcasecmp($labelRemoved->getLabelName(), $label) !== 0;
                    }
                );
                // Ensure array keys start with 0 so json_encode() does encode it
                // as an array and not as an object.
                if (count($offerLd->{$labelsProperty}) > 0) {
                    $offerLd->{$labelsProperty} = array_values($offerLd->{$labelsProperty});
                } else {
                    unset($offerLd->{$labelsProperty});
                }
            }
        }

        return $document->withBody($offerLd);
    }

    protected function applyLabelsImported(AbstractLabelsImported $labelsImported): JsonDocument
    {
        // Just return the JSON body without any changes, but this triggers a playhead update.
        return $this->loadDocumentFromRepository($labelsImported);
    }

    protected function applyImageAdded(AbstractImageAdded $imageAdded): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($imageAdded);

        $offerLd = $document->getBody();
        $offerLd->mediaObject = isset($offerLd->mediaObject) ? $offerLd->mediaObject : [];

        $imageData = $this->mediaObjectSerializer->serialize($imageAdded->getImage());
        $offerLd->mediaObject[] = $imageData;

        if (count($offerLd->mediaObject) === 1) {
            $offerLd->image = $imageData['contentUrl'];
        }

        return $document->withBody($offerLd);
    }

    protected function applyImageUpdated(AbstractImageUpdated $imageUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($imageUpdated);

        $offerLd = $document->getBody();

        if (!isset($offerLd->mediaObject)) {
            throw new \Exception('The image to update could not be found.');
        }

        $updatedMediaObjects = [];

        foreach ($offerLd->mediaObject as $mediaObject) {
            $mediaObjectMatches = (
                strpos(
                    $mediaObject->{'@id'},
                    $imageUpdated->getMediaObjectId()
                ) > 0
            );

            if ($mediaObjectMatches) {
                $mediaObject->description = $imageUpdated->getDescription();
                $mediaObject->copyrightHolder = $imageUpdated->getCopyrightHolder();
                if ($imageUpdated->getLanguage()) {
                    $mediaObject->inLanguage = $imageUpdated->getLanguage();
                }

                $updatedMediaObjects[] = $mediaObject;
            }
        };

        if (empty($updatedMediaObjects)) {
            throw new \Exception('The image to update could not be found.');
        }

        return $document->withBody($offerLd);
    }

    protected function applyImageRemoved(AbstractImageRemoved $imageRemoved): ?JsonDocument
    {
        $document = $this->loadDocumentFromRepository($imageRemoved);

        $offerLd = $document->getBody();

        // Nothing to remove if there are no media objects!
        if (!isset($offerLd->mediaObject)) {
            return null;
        }

        $imageId = $imageRemoved->getImage()->getMediaObjectId()->toString();
        $imageUrl = $imageRemoved->getImage()->getSourceLocation()->toString();

        /**
         * @return bool
         *  Returns true when the media object does not match the image to remove.
         */
        $shouldNotBeRemoved = function (object $mediaObject) use ($imageId) {
            $containsId = !!strpos($mediaObject->{'@id'}, $imageId);
            return !$containsId;
        };

        // Remove any media objects that match the image.
        $filteredMediaObjects = array_filter(
            $offerLd->mediaObject,
            $shouldNotBeRemoved
        );

        // Unset the main image if it matches the removed image
        // stripping the protocol before comparison is done for edge cases where an offer has multiple images
        // @see https://jira.uitdatabank.be/browse/III-4684
        if (isset($offerLd->image) && stristr($offerLd->{'image'}, ':') === stristr($imageUrl, ':')) {
            unset($offerLd->{'image'});
        }

        if (!isset($offerLd->image) && count($filteredMediaObjects) > 0) {
            $offerLd->image = array_values($filteredMediaObjects)[0]->contentUrl;
        }

        // If no media objects are left remove the attribute and the imageUrl.
        // @see https://jira.uitdatabank.be/browse/III-4684
        if (empty($filteredMediaObjects)) {
            unset($offerLd->{'mediaObject'});
            unset($offerLd->{'image'});
        } else {
            $offerLd->mediaObject = array_values($filteredMediaObjects);
        }

        return $document->withBody($offerLd);
    }

    protected function applyMainImageSelected(AbstractMainImageSelected $mainImageSelected): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($mainImageSelected);
        $offerLd = $document->getBody();
        $imageId = $mainImageSelected->getImage()->getMediaObjectId();
        $mediaObjectMatcher = function ($matchingMediaObject, $currentMediaObject) use ($imageId) {
            if (!$matchingMediaObject && $this->mediaObjectMatchesId($currentMediaObject, $imageId)) {
                $matchingMediaObject = $currentMediaObject;
            }

            return $matchingMediaObject;
        };
        $mediaObject = array_reduce(
            $offerLd->mediaObject,
            $mediaObjectMatcher
        );

        $offerLd->image = $mediaObject->contentUrl;

        return $document->withBody($offerLd);
    }

    protected function mediaObjectMatchesId(object $mediaObject, Uuid $mediaObjectId): bool
    {
        return strpos($mediaObject->{'@id'}, $mediaObjectId->toString()) > 0;
    }

    protected function applyVideoAdded(AbstractVideoEvent $videoAdded): JsonDocument
    {
        $document = $this->loadDocumentFromRepositoryByItemId($videoAdded->getItemId());

        $offerLd = $document->getBody();
        $offerLd->videos = $offerLd->videos ?? [];
        $offerLd->videos[] = $this->videoNormalizer->normalize($videoAdded->getVideo());

        return $document->withBody($offerLd);
    }

    protected function applyVideoDeleted(AbstractVideoDeleted $videoDeleted): JsonDocument
    {
        $document = $this->loadDocumentFromRepositoryByItemId($videoDeleted->getItemId());

        $offerLd = $document->getBody();

        $offerLd->videos = array_values(array_filter(
            $offerLd->videos,
            static fn ($video) => $video->id !== $videoDeleted->getVideoId()
        ));

        if (count($offerLd->videos) === 0) {
            unset($offerLd->videos);
        }

        return $document->withBody($offerLd);
    }

    protected function applyVideoUpdated(AbstractVideoEvent $videoUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepositoryByItemId($videoUpdated->getItemId());

        $offerLd = $document->getBody();

        $offerLd->videos = array_values(array_map(
            fn ($video) => $video->id === $videoUpdated->getVideo()->getId() ?
                $this->videoNormalizer->normalize($videoUpdated->getVideo()) : $video,
            $offerLd->videos
        ));

        return $document->withBody($offerLd);
    }

    protected function applyTitleTranslated(AbstractTitleTranslated $titleTranslated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($titleTranslated);

        $offerLd = $document->getBody();
        $offerLd->name->{$titleTranslated->getLanguage()->getCode()} = $titleTranslated->getTitle();

        return $document->withBody($offerLd);
    }

    protected function applyTitleUpdated(AbstractTitleUpdated $titleUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($titleUpdated);
        $offerLd = $document->getBody();
        $mainLanguage = $offerLd->mainLanguage ?? 'nl';

        $offerLd->name->{$mainLanguage} = $titleUpdated->getTitle();

        return $document->withBody($offerLd);
    }

    protected function applyDescriptionTranslated(
        AbstractDescriptionTranslated $descriptionTranslated
    ): JsonDocument {
        $document = $this->loadDocumentFromRepository($descriptionTranslated);

        $offerLd = $document->getBody();
        $languageCode = $descriptionTranslated->getLanguage()->getCode();
        $description = $descriptionTranslated->getDescription()->toString();
        if (empty($offerLd->description)) {
            $offerLd->description = new \stdClass();
        }
        $offerLd->description->{$languageCode} = $description;

        return $document->withBody($offerLd);
    }

    protected function applyCalendarUpdated(AbstractCalendarUpdated $calendarUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($calendarUpdated)
            ->apply(OfferUpdate::calendar($calendarUpdated->getCalendar()));

        $offerLd = $document->getBody();

        $offerLd->availableTo = AvailableTo::createFromCalendar(
            $calendarUpdated->getCalendar(),
            null
        )->format(DateTimeInterface::ATOM);

        return $document->withBody($offerLd);
    }

    protected function applyOrganizerUpdated(AbstractOrganizerUpdated $organizerUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($organizerUpdated);

        $offerLd = $document->getBody();

        $offerLd->organizer = [
                '@type' => 'Organizer',
            ] + $this->organizerJSONLD($organizerUpdated->getOrganizerId());

        return $document->withBody($offerLd);
    }

    protected function applyOrganizerDeleted(AbstractOrganizerDeleted $organizerDeleted): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($organizerDeleted);

        $offerLd = $document->getBody();

        unset($offerLd->organizer);

        return $document->withBody($offerLd);
    }

    protected function applyBookingInfoUpdated(AbstractBookingInfoUpdated $bookingInfoUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($bookingInfoUpdated);

        $offerLd = $document->getBody();

        $bookingInfoNormalizer = new BookingInfoNormalizer();
        if (empty($bookingInfoNormalizer->normalize($bookingInfoUpdated->getBookingInfo()))) {
            unset($offerLd->bookingInfo);
        } else {
            $offerLd->bookingInfo = $bookingInfoNormalizer->normalize($bookingInfoUpdated->getBookingInfo());
        }

        return $document->withBody($offerLd);
    }

    protected function applyPriceInfoUpdated(AbstractPriceInfoUpdated $priceInfoUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($priceInfoUpdated);

        $offerLd = $document->getBody();
        $offerLd->priceInfo = [];

        $basePrice = $priceInfoUpdated->getPriceInfo()->getBasePrice();

        $offerLd->priceInfo[] = [
            'category' => 'base',
            'name' => $this->basePriceTranslations,
            'price' => $basePrice->getPrice()->getAmount() / 100,
            'priceCurrency' => $basePrice->getPrice()->getCurrency()->getName(),
        ];

        $translatedTariffNameNormalizer = new TranslatedTariffNameNormalizer();

        foreach ($priceInfoUpdated->getPriceInfo()->getTariffs() as $tariff) {
            $offerLd->priceInfo[] = [
                'category' => 'tariff',
                'name' => $translatedTariffNameNormalizer->normalize($tariff->getName()),
                'price' => $tariff->getPrice()->getAmount() / 100,
                'priceCurrency' => $tariff->getPrice()->getCurrency()->getName(),
            ];
        }

        foreach ($priceInfoUpdated->getPriceInfo()->getUiTPASTariffs() as $tariff) {
            $offerLd->priceInfo[] = [
                'category' => 'uitpas',
                'name' => $translatedTariffNameNormalizer->normalize($tariff->getName()),
                'price' => $tariff->getPrice()->getAmount() / 100,
                'priceCurrency' => $tariff->getPrice()->getCurrency()->getName(),
            ];
        }

        return $document->withBody($offerLd);
    }

    protected function applyContactPointUpdated(AbstractContactPointUpdated $contactPointUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($contactPointUpdated);

        $offerLd = $document->getBody();
        $offerLd->contactPoint = (new ContactPointNormalizer())->normalize($contactPointUpdated->getContactPoint());

        return $document->withBody($offerLd);
    }

    protected function applyDescriptionUpdated(
        AbstractDescriptionUpdated $descriptionUpdated
    ): JsonDocument {
        $document = $this->loadDocumentFromRepository($descriptionUpdated);

        $offerLd = $document->getBody();
        if (empty($offerLd->description)) {
            $offerLd->description = new \stdClass();
        }

        $mainLanguage = isset($offerLd->mainLanguage) ? $offerLd->mainLanguage : 'nl';
        $offerLd->description->{$mainLanguage} = $descriptionUpdated->getDescription()->toString();

        return $document->withBody($offerLd);
    }

    protected function applyDescriptionDeleted(
        AbstractDescriptionDeleted $descriptionDeleted
    ): JsonDocument {
        $document = $this->loadDocumentFromRepository($descriptionDeleted);

        $offerLd = $document->getBody();

        if (!isset($offerLd->description)) {
            return $document;
        }

        $langKey = $descriptionDeleted->getLanguage()->toString();
        unset($offerLd->description->{$langKey});

        // Remove description if description is empty, last language was deleted
        if (count((array)$offerLd->description) === 0) {
            unset($offerLd->description);
        }

        return $document->withBody($offerLd);
    }

    protected function applyTypicalAgeRangeUpdated(
        AbstractTypicalAgeRangeUpdated $typicalAgeRangeUpdated
    ): JsonDocument {
        $document = $this->loadDocumentFromRepository($typicalAgeRangeUpdated);

        $offerLd = $document->getBody();
        $offerLd->typicalAgeRange = $typicalAgeRangeUpdated->getTypicalAgeRange()->toString();

        return $document->withBody($offerLd);
    }

    protected function applyTypicalAgeRangeDeleted(
        AbstractTypicalAgeRangeDeleted $typicalAgeRangeDeleted
    ): JsonDocument {
        $document = $this->loadDocumentFromRepository($typicalAgeRangeDeleted);

        $offerLd = $document->getBody();

        $offerLd->typicalAgeRange = '-';

        return $document->withBody($offerLd);
    }

    protected function applyAvailableFromUpdated(AbstractAvailableFromUpdated $availableFromUpdated): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($availableFromUpdated);

        $offerLd = $document->getBody();

        $offerLd->availableFrom = $availableFromUpdated->getAvailableFrom()->format(DateTimeInterface::ATOM);

        return $document->withBody($offerLd);
    }

    protected function applyPublished(AbstractPublished $published): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($published);

        $offerLd = $document->getBody();

        $offerLd->workflowStatus = WorkflowStatus::READY_FOR_VALIDATION()->toString();

        $publicationDate = $published->getPublicationDate();
        $offerLd->availableFrom = $publicationDate->format(DateTimeInterface::ATOM);

        return $document->withBody($offerLd);
    }

    protected function applyApproved(AbstractApproved $approved): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($approved);
        $offerLd = $document->getBody();
        $offerLd->workflowStatus = WorkflowStatus::APPROVED()->toString();
        return $document->withBody($offerLd);
    }

    protected function applyRejected(AbstractRejected $rejected): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($rejected);
        $offerLd = $document->getBody();
        $offerLd->workflowStatus = WorkflowStatus::REJECTED()->toString();
        return $document->withBody($offerLd);
    }

    protected function applyFlaggedAsDuplicate(
        AbstractFlaggedAsDuplicate $flaggedAsDuplicate
    ): JsonDocument {
        $document = $this->loadDocumentFromRepository($flaggedAsDuplicate);
        $offerLd = $document->getBody();
        $offerLd->workflowStatus = WorkflowStatus::REJECTED()->toString();
        return $document->withBody($offerLd);
    }

    protected function applyFlaggedAsInappropriate(
        AbstractFlaggedAsInappropriate $flaggedAsInappropriate
    ): JsonDocument {
        $document = $this->loadDocumentFromRepository($flaggedAsInappropriate);
        $offerLd = $document->getBody();
        $offerLd->workflowStatus = WorkflowStatus::REJECTED()->toString();
        return $document->withBody($offerLd);
    }

    protected function applyImagesImportedFromUdb2(AbstractImagesImportedFromUDB2 $imagesImportedFromUDB2): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($imagesImportedFromUDB2);
        $offerLd = $document->getBody();
        $this->applyUdb2ImagesEvent($offerLd, $imagesImportedFromUDB2);
        return $document->withBody($offerLd);
    }

    protected function applyImagesUpdatedFromUdb2(AbstractImagesUpdatedFromUDB2 $imagesUpdatedFromUDB2): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($imagesUpdatedFromUDB2);
        $offerLd = $document->getBody();
        $this->applyUdb2ImagesEvent($offerLd, $imagesUpdatedFromUDB2);
        return $document->withBody($offerLd);
    }

    /**
     * This indirect apply method can be called internally to deal with images coming from UDB2.
     * Imports from UDB2 only contain the native Dutch content.
     * @see https://github.com/cultuurnet/udb3-udb2-bridge/blob/db0a7ab2444f55bb3faae3d59b82b39aaeba253b/test/Media/ImageCollectionFactoryTest.php#L79-L103
     * Because of this we have to make sure translated images are left in place.
     */
    private function applyUdb2ImagesEvent(\stdClass $offerLd, AbstractImagesEvent $imagesEvent): void
    {
        $images = $imagesEvent->getImages();
        $currentMediaObjects = isset($offerLd->mediaObject) ? $offerLd->mediaObject : [];
        $dutchMediaObjects = array_map(
            function (Image $image) {
                return $this->mediaObjectSerializer->serialize($image);
            },
            $images->toArray()
        );
        $translatedMediaObjects = array_filter(
            $currentMediaObjects,
            function ($image) {
                return $image->inLanguage !== 'nl';
            }
        );
        $mainImage = $images->getMain();

        unset($offerLd->mediaObject, $offerLd->image);

        if (!empty($dutchMediaObjects) || !empty($translatedMediaObjects)) {
            $offerLd->mediaObject = array_merge($dutchMediaObjects, $translatedMediaObjects);
        }

        if (isset($mainImage)) {
            $offerLd->image = $mainImage->getSourceLocation()->toString();
        }
    }

    protected function applyConcluded(Concluded $concluded): JsonDocument
    {
        return $this->loadDocumentFromRepository($concluded);
    }

    protected function newDocument(string $id): JsonDocument
    {
        $document = new JsonDocument($id);

        $offerLd = $document->getBody();
        $offerLd->{'@id'} = $this->iriGenerator->iri($id);

        return $document->withBody($offerLd);
    }

    protected function loadDocumentFromRepository(AbstractEvent $event): JsonDocument
    {
        return $this->loadDocumentFromRepositoryByItemId($event->getItemId());
    }

    protected function loadDocumentFromRepositoryByItemId(string $itemId): JsonDocument
    {
        try {
            $document = $this->repository->fetch($itemId);
            $nrOfRetries = $this->nrOfRetries;

            while ($this->playheadMismatch($document->getBody()) && $nrOfRetries > 0) {
                usleep($this->timeBetweenRetries);
                $nrOfRetries--;
                $this->logger->warning(
                    'Playhead mismatch for document ' . $itemId . ' retries left ' . $nrOfRetries . '. Expected ' . ($this->playhead - 1) . ' but found ' . $document->getBody()->playhead
                );
                $document = $this->repository->fetch($itemId);

                if ($nrOfRetries === 0 && $this->playheadMismatch($document->getBody())) {
                    $this->logger->error(
                        'Playhead mismatch for document ' . $itemId . '. Expected ' . ($this->playhead - 1) . ' but found ' . $document->getBody()->playhead
                    );
                }
            }
        } catch (DocumentDoesNotExist $e) {
            return $this->newDocument($itemId);
        }

        return $document;
    }

    private function playheadMismatch(\stdClass $body): bool
    {
        if (!isset($this->playhead)) {
            return false;
        }

        if ($this->playhead <= 0) {
            return false;
        }

        if (!isset($body->playhead)) {
            return false;
        }

        if ($body->playhead === $this->playhead - 1) {
            return false;
        }

        return true;
    }

    public function organizerJSONLD(string $organizerId): array
    {
        try {
            $organizerJSONLD = $this->organizerRepository->fetch($organizerId)->getRawBody();
            return (array)Json::decode($organizerJSONLD);
        } catch (JsonException $e) {
            return [];
        } catch (DocumentDoesNotExist $e) {
            // In case the place can not be found at the moment, just add its ID
            return [
                '@id' => $this->organizerIriGenerator->iri($organizerId),
            ];
        }
    }

    private function updateModified(JsonDocument $jsonDocument, DomainMessage $domainMessage): JsonDocument
    {
        $body = $jsonDocument->getBody();

        $recordedDateTime = RecordedOn::fromDomainMessage($domainMessage);
        $body->modified = $recordedDateTime->toString();

        return $jsonDocument->withBody($body);
    }

    private function updateCompleteness(JsonDocument $jsonDocument): JsonDocument
    {
        $body = $jsonDocument->getAssocBody();

        $body['completeness'] = $this->completeness->calculateForDocument($jsonDocument);

        return $jsonDocument->withAssocBody($body);
    }

    private function updatePlayhead(JsonDocument $jsonDocument, DomainMessage $domainMessage): JsonDocument
    {
        $body = $jsonDocument->getBody();

        $body->playhead = $domainMessage->getPlayhead();

        return $jsonDocument->withBody($body);
    }
}
