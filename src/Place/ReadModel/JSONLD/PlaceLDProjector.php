<?php

namespace CultuurNet\UDB3\Place\ReadModel\JSONLD;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\AvailableTo;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferLDProjector;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferUpdate;
use CultuurNet\UDB3\Offer\WorkflowStatus;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Place\Events\CalendarUpdated;
use CultuurNet\UDB3\Place\Events\ContactPointUpdated;
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
use CultuurNet\UDB3\Place\Events\MainImageSelected;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\MarkedAsCanonical;
use CultuurNet\UDB3\Place\Events\MarkedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\Approved;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Place\Events\Moderation\Published;
use CultuurNet\UDB3\Place\Events\Moderation\Rejected;
use CultuurNet\UDB3\Place\Events\OrganizerDeleted;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Place\Events\ThemeUpdated;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\Place\Events\TypeUpdated;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentMetaDataEnricherInterface;
use CultuurNet\UDB3\Theme;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Projects state changes on Place entities to a JSON-LD read model in a
 * document repository.
 */
class PlaceLDProjector extends OfferLDProjector implements EventListenerInterface
{
    /**
     * @var CdbXMLImporter
     */
    protected $cdbXMLImporter;

    /**
     * @param DocumentRepositoryInterface $repository
     * @param IriGeneratorInterface $iriGenerator
     * @param EntityServiceInterface $organizerService
     * @param SerializerInterface $mediaObjectSerializer
     * @param CdbXMLImporter $cdbXMLImporter
     * @param JsonDocumentMetaDataEnricherInterface $jsonDocumentMetaDataEnricher
     * @param string[] $basePriceTranslations
     */
    public function __construct(
        DocumentRepositoryInterface $repository,
        IriGeneratorInterface $iriGenerator,
        EntityServiceInterface $organizerService,
        SerializerInterface $mediaObjectSerializer,
        CdbXMLImporter $cdbXMLImporter,
        JsonDocumentMetaDataEnricherInterface $jsonDocumentMetaDataEnricher,
        array $basePriceTranslations
    ) {
        parent::__construct(
            $repository,
            $iriGenerator,
            $organizerService,
            $mediaObjectSerializer,
            $jsonDocumentMetaDataEnricher,
            $basePriceTranslations
        );

        $this->cdbXMLImporter = $cdbXMLImporter;
    }

    /**
     * @param PlaceImportedFromUDB2 $placeImportedFromUDB2
     * @return JsonDocument
     */
    protected function applyPlaceImportedFromUDB2(
        PlaceImportedFromUDB2 $placeImportedFromUDB2
    ) {
        return $this->projectActorImportedFromUDB2(
            $placeImportedFromUDB2
        );
    }

    /**
     * @param PlaceUpdatedFromUDB2 $placeUpdatedFromUDB2
     * @return JsonDocument
     */
    protected function applyPlaceUpdatedFromUDB2(
        PlaceUpdatedFromUDB2 $placeUpdatedFromUDB2
    ) {
        return $this->projectActorImportedFromUDB2(
            $placeUpdatedFromUDB2
        );
    }

    /**
     * @param ActorImportedFromUDB2 $actorImportedFromUDB2
     * @return JsonDocument
     * @throws \CultureFeed_Cdb_ParseException
     */
    protected function projectActorImportedFromUDB2(
        ActorImportedFromUDB2 $actorImportedFromUDB2
    ) {
        $actorId = $actorImportedFromUDB2->getActorId();

        $udb2Actor = ActorItemFactory::createActorFromCdbXml(
            $actorImportedFromUDB2->getCdbXmlNamespaceUri(),
            $actorImportedFromUDB2->getCdbXml()
        );

        try {
            $document = $this->loadPlaceDocumentFromRepositoryById($actorId);
        } catch (DocumentGoneException $e) {
            $document = $this->newDocument($actorId);
        }

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

    /**
     * @param string $id
     * @return JsonDocument
     */
    protected function newDocument($id)
    {
        $document = new JsonDocument($id);

        $placeLd = $document->getBody();
        $placeLd->{'@id'} = $this->iriGenerator->iri($id);
        $placeLd->{'@context'} = '/contexts/place';

        return $document->withBody($placeLd);
    }

    /**
     * @param PlaceCreated $placeCreated
     * @param DomainMessage $domainMessage
     * @return JsonDocument
     */
    protected function applyPlaceCreated(PlaceCreated $placeCreated, DomainMessage $domainMessage)
    {
        $document = $this->newDocument($placeCreated->getPlaceId());

        $jsonLD = $document->getBody();

        $jsonLD->{'@id'} = $this->iriGenerator->iri(
            $placeCreated->getPlaceId()
        );

        $this->setMainLanguage($jsonLD, $placeCreated->getMainLanguage());

        $jsonLD->name[$placeCreated->getMainLanguage()->getCode()] = $placeCreated->getTitle();

        $this->setAddress(
            $jsonLD,
            $placeCreated->getAddress(),
            $this->getMainLanguage($jsonLD)
        );

        $calendarJsonLD = $placeCreated->getCalendar()->toJsonLd();
        $jsonLD = (object) array_merge((array) $jsonLD, $calendarJsonLD);

        $availableTo = AvailableTo::createFromCalendar($placeCreated->getCalendar());
        $jsonLD->availableTo = (string)$availableTo;

        $eventType = $placeCreated->getEventType();
        $jsonLD->terms = [
            $eventType->toJsonLd(),
        ];

        $theme = $placeCreated->getTheme();
        if (!empty($theme)) {
            $jsonLD->terms[] = $theme->toJsonLd();
        }

        $recordedOn = $domainMessage->getRecordedOn()->toString();
        $jsonLD->created = \DateTime::createFromFormat(
            DateTime::FORMAT_STRING,
            $recordedOn
        )->format('c');

        $jsonLD->modified = $jsonLD->created;

        $metaData = $domainMessage->getMetadata()->serialize();
        if (isset($metaData['user_id'])) {
            $jsonLD->creator = $metaData['user_id'];
        }

        $jsonLD->workflowStatus = WorkflowStatus::DRAFT()->getName();

        return $document->withBody($jsonLD);
    }

    /**
     * @param PlaceDeleted $placeDeleted
     * @return null
     */
    protected function applyPlaceDeleted(PlaceDeleted $placeDeleted)
    {
        $document = $this->loadDocumentFromRepository($placeDeleted);

        $jsonLD = $document->getBody();

        $jsonLD->workflowStatus = WorkflowStatus::DELETED()->getName();

        return $document->withBody($jsonLD);
    }

    /**
     * Apply the major info updated command to the projector.
     * @param MajorInfoUpdated $majorInfoUpdated
     * @return JsonDocument
     */
    protected function applyMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated)
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

        $availableTo = AvailableTo::createFromCalendar($majorInfoUpdated->getCalendar());
        $jsonLD->availableTo = (string)$availableTo;

        // Remove old theme and event type.
        $jsonLD->terms = array_filter($jsonLD->terms, function ($term) {
            return $term->domain !== EventType::DOMAIN &&  $term->domain !== Theme::DOMAIN;
        });

        $eventType = $majorInfoUpdated->getEventType();
        $jsonLD->terms = [
            $eventType->toJsonLd(),
        ];

        $theme = $majorInfoUpdated->getTheme();
        if (!empty($theme)) {
            $jsonLD->terms[] = $theme->toJsonLd();
        }

        // Remove geocoordinates, because the address might have been
        // updated and we might get inconsistent data if it takes a while
        // before the new geocoordinates are added.
        // In case geocoding fails, it's also easier to look for places that
        // have no geocoordinates instead of places that have incorrect
        // geocoordinates.
        unset($jsonLD->geo);

        return $document->withBody($jsonLD);
    }

    /**
     * @param AddressUpdated $addressUpdated
     * @return JsonDocument
     */
    protected function applyAddressUpdated(AddressUpdated $addressUpdated)
    {
        $document = $this->loadPlaceDocumentFromRepositoryById($addressUpdated->getPlaceId());
        $jsonLD = $document->getBody();
        $this->setAddress($jsonLD, $addressUpdated->getAddress(), $this->getMainLanguage($jsonLD));
        return $document->withBody($jsonLD);
    }

    /**
     * @param AddressTranslated $addressTranslated
     * @return JsonDocument
     */
    protected function applyAddressTranslated(AddressTranslated $addressTranslated)
    {
        $document = $this->loadPlaceDocumentFromRepositoryById($addressTranslated->getPlaceId());
        $jsonLD = $document->getBody();
        $this->setAddress($jsonLD, $addressTranslated->getAddress(), $addressTranslated->getLanguage());
        return $document->withBody($jsonLD);
    }

    /**
     * @param \stdClass $jsonLd
     * @param Address $address
     * @param Language $language
     */
    protected function setAddress(\stdClass $jsonLd, Address $address, Language $language)
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

        $jsonLd->address->{$language->getCode()} = $address->toJsonLd();
    }

    /**
     * @param GeoCoordinatesUpdated $geoCoordinatesUpdated
     * @return JsonDocument
     */
    protected function applyGeoCoordinatesUpdated(GeoCoordinatesUpdated $geoCoordinatesUpdated)
    {
        $document = $this->loadPlaceDocumentFromRepositoryById($geoCoordinatesUpdated->getItemId());

        $placeLd = $document->getBody();

        $placeLd->geo = (object) [
            'latitude' => $geoCoordinatesUpdated->getCoordinates()->getLatitude()->toDouble(),
            'longitude' => $geoCoordinatesUpdated->getCoordinates()->getLongitude()->toDouble(),
        ];

        return $document->withBody($placeLd);
    }

    protected function applyMarkedAsDuplicate(MarkedAsDuplicate $markedAsDuplicate): JsonDocument
    {
        $document = $this->loadPlaceDocumentFromRepositoryById($markedAsDuplicate->getPlaceId());

        return $document->apply(function ($placeLd) use ($markedAsDuplicate) {
            $placeLd->duplicateOf = $this->iriGenerator->iri($markedAsDuplicate->getDuplicateOf());
            return $placeLd;
        });
    }

    protected function applyMarkedAsCanonical(MarkedAsCanonical $markedAsCanonical): JsonDocument
    {
        $document = $this->loadPlaceDocumentFromRepositoryById($markedAsCanonical->getPlaceId());

        return $document->apply(function ($placeLd) use ($markedAsCanonical) {
            $placeLd->duplicatedBy[] = $this->iriGenerator->iri($markedAsCanonical->getDuplicatedBy());
            foreach ($markedAsCanonical->getDuplicatesOfDuplicate() as $duplicateOfDuplicate) {
                $placeLd->duplicatedBy[] = $this->iriGenerator->iri($duplicateOfDuplicate);
            }
            return $placeLd;
        });
    }

    /**
     * @param string $itemId
     * @return JsonDocument
     */
    protected function loadPlaceDocumentFromRepositoryById($itemId)
    {
        $document = $this->repository->get($itemId);

        if (!$document) {
            return $this->newDocument($itemId);
        }

        return $document;
    }

    /**
     * @return string
     */
    protected function getLabelAddedClassName()
    {
        return LabelAdded::class;
    }

    /**
     * @return string
     */
    protected function getLabelRemovedClassName()
    {
        return LabelRemoved::class;
    }

    /**
     * @return string
     */
    protected function getImageAddedClassName()
    {
        return ImageAdded::class;
    }

    /**
     * @return string
     */
    protected function getImageRemovedClassName()
    {
        return ImageRemoved::class;
    }

    /**
     * @return string
     */
    protected function getImageUpdatedClassName()
    {
        return ImageUpdated::class;
    }

    protected function getMainImageSelectedClassName()
    {
        return MainImageSelected::class;
    }

    /**
     * @return string
     */
    protected function getTitleTranslatedClassName()
    {
        return TitleTranslated::class;
    }

    /**
     * @return string
     */
    protected function getDescriptionTranslatedClassName()
    {
        return DescriptionTranslated::class;
    }

    /**
     * @return string
     */
    protected function getOrganizerUpdatedClassName()
    {
        return OrganizerUpdated::class;
    }

    /**
     * @return string
     */
    protected function getOrganizerDeletedClassName()
    {
        return OrganizerDeleted::class;
    }

    protected function getBookingInfoUpdatedClassName()
    {
        return BookingInfoUpdated::class;
    }

    /**
     * @return string
     */
    protected function getPriceInfoUpdatedClassName()
    {
        return PriceInfoUpdated::class;
    }

    protected function getContactPointUpdatedClassName()
    {
        return ContactPointUpdated::class;
    }

    protected function getDescriptionUpdatedClassName()
    {
        return DescriptionUpdated::class;
    }

    protected function getCalendarUpdatedClassName()
    {
        return CalendarUpdated::class;
    }

    protected function getTypicalAgeRangeUpdatedClassName()
    {
        return TypicalAgeRangeUpdated::class;
    }

    protected function getTypicalAgeRangeDeletedClassName()
    {
        return TypicalAgeRangeDeleted::class;
    }

    protected function getPublishedClassName()
    {
        return Published::class;
    }

    protected function getApprovedClassName()
    {
        return Approved::class;
    }

    protected function getRejectedClassName()
    {
        return Rejected::class;
    }

    protected function getFlaggedAsDuplicateClassName()
    {
        return FlaggedAsDuplicate::class;
    }

    protected function getFlaggedAsInappropriateClassName()
    {
        return FlaggedAsInappropriate::class;
    }

    protected function getImagesImportedFromUdb2ClassName()
    {
        return ImagesImportedFromUDB2::class;
    }

    protected function getImagesUpdatedFromUdb2ClassName()
    {
        return ImagesUpdatedFromUDB2::class;
    }

    protected function getTitleUpdatedClassName()
    {
        return TitleUpdated::class;
    }

    protected function getTypeUpdatedClassName()
    {
        return TypeUpdated::class;
    }

    protected function getThemeUpdatedClassName()
    {
        return ThemeUpdated::class;
    }

    protected function getFacilitiesUpdatedClassName()
    {
        return FacilitiesUpdated::class;
    }
}
