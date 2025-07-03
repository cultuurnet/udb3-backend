<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\DateTimeFactory;
use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Actor\ActorEvent;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Completeness\Completeness;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\ContactPointNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Moderation\Organizer\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Organizer\Events\AddressRemoved;
use CultuurNet\UDB3\Organizer\Events\AddressTranslated;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\DescriptionDeleted;
use CultuurNet\UDB3\Organizer\Events\DescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\EducationalDescriptionDeleted;
use CultuurNet\UDB3\Organizer\Events\EducationalDescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Organizer\Events\ImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageRemoved;
use CultuurNet\UDB3\Organizer\Events\ImageUpdated;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Organizer\Events\OrganizerEvent;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\MainImageUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OwnerChanged;
use CultuurNet\UDB3\Organizer\Events\TitleTranslated;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\CdbXMLImporter;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentMetaDataEnricherInterface;
use CultuurNet\UDB3\ReadModel\MultilingualJsonLDProjectorTrait;
use CultuurNet\UDB3\RecordedOn;
use stdClass;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class OrganizerLDProjector implements EventListener
{
    use MultilingualJsonLDProjectorTrait;

    /**
     * @uses applyOrganizerImportedFromUDB2
     * @uses applyOrganizerCreated
     * @uses applyOrganizerCreatedWithUniqueWebsite
     * @uses applyOwnerChanged
     * @uses applyWebsiteUpdated
     * @uses applyTitleUpdated
     * @uses applyTitleTranslated
     * @uses applyDescriptionUpdated
     * @uses applyDescriptionDeleted
     * @uses applyAddressUpdated
     * @uses applyAddressRemoved
     * @uses applyAddressTranslated
     * @uses applyContactPointUpdated
     * @uses applyImageAdded
     * @uses applyImageUpdated
     * @uses applyImageRemoved
     * @uses applyMainImageUpdated
     * @uses applyOrganizerUpdatedFRomUDB2
     * @uses applyLabelAdded
     * @uses applyLabelRemoved
     * @uses applyOrganizerDeleted
     */
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleMethodSpecificEvents;
    }

    private DocumentRepository $repository;

    private IriGeneratorInterface $iriGenerator;

    private JsonDocumentMetaDataEnricherInterface $jsonDocumentMetaDataEnricher;

    private CdbXMLImporter $cdbXMLImporter;

    private NormalizerInterface $addressNormalizer;

    private NormalizerInterface $contactPointNormalizer;

    private NormalizerInterface $imageNormalizer;

    private Completeness $completeness;

    public function __construct(
        DocumentRepository $repository,
        IriGeneratorInterface $iriGenerator,
        JsonDocumentMetaDataEnricherInterface $jsonDocumentMetaDataEnricher,
        NormalizerInterface $imageNormalizer,
        CdbXMLImporter $cdbXMLImporter,
        Completeness $completeness
    ) {
        $this->repository = $repository;
        $this->iriGenerator = $iriGenerator;
        $this->jsonDocumentMetaDataEnricher = $jsonDocumentMetaDataEnricher;
        $this->imageNormalizer = $imageNormalizer;
        $this->cdbXMLImporter = $cdbXMLImporter;
        $this->completeness = $completeness;
        $this->addressNormalizer = new AddressNormalizer();
        $this->contactPointNormalizer = new ContactPointNormalizer();
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $handleMethod = $this->getHandleMethodName($event);
        if (!$handleMethod) {
            return;
        }

        $jsonDocument = $this->{$handleMethod}($event, $domainMessage);

        if ($jsonDocument) {
            $jsonDocument = $this->jsonDocumentMetaDataEnricher->enrich($jsonDocument, $domainMessage->getMetadata());

            $jsonDocument = $this->updateModified($jsonDocument, $domainMessage);

            $jsonDocument = $this->updateCompleteness($jsonDocument);

            $this->repository->save($jsonDocument);
        }
    }

    /**
     * @throws \CultureFeed_Cdb_ParseException
     */
    private function applyOrganizerImportedFromUDB2(
        OrganizerImportedFromUDB2 $organizerImportedFromUDB2
    ): JsonDocument {
        $udb2Actor = ActorItemFactory::createActorFromCdbXml(
            $organizerImportedFromUDB2->getCdbXmlNamespaceUri(),
            $organizerImportedFromUDB2->getCdbXml()
        );

        $document = $this->newDocument($organizerImportedFromUDB2->getActorId());
        $jsonLD = $document->getBody();

        $jsonLD = $this->cdbXMLImporter->documentWithCdbXML(
            $jsonLD,
            $udb2Actor
        );

        $jsonLD->workflowStatus = WorkflowStatus::ACTIVE()->toString();

        return $document->withBody($jsonLD);
    }

    private function applyOrganizerCreated(
        OrganizerCreated $organizerCreated,
        DomainMessage $domainMessage
    ): JsonDocument {
        $document = $this->newDocument($organizerCreated->getOrganizerId());

        $jsonLD = $document->getBody();

        $jsonLD->{'@id'} = $this->iriGenerator->iri(
            $organizerCreated->getOrganizerId()
        );

        $jsonLD->name = [
            $this->getMainLanguage($jsonLD)->getCode() => $organizerCreated->getTitle(),
        ];

        if ($organizerCreated->hasAddress()) {
            $address = new Address(
                new Street($organizerCreated->getStreetAddress()),
                new PostalCode($organizerCreated->getPostalCode()),
                new Locality($organizerCreated->getLocality()),
                new CountryCode(
                    $organizerCreated->getCountryCode()
                )
            );
            $jsonLD->address = [
                $this->getMainLanguage($jsonLD)->getCode() => $this->addressNormalizer->normalize($address),
            ];
        }

        $jsonLD->phone = $organizerCreated->getPhones();
        $jsonLD->email = $organizerCreated->getEmails();
        $jsonLD->url = $organizerCreated->getUrls();

        $recordedOn = $domainMessage->getRecordedOn()->toString();
        $jsonLD->created = DateTimeFactory::fromFormat(
            DateTime::FORMAT_STRING,
            $recordedOn
        )->format('c');

        $jsonLD = $this->appendCreator($jsonLD, $domainMessage);

        $jsonLD->workflowStatus = WorkflowStatus::ACTIVE()->toString();

        return $document->withBody($jsonLD);
    }

    private function appendCreator(stdClass $jsonLD, DomainMessage $domainMessage): stdClass
    {
        $newJsonLD = clone $jsonLD;

        $metaData = $domainMessage->getMetadata()->serialize();
        if (isset($metaData['user_id'])) {
            $newJsonLD->creator = $metaData['user_id'];
        }

        return $newJsonLD;
    }

    private function applyOrganizerCreatedWithUniqueWebsite(
        OrganizerCreatedWithUniqueWebsite $organizerCreated,
        DomainMessage $domainMessage
    ): JsonDocument {
        $document = $this->newDocument($organizerCreated->getOrganizerId());

        $jsonLD = $document->getBody();

        $jsonLD->{'@id'} = $this->iriGenerator->iri(
            $organizerCreated->getOrganizerId()
        );

        $this->setMainLanguage(
            $jsonLD,
            new Language($organizerCreated->getMainLanguage())
        );

        $jsonLD->url = $organizerCreated->getWebsite();

        $jsonLD->name = [
            $this->getMainLanguage($jsonLD)->getCode() => $organizerCreated->getTitle(),
        ];

        $recordedOn = $domainMessage->getRecordedOn()->toString();
        $jsonLD->created = DateTimeFactory::fromFormat(
            DateTime::FORMAT_STRING,
            $recordedOn
        )->format('c');

        $jsonLD = $this->appendCreator($jsonLD, $domainMessage);

        $jsonLD->workflowStatus = WorkflowStatus::ACTIVE()->toString();

        return $document->withBody($jsonLD);
    }

    protected function applyOwnerChanged(OwnerChanged $ownerChanged): JsonDocument
    {
        $document = $this->repository->fetch($ownerChanged->getOrganizerId());
        $jsonLD = $document->getBody();

        $jsonLD->creator = $ownerChanged->getNewOwnerId();

        return $document->withBody($jsonLD);
    }

    private function applyMainImageUpdated(MainImageUpdated $organizerUpdated): JsonDocument
    {
        $document = $this->repository->fetch($organizerUpdated->getOrganizerId());

        $jsonLD = $document->getBody();

        $mainImageIndex = 0;
        foreach ($jsonLD->images as $image) {
            if (strpos($image->{'@id'}, $organizerUpdated->getMainImageId()) !== false) {
                break;
            }
            $mainImageIndex++;
        }

        $jsonLD->mainImage = $jsonLD->images[$mainImageIndex]->contentUrl;

        return $document->withBody($jsonLD);
    }

    private function applyWebsiteUpdated(WebsiteUpdated $websiteUpdated): JsonDocument
    {
        $organizerId = $websiteUpdated->getOrganizerId();

        $document = $this->repository->fetch($organizerId);

        $jsonLD = $document->getBody();
        $jsonLD->url = $websiteUpdated->getWebsite();

        return $document->withBody($jsonLD);
    }

    private function applyTitleUpdated(TitleUpdated $titleUpdated): JsonDocument
    {
        return $this->applyTitle(
            $titleUpdated,
            new Title($titleUpdated->getTitle())
        );
    }

    private function applyTitleTranslated(TitleTranslated $titleTranslated): JsonDocument
    {
        return $this->applyTitle(
            $titleTranslated,
            new Title($titleTranslated->getTitle()),
            new Language($titleTranslated->getLanguage())
        );
    }

    private function applyDescriptionUpdated(DescriptionUpdated $descriptionUpdated): JsonDocument
    {
        $document = $this->repository->fetch($descriptionUpdated->getOrganizerId());
        $jsonLD = $document->getBody();

        if (!isset($jsonLD->description)) {
            $jsonLD->description = new stdClass();
        }

        $jsonLD->description->{$descriptionUpdated->getLanguage()} = $descriptionUpdated->getDescription();

        return $document->withBody($jsonLD);
    }

    private function applyDescriptionDeleted(DescriptionDeleted $descriptionDeleted): JsonDocument
    {
        $document = $this->repository->fetch($descriptionDeleted->getOrganizerId());
        $jsonLD = $document->getBody();

        unset($jsonLD->description->{$descriptionDeleted->getLanguage()});

        if (empty((array) $jsonLD->description)) {
            unset($jsonLD->description);
        }

        return $document->withBody($jsonLD);
    }

    private function applyEducationalDescriptionUpdated(EducationalDescriptionUpdated $educationalDescriptionUpdated): JsonDocument
    {
        $document = $this->repository->fetch($educationalDescriptionUpdated->getOrganizerId());
        $jsonLD = $document->getBody();

        if (!isset($jsonLD->educationalDescription)) {
            $jsonLD->educationalDescription = new stdClass();
        }

        $jsonLD->educationalDescription->{$educationalDescriptionUpdated->getLanguage()} = $educationalDescriptionUpdated->getEducationalDescription();

        return $document->withBody($jsonLD);
    }

    private function applyEducationalDescriptionDeleted(EducationalDescriptionDeleted $educationalDescriptionDeleted): JsonDocument
    {
        $document = $this->repository->fetch($educationalDescriptionDeleted->getOrganizerId());
        $jsonLD = $document->getBody();

        unset($jsonLD->educationalDescription->{$educationalDescriptionDeleted->getLanguage()});

        if (empty((array) $jsonLD->educationalDescription)) {
            unset($jsonLD->educationalDescription);
        }

        return $document->withBody($jsonLD);
    }

    private function applyAddressUpdated(AddressUpdated $addressUpdated): JsonDocument
    {
        return $this->applyAddress($addressUpdated);
    }

    private function applyAddressTranslated(AddressTranslated $addressTranslated): JsonDocument
    {
        return $this->applyAddress($addressTranslated, new Language($addressTranslated->getLanguage()));
    }

    public function applyAddressRemoved(AddressRemoved $addressRemoved): JsonDocument
    {
        $organizerId = $addressRemoved->getOrganizerId();
        $document = $this->repository->fetch($organizerId);
        $jsonLD = $document->getBody();

        unset($jsonLD->address);
        unset($jsonLD->geo);
        return $document->withBody($jsonLD);
    }

    private function applyContactPointUpdated(ContactPointUpdated $contactPointUpdated): JsonDocument
    {
        $organizerId = $contactPointUpdated->getOrganizerId();
        $contactPoint = new ContactPoint(
            new TelephoneNumbers(
                ...array_map(
                    fn (string $phone) => new TelephoneNumber($phone),
                    $contactPointUpdated->getPhones()
                )
            ),
            new EmailAddresses(
                ...array_map(
                    fn (string $email) => new EmailAddress($email),
                    $contactPointUpdated->getEmails()
                )
            ),
            new Urls(
                ...array_map(
                    fn (string $url) => new Url($url),
                    $contactPointUpdated->getUrls()
                )
            )
        );

        $document = $this->repository->fetch($organizerId);

        $jsonLD = $document->getBody();
        $jsonLD->contactPoint = $this->contactPointNormalizer->normalize($contactPoint);

        return $document->withBody($jsonLD);
    }

    private function applyImageAdded(ImageAdded $imageAdded): JsonDocument
    {
        $document = $this->repository->fetch($imageAdded->getOrganizerId());

        $jsonLD = $document->getBody();

        $jsonLD->images = $jsonLD->images ?? [];
        $image = $this->imageNormalizer->normalize($imageAdded->getImage());
        $jsonLD->images[] = $image;

        if (!isset($jsonLD->mainImage)) {
            $jsonLD->mainImage = $image['contentUrl'];
        }

        return $document->withBody($jsonLD);
    }

    private function applyImageUpdated(ImageUpdated $imageUpdated): JsonDocument
    {
        $document = $this->repository->fetch($imageUpdated->getOrganizerId());

        $jsonLD = $document->getBody();

        $jsonLD->images = array_values(array_map(
            fn ($image) => strpos($image->{'@id'}, $imageUpdated->getImageId()) !== false ?
                $this->imageNormalizer->normalize($imageUpdated->getImage()) : $image,
            $jsonLD->images
        ));

        return $document->withBody($jsonLD);
    }

    private function applyImageRemoved(ImageRemoved $imageRemoved): JsonDocument
    {
        $document = $this->repository->fetch($imageRemoved->getOrganizerId());

        $jsonLD = $document->getBody();

        $jsonLD->images = array_values(array_filter(
            $jsonLD->images,
            static fn ($image) => !(strpos($image->{'@id'}, $imageRemoved->getImageId()) !== false)
        ));

        $imageContentUrls = array_map(fn ($image) => $image->contentUrl, $jsonLD->images);
        $mainImageRemoved = !in_array($jsonLD->mainImage, $imageContentUrls, true);

        if ($mainImageRemoved && count($jsonLD->images) > 0) {
            $jsonLD->mainImage = $jsonLD->images[0]->contentUrl;
        }

        if (count($jsonLD->images) === 0) {
            unset($jsonLD->images, $jsonLD->mainImage);
        }

        return $document->withBody($jsonLD);
    }

    /**
     * @throws \CultureFeed_Cdb_ParseException
     */
    private function applyOrganizerUpdatedFromUDB2(
        OrganizerUpdatedFromUDB2 $organizerUpdatedFromUDB2
    ): JsonDocument {
        // It's possible that an organizer has been deleted in udb3, but never
        // in udb2. If an update comes for that organizer from udb2, it should
        // be imported again. This is intended by design.
        // @see https://jira.uitdatabank.be/browse/III-1092
        try {
            $document = $this->loadDocumentFromRepository(
                $organizerUpdatedFromUDB2
            );
        } catch (DocumentDoesNotExist $e) {
            $document = $this->newDocument($organizerUpdatedFromUDB2->getActorId());
        }

        $udb2Actor = ActorItemFactory::createActorFromCdbXml(
            $organizerUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $organizerUpdatedFromUDB2->getCdbXml()
        );

        $actorLd = $this->cdbXMLImporter->documentWithCdbXML(
            $document->getBody(),
            $udb2Actor
        );

        return $document->withBody($actorLd);
    }

    private function applyLabelAdded(LabelAdded $labelAdded): JsonDocument
    {
        $document = $this->repository->fetch($labelAdded->getOrganizerId());

        $jsonLD = $document->getBody();

        // Check the visibility of the label to update the right property.
        $labelsProperty = $labelAdded->isLabelVisible() ? 'labels' : 'hiddenLabels';

        $labels = isset($jsonLD->{$labelsProperty}) ? $jsonLD->{$labelsProperty} : [];
        $label = $labelAdded->getLabelName();

        $labels[] = $label;
        $jsonLD->{$labelsProperty} = array_unique($labels);

        return $document->withBody($jsonLD);
    }

    private function applyLabelRemoved(LabelRemoved $labelRemoved): JsonDocument
    {
        $document = $this->repository->fetch($labelRemoved->getOrganizerId());
        $jsonLD = $document->getBody();

        // Don't presume that the label visibility is correct when removing.
        // So iterate over both the visible and invisible labels.
        $labelsProperties = ['labels', 'hiddenLabels'];

        foreach ($labelsProperties as $labelsProperty) {
            if (isset($jsonLD->{$labelsProperty}) && is_array($jsonLD->{$labelsProperty})) {
                $jsonLD->{$labelsProperty} = array_filter(
                    $jsonLD->{$labelsProperty},
                    function ($label) use ($labelRemoved) {
                        return strcasecmp($labelRemoved->getLabelName(), $label) !== 0;
                    }
                );

                // Ensure array keys start with 0 so json_encode() does encode it
                // as an array and not as an object.
                if (count($jsonLD->{$labelsProperty}) > 0) {
                    $jsonLD->{$labelsProperty} = array_values($jsonLD->{$labelsProperty});
                } else {
                    unset($jsonLD->{$labelsProperty});
                }
            }
        }

        return $document->withBody($jsonLD);
    }

    private function applyOrganizerDeleted(OrganizerDeleted $organizerDeleted): JsonDocument
    {
        $document = $this->repository->fetch($organizerDeleted->getOrganizerId());

        $jsonLD = $document->getBody();

        $jsonLD->workflowStatus = WorkflowStatus::DELETED()->toString();

        return $document->withBody($jsonLD);
    }

    private function newDocument(string $id): JsonDocument
    {
        $document = new JsonDocument($id);

        $organizerLd = $document->getBody();
        $organizerLd->{'@id'} = $this->iriGenerator->iri($id);
        $organizerLd->{'@context'} = '/contexts/organizer';
        // For an new organizer document set a default language of nl.
        // This avoids a missing language for imports.
        // When created with UDB3 this main language gets overwritten by the real one.
        $organizerLd->mainLanguage = 'nl';

        return $document->withBody($organizerLd);
    }

    private function applyTitle(
        OrganizerEvent $organizerEvent,
        Title $title,
        Language $language = null
    ): JsonDocument {
        $organizerId = $organizerEvent->getOrganizerId();

        $document = $this->repository->fetch($organizerId);

        $jsonLD = $document->getBody();

        $mainLanguage = $this->getMainLanguage($jsonLD);
        if ($language === null) {
            $language = $mainLanguage;
        }

        // @replay_i18n For old projections the name is untranslated and just a string.
        // This needs to be upgraded to an object with languages and translation.
        // When a full replay is done this code becomes obsolete.
        // @see https://jira.uitdatabank.be/browse/III-2201
        if (isset($jsonLD->name) && is_string($jsonLD->name)) {
            $previousTitle = $jsonLD->name;
            $jsonLD->name = new \stdClass();
            $jsonLD->name->{$mainLanguage->getCode()} = $previousTitle;
        }

        $jsonLD->name->{$language->getCode()} = $title->toString();

        return $document->withBody($jsonLD);
    }

    private function applyAddress(
        AddressUpdated $addressUpdated,
        Language $language = null
    ): JsonDocument {
        $organizerId = $addressUpdated->getOrganizerId();
        $document = $this->repository->fetch($organizerId);
        $jsonLD = $document->getBody();

        $mainLanguage = $this->getMainLanguage($jsonLD);
        if ($language === null) {
            $language = $mainLanguage;
        }

        if (!isset($jsonLD->address)) {
            $jsonLD->address = new \stdClass();
        }

        $jsonLD->address->{$language->getCode()} = $this->addressNormalizer->normalize(
            new Address(
                new Street($addressUpdated->getStreetAddress()),
                new PostalCode($addressUpdated->getPostalCode()),
                new Locality($addressUpdated->getLocality()),
                new CountryCode($addressUpdated->getCountryCode())
            )
        );

        return $document->withBody($jsonLD);
    }

    public function applyGeoCoordinatesUpdated(GeoCoordinatesUpdated $geoCoordinatesUpdated): JsonDocument
    {
        $document = $this->repository->fetch($geoCoordinatesUpdated->getOrganizerId());

        $jsonLD = $document->getBody();

        $jsonLD->geo = [
            'latitude' => $geoCoordinatesUpdated->getLatitude(),
            'longitude' => $geoCoordinatesUpdated->getLongitude(),
        ];

        return $document->withBody($jsonLD);
    }

    private function loadDocumentFromRepository(ActorEvent $actor): JsonDocument
    {
        try {
            $document = $this->repository->fetch($actor->getActorId());
        } catch (DocumentDoesNotExist $e) {
            return $this->newDocument($actor->getActorId());
        }

        return $document;
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

        $completeness = $this->completeness->calculateForDocument($jsonDocument);

        $body['completeness'] = $completeness;

        return $jsonDocument->withAssocBody($body);
    }
}
