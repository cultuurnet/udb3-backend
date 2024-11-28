<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\JSONLD;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Completeness\Completeness;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Model\ValueObject\Moderation\AvailableTo;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferLDProjector;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferUpdate;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\AvailableFromUpdated;
use CultuurNet\UDB3\Place\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Place\Events\CalendarUpdated;
use CultuurNet\UDB3\Place\Events\ContactPointUpdated;
use CultuurNet\UDB3\Place\Events\DescriptionDeleted;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated;
use CultuurNet\UDB3\Place\Events\DescriptionUpdated;
use CultuurNet\UDB3\Place\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\ImageAdded;
use CultuurNet\UDB3\Place\Events\ImageRemoved;
use CultuurNet\UDB3\Place\Events\ImageUpdated;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\LabelsImported;
use CultuurNet\UDB3\Place\Events\MainImageSelected;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\Moderation\Approved;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Place\Events\Moderation\Published;
use CultuurNet\UDB3\Place\Events\Moderation\Rejected;
use CultuurNet\UDB3\Place\Events\OrganizerDeleted;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated;
use CultuurNet\UDB3\Place\Events\OwnerChanged;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\Place\Events\TypeUpdated;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Place\Events\VideoAdded;
use CultuurNet\UDB3\Place\Events\VideoDeleted;
use CultuurNet\UDB3\Place\Events\VideoUpdated;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentMetaDataEnricherInterface;
use CultuurNet\UDB3\Theme;
use DateTimeInterface;

/**
 * Projects state changes on Place entities to a JSON-LD read model in a
 * document repository.
 */
class PlaceLDProjector extends OfferLDProjector implements EventListener
{
    protected CdbXMLImporter $cdbXMLImporter;

    /**
     * @param string[] $basePriceTranslations
     */
    public function __construct(
        DocumentRepository $repository,
        IriGeneratorInterface $iriGenerator,
        IriGeneratorInterface $organizerIriGenerator,
        DocumentRepository $organizerRepository,
        MediaObjectSerializer $mediaObjectSerializer,
        CdbXMLImporter $cdbXMLImporter,
        JsonDocumentMetaDataEnricherInterface $jsonDocumentMetaDataEnricher,
        array $basePriceTranslations,
        VideoNormalizer $videoNormalizer,
        Completeness $completeness
    ) {
        parent::__construct(
            $repository,
            $iriGenerator,
            $organizerIriGenerator,
            $organizerRepository,
            $mediaObjectSerializer,
            $jsonDocumentMetaDataEnricher,
            $basePriceTranslations,
            $videoNormalizer,
            $completeness
        );

        $this->cdbXMLImporter = $cdbXMLImporter;
    }

    protected function applyPlaceImportedFromUDB2(
        PlaceImportedFromUDB2 $placeImportedFromUDB2
    ): JsonDocument {
        return $this->projectActorImportedFromUDB2(
            $placeImportedFromUDB2
        );
    }

    protected function applyPlaceUpdatedFromUDB2(
        PlaceUpdatedFromUDB2 $placeUpdatedFromUDB2
    ): JsonDocument {
        return $this->projectActorImportedFromUDB2(
            $placeUpdatedFromUDB2
        );
    }

    protected function projectActorImportedFromUDB2(
        ActorImportedFromUDB2 $actorImportedFromUDB2
    ): JsonDocument {
        $actorId = $actorImportedFromUDB2->getActorId();

        $udb2Actor = ActorItemFactory::createActorFromCdbXml(
            $actorImportedFromUDB2->getCdbXmlNamespaceUri(),
            $actorImportedFromUDB2->getCdbXml()
        );

        $document = $this->loadPlaceDocumentFromRepositoryById($actorId);

        $actorLd = $document->getBody();

        $actorLd = $this->cdbXMLImporter->documentWithCdbXML(
            $actorLd,
            $udb2Actor
        );

        // When importing from UDB2 the main language is always nl.
        // When updating from UDB2 never change the main language.
        if (!isset($actorLd->mainLanguage)) {
            $this->setMainLanguage($actorLd, new Language('nl'));
        }

        // Remove geocoordinates, because the address might have been
        // updated and we might get inconsistent data if it takes a while
        // before the new geocoordinates are added.
        // In case geocoding fails, it's also easier to look for places that
        // have no geocoordinates instead of places that have incorrect
        // geocoordinates.
        unset($actorLd->geo);

        return $document->withBody($actorLd);
    }

    protected function newDocument(string $id): JsonDocument
    {
        $document = new JsonDocument($id);

        $placeLd = $document->getBody();
        $placeLd->{'@id'} = $this->iriGenerator->iri($id);
        $placeLd->{'@context'} = '/contexts/place';

        return $document->withBody($placeLd);
    }

    protected function applyPlaceCreated(PlaceCreated $placeCreated, DomainMessage $domainMessage): JsonDocument
    {
        $document = $this->newDocument($placeCreated->getPlaceId());

        $jsonLD = $document->getBody();

        $jsonLD->{'@id'} = $this->iriGenerator->iri(
            $placeCreated->getPlaceId()
        );

        $this->setMainLanguage(
            $jsonLD,
            new Language($placeCreated->getMainLanguage()->getCode())
        );

        $jsonLD->name[$placeCreated->getMainLanguage()->getCode()] = $placeCreated->getTitle();

        $this->setAddress(
            $jsonLD,
            $placeCreated->getAddress(),
            $this->getMainLanguage($jsonLD)
        );

        /** @var Calendar $calendar */
        $calendar = $placeCreated->getCalendar();
        $calendarJsonLD = $calendar->toJsonLd();
        $jsonLD = (object) array_merge((array) $jsonLD, $calendarJsonLD);

        $jsonLD->availableTo = AvailableTo::createFromLegacyCalendar($placeCreated->getCalendar())->format(DateTimeInterface::ATOM);

        $eventType = $placeCreated->getEventType();
        $jsonLD->terms = [
            $eventType->toJsonLd(),
        ];

        $recordedOn = $domainMessage->getRecordedOn()->toString();
        $jsonLD->created = DateTimeFactory::fromFormat(
            DateTime::FORMAT_STRING,
            $recordedOn
        )->format('c');

        $jsonLD->modified = $jsonLD->created;

        $metaData = $domainMessage->getMetadata()->serialize();
        if (isset($metaData['user_id'])) {
            $jsonLD->creator = $metaData['user_id'];
        }

        $jsonLD->workflowStatus = WorkflowStatus::DRAFT()->toString();

        return $document->withBody($jsonLD);
    }

    protected function applyPlaceDeleted(PlaceDeleted $placeDeleted): JsonDocument
    {
        $document = $this->loadDocumentFromRepository($placeDeleted);

        $jsonLD = $document->getBody();

        $jsonLD->workflowStatus = WorkflowStatus::DELETED()->toString();

        return $document->withBody($jsonLD);
    }

    protected function applyMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated): JsonDocument
    {
        $document = $this
            ->loadPlaceDocumentFromRepositoryById($majorInfoUpdated->getPlaceId())
            ->apply(OfferUpdate::calendar($majorInfoUpdated->getCalendar()));

        $jsonLD = $document->getBody();

        $jsonLD->name->{$this->getMainLanguage($jsonLD)->getCode()} = $majorInfoUpdated->getTitle();

        $this->setAddress(
            $jsonLD,
            $majorInfoUpdated->getAddress(),
            $this->getMainLanguage($jsonLD)
        );

        $jsonLD->availableTo = AvailableTo::createFromLegacyCalendar($majorInfoUpdated->getCalendar())->format(DateTimeInterface::ATOM);

        // Remove old theme and event type.
        $jsonLD->terms = array_filter($jsonLD->terms, function ($term) {
            return $term->domain !== EventType::DOMAIN &&  $term->domain !== Theme::DOMAIN;
        });

        $eventType = $majorInfoUpdated->getEventType();
        $jsonLD->terms = [
            $eventType->toJsonLd(),
        ];

        // Remove geocoordinates, because the address might have been
        // updated and we might get inconsistent data if it takes a while
        // before the new geocoordinates are added.
        // In case geocoding fails, it's also easier to look for places that
        // have no geocoordinates instead of places that have incorrect
        // geocoordinates.
        unset($jsonLD->geo);

        return $document->withBody($jsonLD);
    }

    protected function applyAddressUpdated(AddressUpdated $addressUpdated): JsonDocument
    {
        $document = $this->loadPlaceDocumentFromRepositoryById($addressUpdated->getPlaceId());
        $jsonLD = $document->getBody();
        $this->setAddress(
            $jsonLD,
            $addressUpdated->getAddress(),
            $this->getMainLanguage($jsonLD)
        );
        return $document->withBody($jsonLD);
    }

    protected function applyAddressTranslated(AddressTranslated $addressTranslated): JsonDocument
    {
        $document = $this->loadPlaceDocumentFromRepositoryById($addressTranslated->getPlaceId());
        $jsonLD = $document->getBody();
        $this->setAddress(
            $jsonLD,
            $addressTranslated->getAddress(),
            new Language($addressTranslated->getLanguage()->getCode())
        );
        return $document->withBody($jsonLD);
    }

    protected function setAddress(\stdClass $jsonLd, Address $address, Language $language): void
    {
        if (!isset($jsonLd->address)) {
            $jsonLd->address = new \stdClass();
        }

        if (isset($jsonLd->address->streetAddress)) {
            // Old projections have their address in a single language.
            // Set the old address as the address for the main language before
            // updating it or adding another address.
            // @replay_i18n
            // @see https://jira.uitdatabank.be/browse/III-2201
            $mainLanguageCode = $this->getMainLanguage($jsonLd)->getCode();
            $jsonLd->address = (object) [
                $mainLanguageCode => $jsonLd->address,
            ];
        }

        $jsonLd->address->{$language->toString()} = $address->toJsonLd();
    }

    protected function applyGeoCoordinatesUpdated(GeoCoordinatesUpdated $geoCoordinatesUpdated): JsonDocument
    {
        $document = $this->loadPlaceDocumentFromRepositoryById($geoCoordinatesUpdated->getItemId());

        $placeLd = $document->getBody();

        $placeLd->geo = (object) [
            'latitude' => $geoCoordinatesUpdated->getCoordinates()->getLatitude()->toFloat(),
            'longitude' => $geoCoordinatesUpdated->getCoordinates()->getLongitude()->toFloat(),
        ];

        return $document->withBody($placeLd);
    }

    protected function applyOwnerChanged(OwnerChanged $ownerChanged): JsonDocument
    {
        return $this->loadDocumentFromRepositoryByItemId($ownerChanged->getOfferId())
            ->applyAssoc(
                function (array $jsonLd) use ($ownerChanged) {
                    $jsonLd['creator'] = $ownerChanged->getNewOwnerId();
                    return $jsonLd;
                }
            );
    }

    protected function loadPlaceDocumentFromRepositoryById(string $itemId): JsonDocument
    {
        try {
            $document = $this->repository->fetch($itemId);
        } catch (DocumentDoesNotExist $e) {
            return $this->newDocument($itemId);
        }

        return $document;
    }

    protected function getLabelAddedClassName(): string
    {
        return LabelAdded::class;
    }

    protected function getLabelRemovedClassName(): string
    {
        return LabelRemoved::class;
    }

    protected function getLabelsImportedClassName(): string
    {
        return LabelsImported::class;
    }

    protected function getImageAddedClassName(): string
    {
        return ImageAdded::class;
    }

    protected function getImageRemovedClassName(): string
    {
        return ImageRemoved::class;
    }

    protected function getImageUpdatedClassName(): string
    {
        return ImageUpdated::class;
    }

    protected function getMainImageSelectedClassName(): string
    {
        return MainImageSelected::class;
    }

    protected function getVideoAddedClassName(): string
    {
        return VideoAdded::class;
    }

    protected function getVideoDeletedClassName(): string
    {
        return VideoDeleted::class;
    }

    protected function getVideoUpdatedClassName(): string
    {
        return VideoUpdated::class;
    }

    protected function getTitleTranslatedClassName(): string
    {
        return TitleTranslated::class;
    }

    protected function getDescriptionTranslatedClassName(): string
    {
        return DescriptionTranslated::class;
    }

    protected function getOrganizerUpdatedClassName(): string
    {
        return OrganizerUpdated::class;
    }

    protected function getOrganizerDeletedClassName(): string
    {
        return OrganizerDeleted::class;
    }

    protected function getBookingInfoUpdatedClassName(): string
    {
        return BookingInfoUpdated::class;
    }

    protected function getPriceInfoUpdatedClassName(): string
    {
        return PriceInfoUpdated::class;
    }

    protected function getContactPointUpdatedClassName(): string
    {
        return ContactPointUpdated::class;
    }

    protected function getDescriptionUpdatedClassName(): string
    {
        return DescriptionUpdated::class;
    }

    protected function getDescriptionDeletedClassName(): string
    {
        return DescriptionDeleted::class;
    }

    protected function getCalendarUpdatedClassName(): string
    {
        return CalendarUpdated::class;
    }

    protected function getTypicalAgeRangeUpdatedClassName(): string
    {
        return TypicalAgeRangeUpdated::class;
    }

    protected function getTypicalAgeRangeDeletedClassName(): string
    {
        return TypicalAgeRangeDeleted::class;
    }

    protected function getAvailableFromUpdatedClassName(): string
    {
        return AvailableFromUpdated::class;
    }

    protected function getPublishedClassName(): string
    {
        return Published::class;
    }

    protected function getApprovedClassName(): string
    {
        return Approved::class;
    }

    protected function getRejectedClassName(): string
    {
        return Rejected::class;
    }

    protected function getFlaggedAsDuplicateClassName(): string
    {
        return FlaggedAsDuplicate::class;
    }

    protected function getFlaggedAsInappropriateClassName(): string
    {
        return FlaggedAsInappropriate::class;
    }

    protected function getImagesImportedFromUdb2ClassName(): string
    {
        return ImagesImportedFromUDB2::class;
    }

    protected function getImagesUpdatedFromUdb2ClassName(): string
    {
        return ImagesUpdatedFromUDB2::class;
    }

    protected function getTitleUpdatedClassName(): string
    {
        return TitleUpdated::class;
    }

    protected function getTypeUpdatedClassName(): string
    {
        return TypeUpdated::class;
    }

    protected function getFacilitiesUpdatedClassName(): string
    {
        return FacilitiesUpdated::class;
    }
}
