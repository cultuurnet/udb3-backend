<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\Offer;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\Offer\ImmutableOffer;
use CultuurNet\UDB3\Model\Organizer\OrganizerIDParser;
use CultuurNet\UDB3\Model\Organizer\OrganizerReference;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerReferenceDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Audience\AgeRangeDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\CalendarDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\ContactPointDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\MediaObjectReferencesDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\PriceInfoDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoriesDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Label\LabelsDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Text\TranslatedDescriptionDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Text\TranslatedTitleDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedDescription;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Ramsey\Uuid\UuidFactory;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

abstract class OfferDenormalizer implements DenormalizerInterface
{
    private UUIDParser $idParser;

    private DenormalizerInterface $titleDenormalizer;

    private DenormalizerInterface $descriptionDenormalizer;

    private DenormalizerInterface $calendarDenormalizer;

    private DenormalizerInterface $categoriesDenormalizer;

    private DenormalizerInterface $labelsDenormalizer;

    private DenormalizerInterface $organizerReferenceDenormalizer;

    private DenormalizerInterface $ageRangeDenormalizer;

    private DenormalizerInterface $priceInfoDenormalizer;

    private DenormalizerInterface $bookingInfoDenormalizer;

    private DenormalizerInterface $contactPointDenormalizer;

    private DenormalizerInterface $mediaObjectReferencesDenormalizer;

    private DenormalizerInterface $videoDenormalizer;

    public function __construct(
        UUIDParser $idParser,
        DenormalizerInterface $titleDenormalizer = null,
        DenormalizerInterface $descriptionDenormalizer = null,
        DenormalizerInterface $calendarDenormalizer = null,
        DenormalizerInterface $categoriesDenormalizer = null,
        DenormalizerInterface $labelsDenormalizer = null,
        DenormalizerInterface $organizerReferenceDenormalizer = null,
        DenormalizerInterface $ageRangeDenormalizer = null,
        DenormalizerInterface $priceInfoDenormalizer = null,
        DenormalizerInterface $bookingInfoDenormalizer = null,
        DenormalizerInterface $contactPointDenormalizer = null,
        DenormalizerInterface $mediaObjectReferencesDenormalizer = null,
        DenormalizerInterface $videoDenormalizer = null
    ) {
        if (!$titleDenormalizer) {
            $titleDenormalizer = new TranslatedTitleDenormalizer();
        }

        if (!$descriptionDenormalizer) {
            $descriptionDenormalizer = new TranslatedDescriptionDenormalizer();
        }

        if (!$calendarDenormalizer) {
            $calendarDenormalizer = new CalendarDenormalizer();
        }

        if (!$categoriesDenormalizer) {
            $categoriesDenormalizer = new CategoriesDenormalizer();
        }

        if (!$labelsDenormalizer) {
            $labelsDenormalizer = new LabelsDenormalizer();
        }

        if (!$organizerReferenceDenormalizer) {
            $organizerReferenceDenormalizer = new OrganizerReferenceDenormalizer(new OrganizerIDParser());
        }

        if (!$ageRangeDenormalizer) {
            $ageRangeDenormalizer = new AgeRangeDenormalizer();
        }

        if (!$priceInfoDenormalizer) {
            $priceInfoDenormalizer = new PriceInfoDenormalizer();
        }

        if (!$bookingInfoDenormalizer) {
            $bookingInfoDenormalizer = new BookingInfoDenormalizer();
        }

        if (!$contactPointDenormalizer) {
            $contactPointDenormalizer = new ContactPointDenormalizer();
        }

        if (!$mediaObjectReferencesDenormalizer) {
            $mediaObjectReferencesDenormalizer = new MediaObjectReferencesDenormalizer();
        }

        if (!$videoDenormalizer) {
            $videoDenormalizer = new VideoDenormalizer(new UuidFactory());
        }

        $this->idParser = $idParser;
        $this->titleDenormalizer = $titleDenormalizer;
        $this->descriptionDenormalizer = $descriptionDenormalizer;
        $this->calendarDenormalizer = $calendarDenormalizer;
        $this->categoriesDenormalizer = $categoriesDenormalizer;
        $this->labelsDenormalizer = $labelsDenormalizer;
        $this->organizerReferenceDenormalizer = $organizerReferenceDenormalizer;
        $this->ageRangeDenormalizer = $ageRangeDenormalizer;
        $this->priceInfoDenormalizer = $priceInfoDenormalizer;
        $this->bookingInfoDenormalizer = $bookingInfoDenormalizer;
        $this->contactPointDenormalizer = $contactPointDenormalizer;
        $this->mediaObjectReferencesDenormalizer = $mediaObjectReferencesDenormalizer;
        $this->videoDenormalizer = $videoDenormalizer;
    }

    abstract protected function createOffer(
        array $originalData,
        UUID $id,
        Language $mainLanguage,
        TranslatedTitle $title,
        Calendar $calendar,
        Categories $categories
    ): ImmutableOffer;

    protected function denormalizeOffer(array $data): ImmutableOffer
    {
        $idUrl = new Url($data['@id']);
        $id = $this->idParser->fromUrl($idUrl);

        $mainLanguageKey = $data['mainLanguage'];
        $mainLanguage = new Language($mainLanguageKey);

        /* @var TranslatedTitle $title */
        $title = $this->titleDenormalizer->denormalize(
            $data['name'],
            TranslatedTitle::class,
            null,
            ['originalLanguage' => $mainLanguageKey]
        );

        $calendar = $this->calendarDenormalizer->denormalize($data, Calendar::class);
        $categories = $this->categoriesDenormalizer->denormalize($data['terms'], Categories::class);

        $offer = $this->createOffer($data, $id, $mainLanguage, $title, $calendar, $categories);
        $offer = $this->denormalizeDescription($data, $offer);
        $offer = $this->denormalizeLabels($data, $offer);
        $offer = $this->denormalizeOrganizerReference($data, $offer);
        $offer = $this->denormalizeAgeRange($data, $offer);
        $offer = $this->denormalizePriceInfo($data, $offer);
        $offer = $this->denormalizeBookingInfo($data, $offer);
        $offer = $this->denormalizeContactPoint($data, $offer);
        $offer = $this->denormalizeMediaObjectReferences($data, $offer);
        $offer = $this->denormalizeVideos($data, $offer);
        $offer = $this->denormalizeWorkflowStatus($data, $offer);
        $offer = $this->denormalizeAvailableFrom($data, $offer);

        return $offer;
    }

    protected function denormalizeDescription(array $data, ImmutableOffer $offer): ImmutableOffer
    {
        if (isset($data['description'])) {
            /* @var TranslatedDescription $description */
            $description = $this->descriptionDenormalizer->denormalize(
                array_filter($data['description']),
                TranslatedDescription::class,
                null,
                ['originalLanguage' => $data['mainLanguage']]
            );

            $offer = $offer->withDescription($description);
        }

        return $offer;
    }

    protected function denormalizeLabels(array $data, ImmutableOffer $offer): ImmutableOffer
    {
        $labels = $this->labelsDenormalizer->denormalize($data, Labels::class);
        return $offer->withLabels($labels);
    }

    protected function denormalizeOrganizerReference(array $data, ImmutableOffer $offer): ImmutableOffer
    {
        if (isset($data['organizer'])) {
            $organizerReference = $this->organizerReferenceDenormalizer->denormalize(
                $data['organizer'],
                OrganizerReference::class
            );

            $offer = $offer->withOrganizerReference($organizerReference);
        }

        return $offer;
    }

    protected function denormalizeAgeRange(array $data, ImmutableOffer $offer): ImmutableOffer
    {
        if (isset($data['typicalAgeRange'])) {
            $ageRange = $this->ageRangeDenormalizer->denormalize($data['typicalAgeRange'], AgeRange::class);
            $offer = $offer->withAgeRange($ageRange);
        }

        return $offer;
    }

    protected function denormalizePriceInfo(array $data, ImmutableOffer $offer): ImmutableOffer
    {
        if (isset($data['priceInfo'])) {
            $priceInfo = $this->priceInfoDenormalizer->denormalize(
                $data['priceInfo'],
                PriceInfo::class,
                null,
                ['originalLanguage' => $data['mainLanguage']]
            );
            $offer = $offer->withPriceInfo($priceInfo);
        }

        return $offer;
    }

    protected function denormalizeBookingInfo(array $data, ImmutableOffer $offer): ImmutableOffer
    {
        if (isset($data['bookingInfo'])) {
            $bookingInfo = $this->bookingInfoDenormalizer->denormalize(
                $data['bookingInfo'],
                BookingInfo::class,
                null,
                ['originalLanguage' => $data['mainLanguage']]
            );
            $offer = $offer->withBookingInfo($bookingInfo);
        }

        return $offer;
    }

    protected function denormalizeContactPoint(array $data, ImmutableOffer $offer): ImmutableOffer
    {
        if (isset($data['contactPoint'])) {
            $contactPoint = $this->contactPointDenormalizer->denormalize(
                $data['contactPoint'],
                ContactPoint::class,
                null,
                ['originalLanguage' => $data['mainLanguage']]
            );
            $offer = $offer->withContactPoint($contactPoint);
        }

        return $offer;
    }

    protected function denormalizeMediaObjectReferences(array $data, ImmutableOffer $offer): ImmutableOffer
    {
        if (isset($data['mediaObject'])) {
            /* @var MediaObjectReferences $mediaObjectReferences */
            $mediaObjectReferences = $this->mediaObjectReferencesDenormalizer->denormalize(
                $data['mediaObject'],
                MediaObjectReferences::class,
                null,
                ['originalLanguage' => $data['mainLanguage']]
            );
            $offer = $offer->withMediaObjectReferences($mediaObjectReferences);
        }

        return $offer;
    }

    protected function denormalizeVideos(array $data, ImmutableOffer $offer): ImmutableOffer
    {
        if (isset($data['videos'])) {
            $videos = new VideoCollection();

            foreach ($data['videos'] as $videoAsArray) {
                $videos = $videos->with($this->videoDenormalizer->denormalize($videoAsArray, Video::class));
            }
            $offer = $offer->withVideos($videos);
        }

        return $offer;
    }

    protected function denormalizeWorkflowStatus(array $data, ImmutableOffer $offer): ImmutableOffer
    {
        if (isset($data['workflowStatus'])) {
            $workflowStatus = new WorkflowStatus($data['workflowStatus']);
            $offer = $offer->withWorkflowStatus($workflowStatus);
        }

        return $offer;
    }

    protected function denormalizeAvailableFrom(array $data, ImmutableOffer $offer): ImmutableOffer
    {
        if (isset($data['availableFrom'])) {
            $availableFrom = DateTimeFactory::fromISO8601($data['availableFrom']);
            $offer = $offer->withAvailableFrom($availableFrom);
        }

        return $offer;
    }
}
