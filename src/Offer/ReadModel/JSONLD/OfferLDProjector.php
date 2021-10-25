<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Category;
use CultuurNet\UDB3\CulturefeedSlugger;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\OrganizerServiceInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Offer\AvailableTo;
use CultuurNet\UDB3\Offer\Events\AbstractBookingInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractCalendarUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractContactPointUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Offer\Events\AbstractFacilitiesUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractPriceInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTitleTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractTitleUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTypeUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractVideoEvent;
use CultuurNet\UDB3\Offer\Events\AbstractVideoDeleted;
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
use CultuurNet\UDB3\Offer\WorkflowStatus;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentMetaDataEnricherInterface;
use CultuurNet\UDB3\ReadModel\MultilingualJsonLDProjectorTrait;
use CultuurNet\UDB3\RecordedOn;
use CultuurNet\UDB3\SluggerInterface;
use DateTimeInterface;
use ValueObjects\Identity\UUID;

abstract class OfferLDProjector implements OrganizerServiceInterface
{
    use MultilingualJsonLDProjectorTrait;
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleUnknownEvents;
    }

    protected DocumentRepository $repository;

    protected IriGeneratorInterface $iriGenerator;

    protected EntityServiceInterface $organizerService;

    protected JsonDocumentMetaDataEnricherInterface $jsonDocumentMetaDataEnricher;

    protected MediaObjectSerializer $mediaObjectSerializer;

    /**
     * Associative array of bases prices.
     * Key is the language, value is the translated string.
     *
     * @var string[]
     */
    private array $basePriceTranslations;

    protected SluggerInterface $slugger;

    protected VideoNormalizer $videoNormalizer;

    /**
     * @param string[] $basePriceTranslations
     */
    public function __construct(
        DocumentRepository $repository,
        IriGeneratorInterface $iriGenerator,
        EntityServiceInterface $organizerService,
        MediaObjectSerializer $mediaObjectSerializer,
        JsonDocumentMetaDataEnricherInterface $jsonDocumentMetaDataEnricher,
        array $basePriceTranslations,
        VideoNormalizer $videoNormalizer
    ) {
        $this->repository = $repository;
        $this->iriGenerator = $iriGenerator;
        $this->organizerService = $organizerService;
        $this->jsonDocumentMetaDataEnricher = $jsonDocumentMetaDataEnricher;
        $this->mediaObjectSerializer = $mediaObjectSerializer;
        $this->basePriceTranslations = $basePriceTranslations;
        $this->videoNormalizer = $videoNormalizer;

        $this->slugger = new CulturefeedSlugger();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(DomainMessage $domainMessage)
    {
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

            $this->repository->save($jsonDocument);
        }
    }

    /**
     * @return string[]
     *   An associative array of commands and their handler methods.
     */
    private function getEventHandlers()
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

    /**
     * @return string
     */
    abstract protected function getLabelAddedClassName();

    /**
     * @return string
     */
    abstract protected function getLabelRemovedClassName();

    /**
     * @return string
     */
    abstract protected function getImageAddedClassName();

    /**
     * @return string
     */
    abstract protected function getImageRemovedClassName();

    /**
     * @return string
     */
    abstract protected function getImageUpdatedClassName();

    /**
     * @return string
     */
    abstract protected function getMainImageSelectedClassName();

    abstract protected function getVideoAddedClassName(): string;

    abstract protected function getVideoDeletedClassName(): string;

    abstract protected function getVideoUpdatedClassName(): string;

    /**
     * @return string
     */
    abstract protected function getTitleTranslatedClassName();

    /**
     * @return string
     */
    abstract protected function getTitleUpdatedClassName();

    /**
     * @return string
     */
    abstract protected function getDescriptionTranslatedClassName();

    /**
     * @return string
     */
    abstract protected function getOrganizerUpdatedClassName();

    /**
     * @return string
     */
    abstract protected function getOrganizerDeletedClassName();

    /**
     * @return string
     */
    abstract protected function getBookingInfoUpdatedClassName();

    /**
     * @return string
     */
    abstract protected function getPriceInfoUpdatedClassName();

    /**
     * @return string
     */
    abstract protected function getContactPointUpdatedClassName();

    /**
     * @return string
     */
    abstract protected function getDescriptionUpdatedClassName();

    /**
     * @return string
     */
    abstract protected function getCalendarUpdatedClassName();

    /**
     * @return string
     */
    abstract protected function getTypicalAgeRangeUpdatedClassName();

    /**
     * @return string
     */
    abstract protected function getTypicalAgeRangeDeletedClassName();

    /**
     * @return string
     */
    abstract protected function getPublishedClassName();

    /**
     * @return string
     */
    abstract protected function getApprovedClassName();

    /**
     * @return string
     */
    abstract protected function getRejectedClassName();

    /**
     * @return string
     */
    abstract protected function getFlaggedAsDuplicateClassName();

    /**
     * @return string
     */
    abstract protected function getFlaggedAsInappropriateClassName();

    /**
     * @return string
     */
    abstract protected function getImagesImportedFromUdb2ClassName();

    /**
     * @return string
     */
    abstract protected function getImagesUpdatedFromUdb2ClassName();

    /**
     * @return string
     */
    abstract protected function getTypeUpdatedClassName();

    /**
     * @return string
     */
    abstract protected function getFacilitiesUpdatedClassName();

    /**
     * @return JsonDocument
     */
    protected function applyTypeUpdated(AbstractTypeUpdated $typeUpdated)
    {
        $document = $this->loadDocumentFromRepository($typeUpdated);

        return $this->updateTerm($document, $typeUpdated->getType());
    }

    /**
     *
     * @return JsonDocument
     */
    protected function updateTerm(JsonDocument $document, Category $category)
    {
        $offerLD = $document->getBody();

        $oldTerms = property_exists($offerLD, 'terms') ? $offerLD->terms : [];
        $newTerm = (object) $category->serialize();

        $newTerms = array_filter(
            $oldTerms,
            function ($term) use ($category) {
                return !property_exists($term, 'domain') || $term->domain !== $category->getDomain();
            }
        );

        array_push($newTerms, $newTerm);

        $offerLD->terms = array_values($newTerms);

        return $document->withBody($offerLD);
    }

    /**
     * @return JsonDocument
     */
    protected function applyFacilitiesUpdated(AbstractFacilitiesUpdated $facilitiesUpdated)
    {
        $document = $this->loadDocumentFromRepository($facilitiesUpdated);

        $offerLd = $document->getBody();

        $terms = isset($offerLd->terms) ? $offerLd->terms : [];

        // Remove all old facilities + get numeric keys.
        $terms = array_values(array_filter(
            $terms,
            function ($term) {
                return $term->domain !== Facility::DOMAIN;
            }
        ));

        // Add the new facilities.
        foreach ($facilitiesUpdated->getFacilities() as $facility) {
            $terms[] = $facility->toJsonLd();
        }

        $offerLd->terms = $terms;

        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyLabelAdded(AbstractLabelAdded $labelAdded)
    {
        $document = $this->loadDocumentFromRepository($labelAdded);

        $offerLd = $document->getBody();

        // Check the visibility of the label to update the right property.
        $labelsProperty = $labelAdded->getLabel()->isVisible() ? 'labels' : 'hiddenLabels';

        $labels = isset($offerLd->{$labelsProperty}) ? $offerLd->{$labelsProperty} : [];
        $label = (string) $labelAdded->getLabel();

        $labels[] = $label;
        $offerLd->{$labelsProperty} = array_unique($labels);

        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyLabelRemoved(AbstractLabelRemoved $labelRemoved)
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
                        return !$labelRemoved->getLabel()->equals(
                            new Label($label)
                        );
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

    /**
     * Apply the imageAdded event to the item repository.
     *
     * @return JsonDocument
     */
    protected function applyImageAdded(AbstractImageAdded $imageAdded)
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

    /**
     * Apply the ImageUpdated event to the item repository.
     *
     * @return JsonDocument
     * @throws \Exception
     */
    protected function applyImageUpdated(AbstractImageUpdated $imageUpdated)
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
                    (string)$imageUpdated->getMediaObjectId()
                ) > 0
            );

            if ($mediaObjectMatches) {
                $mediaObject->description = (string)$imageUpdated->getDescription();
                $mediaObject->copyrightHolder = $imageUpdated->getCopyrightHolder()->toString();

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

        $imageId = (string) $imageRemoved->getImage()->getMediaObjectId();

        /**
         * Matches any object that is not the removed image.
         *
         * @param Object $mediaObject
         *  An existing projection of a media object.
         *
         * @return bool
         *  Returns true when the media object does not match the image to remove.
         */
        $shouldNotBeRemoved = function ($mediaObject) use ($imageId) {
            $containsId = !!strpos($mediaObject->{'@id'}, $imageId);
            return !$containsId;
        };

        // Remove any media objects that match the image.
        $filteredMediaObjects = array_filter(
            $offerLd->mediaObject,
            $shouldNotBeRemoved
        );

        // Unset the main image if it matches the removed image
        if (isset($offerLd->image) && strpos($offerLd->{'image'}, $imageId)) {
            unset($offerLd->{'image'});
        }

        if (!isset($offerLd->image) && count($filteredMediaObjects) > 0) {
            $offerLd->image = array_values($filteredMediaObjects)[0]->contentUrl;
        }

        // If no media objects are left remove the attribute.
        if (empty($filteredMediaObjects)) {
            unset($offerLd->{'mediaObject'});
        } else {
            $offerLd->mediaObject = array_values($filteredMediaObjects);
        }

        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyMainImageSelected(AbstractMainImageSelected $mainImageSelected)
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

    /**
     * @param Object $mediaObject
     *
     * @return bool
     */
    protected function mediaObjectMatchesId($mediaObject, UUID $mediaObjectId)
    {
        return strpos($mediaObject->{'@id'}, (string) $mediaObjectId) > 0;
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

    /**
     * @return JsonDocument
     */
    protected function applyTitleTranslated(AbstractTitleTranslated $titleTranslated)
    {
        $document = $this->loadDocumentFromRepository($titleTranslated);

        $offerLd = $document->getBody();
        $offerLd->name->{$titleTranslated->getLanguage()->getCode(
        )} = $titleTranslated->getTitle()->toNative();

        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyTitleUpdated(AbstractTitleUpdated $titleUpdated)
    {
        $document = $this->loadDocumentFromRepository($titleUpdated);
        $offerLd = $document->getBody();
        $mainLanguage = isset($offerLd->mainLanguage) ? $offerLd->mainLanguage : 'nl';

        $offerLd->name->{$mainLanguage} = $titleUpdated->getTitle()->toNative();

        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyDescriptionTranslated(
        AbstractDescriptionTranslated $descriptionTranslated
    ) {
        $document = $this->loadDocumentFromRepository($descriptionTranslated);

        $offerLd = $document->getBody();
        $languageCode = $descriptionTranslated->getLanguage()->getCode();
        $description = $descriptionTranslated->getDescription()->toNative();
        if (empty($offerLd->description)) {
            $offerLd->description = new \stdClass();
        }
        $offerLd->description->{$languageCode} = $description;

        return $document->withBody($offerLd);
    }

    /**
     *
     * @return JsonDocument
     */
    protected function applyCalendarUpdated(AbstractCalendarUpdated $calendarUpdated)
    {
        $document = $this->loadDocumentFromRepository($calendarUpdated)
            ->apply(OfferUpdate::calendar($calendarUpdated->getCalendar()));

        $offerLd = $document->getBody();

        $availableTo = AvailableTo::createFromCalendar($calendarUpdated->getCalendar());
        $offerLd->availableTo = (string)$availableTo;

        return $document->withBody($offerLd);
    }

    /**
     * Apply the organizer updated event to the offer repository.
     * @return JsonDocument
     */
    protected function applyOrganizerUpdated(AbstractOrganizerUpdated $organizerUpdated)
    {
        $document = $this->loadDocumentFromRepository($organizerUpdated);

        $offerLd = $document->getBody();

        $offerLd->organizer = [
                '@type' => 'Organizer',
            ] + $this->organizerJSONLD($organizerUpdated->getOrganizerId());

        return $document->withBody($offerLd);
    }

    /**
     * Apply the organizer delete event to the offer repository.
     * @return JsonDocument
     */
    protected function applyOrganizerDeleted(AbstractOrganizerDeleted $organizerDeleted)
    {
        $document = $this->loadDocumentFromRepository($organizerDeleted);

        $offerLd = $document->getBody();

        unset($offerLd->organizer);

        return $document->withBody($offerLd);
    }

    /**
     * Apply the booking info updated event to the offer repository.
     * @return JsonDocument
     */
    protected function applyBookingInfoUpdated(AbstractBookingInfoUpdated $bookingInfoUpdated)
    {
        $document = $this->loadDocumentFromRepository($bookingInfoUpdated);

        $offerLd = $document->getBody();

        $offerLd->bookingInfo = $bookingInfoUpdated->getBookingInfo()->toJsonLd();

        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyPriceInfoUpdated(AbstractPriceInfoUpdated $priceInfoUpdated)
    {
        $document = $this->loadDocumentFromRepository($priceInfoUpdated);

        $offerLd = $document->getBody();
        $offerLd->priceInfo = [];

        $basePrice = $priceInfoUpdated->getPriceInfo()->getBasePrice();

        $offerLd->priceInfo[] = [
            'category' => 'base',
            'name' => $this->basePriceTranslations,
            'price' => $basePrice->getPrice()->toFloat(),
            'priceCurrency' => $basePrice->getCurrency()->getCode()->toNative(),
        ];

        foreach ($priceInfoUpdated->getPriceInfo()->getTariffs() as $tariff) {
            $offerLd->priceInfo[] = [
                'category' => 'tariff',
                'name' => $tariff->getName()->serialize(),
                'price' => $tariff->getPrice()->toFloat(),
                'priceCurrency' => $tariff->getCurrency()->getCode()->toNative(),
            ];
        }

        return $document->withBody($offerLd);
    }

    /**
     * Apply the contact point updated event to the offer repository.
     * @return JsonDocument
     */
    protected function applyContactPointUpdated(AbstractContactPointUpdated $contactPointUpdated)
    {
        $document = $this->loadDocumentFromRepository($contactPointUpdated);

        $offerLd = $document->getBody();
        $offerLd->contactPoint = $contactPointUpdated->getContactPoint()->toJsonLd();

        return $document->withBody($offerLd);
    }

    /**
     * Apply the description updated event to the offer repository.
     * @return JsonDocument
     */
    protected function applyDescriptionUpdated(
        AbstractDescriptionUpdated $descriptionUpdated
    ) {
        $document = $this->loadDocumentFromRepository($descriptionUpdated);

        $offerLd = $document->getBody();
        if (empty($offerLd->description)) {
            $offerLd->description = new \stdClass();
        }

        $mainLanguage = isset($offerLd->mainLanguage) ? $offerLd->mainLanguage : 'nl';
        $offerLd->description->{$mainLanguage} = $descriptionUpdated->getDescription()->toNative();

        return $document->withBody($offerLd);
    }

    /**
     * Apply the typical age range updated event to the offer repository.
     * @return JsonDocument
     */
    protected function applyTypicalAgeRangeUpdated(
        AbstractTypicalAgeRangeUpdated $typicalAgeRangeUpdated
    ) {
        $document = $this->loadDocumentFromRepository($typicalAgeRangeUpdated);

        $offerLd = $document->getBody();
        $offerLd->typicalAgeRange = (string) $typicalAgeRangeUpdated->getTypicalAgeRange();

        return $document->withBody($offerLd);
    }

    /**
     * Apply the typical age range deleted event to the offer repository.
     * @return JsonDocument
     */
    protected function applyTypicalAgeRangeDeleted(
        AbstractTypicalAgeRangeDeleted $typicalAgeRangeDeleted
    ) {
        $document = $this->loadDocumentFromRepository($typicalAgeRangeDeleted);

        $offerLd = $document->getBody();

        unset($offerLd->typicalAgeRange);

        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyPublished(AbstractPublished $published)
    {
        $document = $this->loadDocumentFromRepository($published);

        $offerLd = $document->getBody();

        $offerLd->workflowStatus = WorkflowStatus::READY_FOR_VALIDATION()->getName();

        $publicationDate = $published->getPublicationDate();
        $offerLd->availableFrom = $publicationDate->format(DateTimeInterface::ATOM);

        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyApproved(AbstractApproved $approved)
    {
        $document = $this->loadDocumentFromRepository($approved);
        $offerLd = $document->getBody();
        $offerLd->workflowStatus = WorkflowStatus::APPROVED()->getName();
        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyRejected(AbstractRejected $rejected)
    {
        $document = $this->loadDocumentFromRepository($rejected);
        $offerLd = $document->getBody();
        $offerLd->workflowStatus = WorkflowStatus::REJECTED()->getName();
        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyFlaggedAsDuplicate(
        AbstractFlaggedAsDuplicate $flaggedAsDuplicate
    ) {
        $document = $this->loadDocumentFromRepository($flaggedAsDuplicate);
        $offerLd = $document->getBody();
        $offerLd->workflowStatus = WorkflowStatus::REJECTED()->getName();
        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyFlaggedAsInappropriate(
        AbstractFlaggedAsInappropriate $flaggedAsInappropriate
    ) {
        $document = $this->loadDocumentFromRepository($flaggedAsInappropriate);
        $offerLd = $document->getBody();
        $offerLd->workflowStatus = WorkflowStatus::REJECTED()->getName();
        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyImagesImportedFromUdb2(AbstractImagesImportedFromUDB2 $imagesImportedFromUDB2)
    {
        $document = $this->loadDocumentFromRepository($imagesImportedFromUDB2);
        $offerLd = $document->getBody();
        $this->applyUdb2ImagesEvent($offerLd, $imagesImportedFromUDB2);
        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function applyImagesUpdatedFromUdb2(AbstractImagesUpdatedFromUDB2 $imagesUpdatedFromUDB2)
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
     *
     */
    private function applyUdb2ImagesEvent(\stdClass $offerLd, AbstractImagesEvent $imagesEvent)
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
            $offerLd->image = (string) $mainImage->getSourceLocation();
        }
    }

    /**
     * @param string $id
     * @return JsonDocument
     */
    protected function newDocument($id)
    {
        $document = new JsonDocument($id);

        $offerLd = $document->getBody();
        $offerLd->{'@id'} = $this->iriGenerator->iri($id);

        return $document->withBody($offerLd);
    }

    /**
     * @return JsonDocument
     */
    protected function loadDocumentFromRepository(AbstractEvent $event)
    {
        return $this->loadDocumentFromRepositoryByItemId($event->getItemId());
    }

    /**
     * @param string $itemId
     * @return JsonDocument
     */
    protected function loadDocumentFromRepositoryByItemId($itemId)
    {
        try {
            $document = $this->repository->fetch($itemId);
        } catch (DocumentDoesNotExist $e) {
            return $this->newDocument($itemId);
        }

        return $document;
    }

    public function organizerJSONLD(string $organizerId): array
    {
        try {
            $organizerJSONLD = $this->organizerService->getEntity(
                $organizerId
            );

            return (array) json_decode($organizerJSONLD);
        } catch (EntityNotFoundException $e) {
            // In case the place can not be found at the moment, just add its ID
            return [
                '@id' => $this->organizerService->iri($organizerId),
            ];
        }
    }

    /**
     * @return JsonDocument
     */
    private function updateModified(JsonDocument $jsonDocument, DomainMessage $domainMessage)
    {
        $body = $jsonDocument->getBody();

        $recordedDateTime = RecordedOn::fromDomainMessage($domainMessage);
        $body->modified = $recordedDateTime->toString();

        return $jsonDocument->withBody($body);
    }
}
