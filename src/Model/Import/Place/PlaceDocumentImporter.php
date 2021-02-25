<?php

namespace CultuurNet\UDB3\Model\Import\Place;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecificationInterface;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Place\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\ImportImages;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateCalendar;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Place\Commands\UpdateDescription;
use CultuurNet\UDB3\Place\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Place\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Place\Commands\UpdateTheme;
use CultuurNet\UDB3\Place\Commands\UpdateTitle;
use CultuurNet\UDB3\Place\Commands\UpdateType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Place\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Place\Place as PlaceAggregate;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PlaceDocumentImporter implements DocumentImporterInterface
{
    /**
     * @var Repository
     */
    private $aggregateRepository;

    /**
     * @var DenormalizerInterface
     */
    private $placeDenormalizer;

    /**
     * @var ImageCollectionFactory
     */
    private $imageCollectionFactory;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var ConsumerSpecificationInterface
     */
    private $shouldApprove;

    /**
     * @var LockedLabelRepository
     */
    private $lockedLabelRepository;

    public function __construct(
        Repository $aggregateRepository,
        DenormalizerInterface $placeDenormalizer,
        ImageCollectionFactory $imageCollectionFactory,
        CommandBus $commandBus,
        ConsumerSpecificationInterface $shouldApprove,
        LockedLabelRepository $lockedLabelRepository
    ) {
        $this->aggregateRepository = $aggregateRepository;
        $this->placeDenormalizer = $placeDenormalizer;
        $this->imageCollectionFactory = $imageCollectionFactory;
        $this->commandBus = $commandBus;
        $this->lockedLabelRepository = $lockedLabelRepository;
        $this->shouldApprove = $shouldApprove;
    }

    /**
     * @inheritdoc
     */
    public function import(DecodedDocument $decodedDocument, ConsumerInterface $consumer = null)
    {
        $id = $decodedDocument->getId();

        try {
            $exists = !is_null($this->aggregateRepository->load($id));
        } catch (AggregateNotFoundException $e) {
            $exists = false;
        }

        /* @var Place $import */
        $importData = $decodedDocument->getBody();
        $import = $this->placeDenormalizer->denormalize($importData, Place::class);

        $adapter = new Udb3ModelToLegacyPlaceAdapter($import);

        $mainLanguage = $adapter->getMainLanguage();
        $title = $adapter->getTitle();
        $type = $adapter->getType();
        $theme = $adapter->getTheme();
        $address = $adapter->getAddress();
        $calendar = $adapter->getCalendar();
        $publishDate = $adapter->getAvailableFrom(new \DateTimeImmutable());

        $commands = [];
        if (!$exists) {
            $place = PlaceAggregate::createPlace(
                $id,
                $mainLanguage,
                $title,
                $type,
                $address,
                $calendar,
                $theme,
                $publishDate
            );

            // New places created via the import API should always be set to
            // ready_for_validation.
            // The publish date in PLaceCreated does not seem to trigger a
            // wfStatus "ready_for_validation" on the json-ld so we manually
            // publish the place after creating it.
            // Existing places should always keep their original status, so
            // only do this publish command for new places.
            $place->publish($publishDate);

            // Places created by specific API partners should automatically be
            // approved.
            if ($consumer && $this->shouldApprove->satisfiedBy($consumer)) {
                $place->approve();
            }

            $this->aggregateRepository->save($place);
        } else {
            $commands[] = new UpdateTitle(
                $id,
                $mainLanguage,
                $title
            );

            $commands[] = new UpdateType($id, $type);
            $commands[] = new UpdateAddress($id, $address, $mainLanguage);
            $commands[] = new UpdateCalendar($id, $calendar);

            if ($theme) {
                $commands[] = new UpdateTheme($id, $theme);
            }
        }

        $bookingInfo = $adapter->getBookingInfo();
        $commands[] = new UpdateBookingInfo($id, $bookingInfo);

        $contactPoint = $adapter->getContactPoint();
        $commands[] = new UpdateContactPoint($id, $contactPoint);

        $description = $adapter->getDescription();
        if ($description) {
            $commands[] = new UpdateDescription($id, $mainLanguage, $description);
        }

        $organizerId = $adapter->getOrganizerId();
        if ($organizerId) {
            $commands[] = new UpdateOrganizer($id, $organizerId);
        } else {
            $commands[] = new DeleteCurrentOrganizer($id);
        }

        $ageRange = $adapter->getAgeRange();
        if ($ageRange) {
            $commands[] = new UpdateTypicalAgeRange($id, $ageRange);
        } else {
            $commands[] = new DeleteTypicalAgeRange($id);
        }

        $priceInfo = $adapter->getPriceInfo();
        if ($priceInfo) {
            $commands[] = new UpdatePriceInfo($id, $priceInfo);
        }

        foreach ($adapter->getTitleTranslations() as $language => $title) {
            $language = new Language($language);
            $commands[] = new UpdateTitle($id, $language, $title);
        }

        foreach ($adapter->getDescriptionTranslations() as $language => $description) {
            $language = new Language($language);
            $commands[] = new UpdateDescription($id, $language, $description);
        }

        foreach ($adapter->getAddressTranslations() as $language => $address) {
            $language = new Language($language);
            $commands[] = new UpdateAddress($id, $address, $language);
        }

        $lockedLabels = $this->lockedLabelRepository->getLockedLabelsForItem($id);
        $commands[] = (new ImportLabels($id, $import->getLabels()))
            ->withLabelsToKeepIfAlreadyOnOffer($lockedLabels);

        $images = $this->imageCollectionFactory->fromMediaObjectReferences($import->getMediaObjectReferences());
        $commands[] = new ImportImages($id, $images);

        foreach ($commands as $command) {
            $this->commandBus->dispatch($command);
        }
    }
}
