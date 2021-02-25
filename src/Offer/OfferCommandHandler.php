<?php

namespace CultuurNet\UDB3\Offer;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteCurrentOrganizer;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteOffer;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteOrganizer;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteTypicalAgeRange;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateBookingInfo;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateContactPoint;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateDescription;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateFacilities;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateOrganizer;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdatePriceInfo;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTheme;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateType;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTypicalAgeRange;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractAddImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractImportImages;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractRemoveImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractSelectMainImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractUpdateImage;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTitle;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractApprove;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsDuplicate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsInappropriate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractPublish;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractReject;
use CultuurNet\UDB3\Organizer\Organizer;

abstract class OfferCommandHandler extends Udb3CommandHandler
{
    /**
     * @var Repository
     */
    protected $offerRepository;

    /**
     * @var Repository
     */
    protected $organizerRepository;

    /**
     * @var MediaManagerInterface|MediaManager
     */
    protected $mediaManager;

    public function __construct(
        Repository $offerRepository,
        Repository $organizerRepository,
        MediaManagerInterface $mediaManager
    ) {
        $this->offerRepository = $offerRepository;
        $this->organizerRepository = $organizerRepository;
        $this->mediaManager = $mediaManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($command): void
    {
        $commandName = get_class($command);
        $commandHandlers = $this->getCommandHandlers();

        if (isset($commandHandlers[$commandName])) {
            $handler = $commandHandlers[$commandName];
            call_user_func([$this, $handler], $command);
        } else {
            parent::handle($command);
        }
    }

    /**
     * @return string[]
     *   An associative array of commands and their handler methods.
     */
    private function getCommandHandlers()
    {
        $commands = [];

        foreach (get_class_methods($this) as $method) {
            $matches = [];
            if (preg_match('/^handle(.+)$/', $method, $matches)) {
                $command = $matches[1];
                $classNameMethod = 'get' . $command . 'ClassName';

                if (method_exists($this, $classNameMethod)) {
                    $commandFullClassName = call_user_func([$this, $classNameMethod]);
                    $commands[$commandFullClassName] = $method;
                }
            }
        }

        return $commands;
    }

    /**
     * @return string
     */
    abstract protected function getUpdateTitleClassName();

    /**
     * @return string
     */
    abstract protected function getAddImageClassName();

    /**
     * @return string
     */
    abstract protected function getUpdateImageClassName();

    /**
     * @return string
     */
    abstract protected function getRemoveImageClassName();

    /**
     * @return string
     */
    abstract protected function getSelectMainImageClassName();

    /**
     * @return string
     */
    abstract protected function getImportImagesClassName();

    /**
     * @return string
     */
    abstract protected function getUpdateDescriptionClassName();

    /**
     * @return string
     */
    abstract protected function getUpdateCalendarClassName();

    /**
     * @return string
     */
    abstract protected function getUpdateTypicalAgeRangeClassName();

    /**
     * @return string
     */
    abstract protected function getDeleteTypicalAgeRangeClassName();

    /**
     * @return string
     */
    abstract protected function getUpdateOrganizerClassName();

    /**
     * @return string
     */
    abstract protected function getDeleteOrganizerClassName();

    /**
     * @return string
     */
    abstract protected function getDeleteCurrentOrganizerClassName();

    /**
     * @return string
     */
    abstract protected function getUpdateContactPointClassName();

    /**
     * @return string
     */
    abstract protected function getUpdateBookingInfoClassName();

    /**
     * @return string
     */
    abstract protected function getUpdatePriceInfoClassName();

    /**
     * @return string
     */
    abstract protected function getDeleteOfferClassName();

    /**
     * @return string
     */
    abstract protected function getPublishClassName();

    /**
     * @return string
     */
    abstract protected function getApproveClassName();

    /**
     * @return string
     */
    abstract protected function getRejectClassName();

    /**
     * @return string
     */
    abstract protected function getFlagAsDuplicateClassName();

    /**
     * @return string
     */
    abstract protected function getFlagAsInappropriateClassName();

    /**
     * @return string
     */
    abstract protected function getUpdateTypeClassName();

    /**
     * @return string
     */
    abstract protected function getUpdateThemeClassName();

    /**
     * @return string
     */
    abstract protected function getUpdateFacilitiesClassName();

    /**
     * @param AbstractUpdateType $updateType
     */
    public function handleUpdateType(AbstractUpdateType $updateType)
    {
        $offer = $this->load($updateType->getItemId());

        $offer->updateType($updateType->getType());

        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractUpdateTheme $updateTheme
     */
    public function handleUpdateTheme(AbstractUpdateTheme $updateTheme)
    {
        $offer = $this->load($updateTheme->getItemId());

        $offer->updateTheme($updateTheme->getTheme());

        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractUpdateFacilities $updateFacilities
     */
    public function handleUpdateFacilities(AbstractUpdateFacilities $updateFacilities)
    {
        $offer = $this->load($updateFacilities->getItemId());

        $offer->updateFacilities($updateFacilities->getFacilities());

        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractUpdateTitle $translateTitle
     */
    private function handleUpdateTitle(AbstractUpdateTitle $translateTitle)
    {
        $offer = $this->load($translateTitle->getItemId());
        $offer->updateTitle($translateTitle->getLanguage(), $translateTitle->getTitle());
        $this->offerRepository->save($offer);
    }

    /**
     * Handle an add image command.
     * @param AbstractAddImage $addImage
     */
    public function handleAddImage(AbstractAddImage $addImage)
    {
        $offer = $this->load($addImage->getItemId());

        $image = $this->mediaManager->getImage($addImage->getImageId());
        $offer->addImage($image);

        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractRemoveImage $removeImage
     */
    public function handleRemoveImage(AbstractRemoveImage $removeImage)
    {
        $offer = $this->load($removeImage->getItemId());
        $offer->removeImage($removeImage->getImage());
        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractUpdateImage $updateImage
     */
    public function handleUpdateImage(AbstractUpdateImage $updateImage)
    {
        $offer = $this->load($updateImage->getItemId());
        $offer->updateImage(
            $updateImage->getMediaObjectId(),
            $updateImage->getDescription(),
            $updateImage->getCopyrightHolder()
        );
        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractSelectMainImage $selectMainImage
     */
    public function handleSelectMainImage(AbstractSelectMainImage $selectMainImage)
    {
        $offer = $this->load($selectMainImage->getItemId());
        $offer->selectMainImage($selectMainImage->getImage());
        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractImportImages $importImages
     */
    public function handleImportImages(AbstractImportImages $importImages)
    {
        $offer = $this->load($importImages->getItemId());
        $offer->importImages($importImages->getImages());
        $this->offerRepository->save($offer);
    }

    /**
     * Handle the update of description on a place.
     * @param AbstractUpdateDescription $updateDescription
     */
    public function handleUpdateDescription(AbstractUpdateDescription $updateDescription)
    {
        $offer = $this->load($updateDescription->getItemId());

        $offer->updateDescription(
            $updateDescription->getDescription(),
            $updateDescription->getLanguage()
        );

        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractUpdateCalendar $updateCalendar
     */
    public function handleUpdateCalendar(AbstractUpdateCalendar $updateCalendar)
    {
        $offer = $this->load($updateCalendar->getItemId());

        $offer->updateCalendar($updateCalendar->getCalendar());

        $this->offerRepository->save($offer);
    }

    /**
     * Handle the update of typical age range on a place.
     * @param AbstractUpdateTypicalAgeRange $updateTypicalAgeRange
     */
    public function handleUpdateTypicalAgeRange(AbstractUpdateTypicalAgeRange $updateTypicalAgeRange)
    {
        $offer = $this->load($updateTypicalAgeRange->getItemId());

        $offer->updateTypicalAgeRange(
            $updateTypicalAgeRange->getTypicalAgeRange()
        );

        $this->offerRepository->save($offer);
    }

    /**
     * Handle the deletion of typical age range on a place.
     * @param AbstractDeleteTypicalAgeRange $deleteTypicalAgeRange
     */
    public function handleDeleteTypicalAgeRange(AbstractDeleteTypicalAgeRange $deleteTypicalAgeRange)
    {
        $offer = $this->load($deleteTypicalAgeRange->getItemId());

        $offer->deleteTypicalAgeRange();

        $this->offerRepository->save($offer);
    }

    /**
     * Handle an update command to update organizer of a place.
     * @param AbstractUpdateOrganizer $updateOrganizer
     */
    public function handleUpdateOrganizer(AbstractUpdateOrganizer $updateOrganizer)
    {
        $offer = $this->load($updateOrganizer->getItemId());
        $this->loadOrganizer($updateOrganizer->getOrganizerId());

        $offer->updateOrganizer(
            $updateOrganizer->getOrganizerId()
        );

        $this->offerRepository->save($offer);
    }

    /**
     * Handle an update command to delete the organizer.
     * @param AbstractDeleteOrganizer $deleteOrganizer
     */
    public function handleDeleteOrganizer(AbstractDeleteOrganizer $deleteOrganizer)
    {
        $offer = $this->load($deleteOrganizer->getItemId());

        $offer->deleteOrganizer(
            $deleteOrganizer->getOrganizerId()
        );

        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractDeleteCurrentOrganizer $deleteCurrentOrganizer
     */
    public function handleDeleteCurrentOrganizer(AbstractDeleteCurrentOrganizer $deleteCurrentOrganizer)
    {
        $offer = $this->load($deleteCurrentOrganizer->getItemId());

        $offer->deleteCurrentOrganizer();

        $this->offerRepository->save($offer);
    }

    /**
     * Handle an update command to updated the contact point.
     * @param AbstractUpdateContactPoint $updateContactPoint
     */
    public function handleUpdateContactPoint(AbstractUpdateContactPoint $updateContactPoint)
    {
        $offer = $this->load($updateContactPoint->getItemId());

        $offer->updateContactPoint(
            $updateContactPoint->getContactPoint()
        );

        $this->offerRepository->save($offer);
    }

    /**
     * Handle an update command to updated the booking info.
     * @param AbstractUpdateBookingInfo $updateBookingInfo
     */
    public function handleUpdateBookingInfo(AbstractUpdateBookingInfo $updateBookingInfo)
    {
        $offer = $this->load($updateBookingInfo->getItemId());

        $offer->updateBookingInfo(
            $updateBookingInfo->getBookingInfo()
        );

        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractUpdatePriceInfo $updatePriceInfo
     */
    private function handleUpdatePriceInfo(AbstractUpdatePriceInfo $updatePriceInfo)
    {
        $offer = $this->load($updatePriceInfo->getItemId());

        $offer->updatePriceInfo(
            $updatePriceInfo->getPriceInfo()
        );

        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractDeleteOffer $deleteOffer
     */
    private function handleDeleteOffer(AbstractDeleteOffer $deleteOffer)
    {
        $offer = $this->load($deleteOffer->getItemId());
        $offer->delete();
        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractPublish $publish
     */
    private function handlePublish(AbstractPublish $publish)
    {
        $offer = $this->load($publish->getItemId());
        $offer->publish($publish->getPublicationDate());
        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractApprove $approve
     */
    private function handleApprove(AbstractApprove $approve)
    {
        $offer = $this->load($approve->getItemId());
        $offer->approve();
        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractReject $reject
     */
    private function handleReject(AbstractReject $reject)
    {
        $offer = $this->load($reject->getItemId());
        $offer->reject($reject->getReason());
        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractFlagAsDuplicate $flagAsDuplicate
     */
    private function handleFlagAsDuplicate(AbstractFlagAsDuplicate $flagAsDuplicate)
    {
        $offer = $this->load($flagAsDuplicate->getItemId());
        $offer->flagAsDuplicate();
        $this->offerRepository->save($offer);
    }

    /**
     * @param AbstractFlagAsInappropriate $flagAsInappropriate
     */
    private function handleFlagAsInappropriate(AbstractFlagAsInappropriate $flagAsInappropriate)
    {
        $offer = $this->load($flagAsInappropriate->getItemId());
        $offer->flagAsInappropriate();
        $this->offerRepository->save($offer);
    }

    private function load(string $id): Offer
    {
        /** @var Offer $offer */
        $offer =  $this->offerRepository->load($id);

        return $offer;
    }

    private function loadOrganizer(string $id): Organizer
    {
        /** @var Organizer $organizer */
        $organizer = $this->organizerRepository->load($id);

        return $organizer;
    }
}
