<?php


namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Event\Commands\AddImage;
use CultuurNet\UDB3\Event\Commands\AddLabel;
use CultuurNet\UDB3\Event\Commands\CreateEvent;
use CultuurNet\UDB3\Event\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Event\Commands\DeleteEvent;
use CultuurNet\UDB3\Event\Commands\ImportImages;
use CultuurNet\UDB3\Event\Commands\ImportLabels;
use CultuurNet\UDB3\Event\Commands\RemoveLabel;
use CultuurNet\UDB3\Event\Commands\Moderation\Approve;
use CultuurNet\UDB3\Event\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Event\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish;
use CultuurNet\UDB3\Event\Commands\Moderation\Reject;
use CultuurNet\UDB3\Event\Commands\RemoveImage;
use CultuurNet\UDB3\Event\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Event\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Event\Commands\SelectMainImage;
use CultuurNet\UDB3\Event\Commands\UpdateFacilities;
use CultuurNet\UDB3\Event\Commands\UpdateTheme;
use CultuurNet\UDB3\Event\Commands\UpdateTitle;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Event\Commands\UpdateCalendar;
use CultuurNet\UDB3\Event\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Commands\UpdateImage;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Event\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Event\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Event\Commands\UpdateType;
use CultuurNet\UDB3\Event\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Offer\OfferCommandHandler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Commandhandler for events
 */
class EventCommandHandler extends OfferCommandHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param CreateEvent $command
     */
    protected function handleCreateEvent(CreateEvent $command)
    {
        $event = Event::create(
            $command->getItemId(),
            $command->getMainLanguage(),
            $command->getTitle(),
            $command->getEventType(),
            $command->getLocation(),
            $command->getCalendar(),
            $command->getTheme(),
            $command->getPublicationDate()
        );

        $this->offerRepository->save($event);
    }

    /**
     * Handle an update the major info command.
     * @param UpdateMajorInfo $updateMajorInfo
     */
    public function handleUpdateMajorInfo(UpdateMajorInfo $updateMajorInfo)
    {
        /** @var Event $event */
        $event = $this->offerRepository->load($updateMajorInfo->getItemId());

        $event->updateMajorInfo(
            $updateMajorInfo->getTitle(),
            $updateMajorInfo->getEventType(),
            $updateMajorInfo->getLocation(),
            $updateMajorInfo->getCalendar(),
            $updateMajorInfo->getTheme()
        );

        $this->offerRepository->save($event);

    }

    /**
     * @param UpdateLocation $updateLocation
     */
    public function handleUpdateLocation(UpdateLocation $updateLocation)
    {
        /** @var Event $event */
        $event = $this->offerRepository->load($updateLocation->getItemId());

        $event->updateLocation($updateLocation->getLocationId());

        $this->offerRepository->save($event);
    }

    /**
     * @param UpdateAudience $updateAudience
     */
    public function handleUpdateAudience(UpdateAudience $updateAudience)
    {
        /** @var Event $event */
        $event = $this->offerRepository->load($updateAudience->getItemId());

        $event->updateAudience($updateAudience->getAudience());

        $this->offerRepository->save($event);
    }

    /**
     * @return string
     */
    protected function getAddLabelClassName()
    {
        return AddLabel::class;
    }

    /**
     * @return string
     */
    protected function getRemoveLabelClassName()
    {
        return RemoveLabel::class;
    }

    /**
     * @return string
     */
    protected function getImportLabelsClassName()
    {
        return ImportLabels::class;
    }

    /**
     * @return string
     */
    protected function getAddImageClassName()
    {
        return AddImage::class;
    }

    /**
     * @return string
     */
    protected function getUpdateImageClassName()
    {
        return UpdateImage::class;
    }

    /**
     * @return string
     */
    protected function getRemoveImageClassName()
    {
        return RemoveImage::class;
    }

    /**
     * @return string
     */
    protected function getSelectMainImageClassName()
    {
        return SelectMainImage::class;
    }

    /**
     * @return string
     */
    protected function getImportImagesClassName()
    {
        return ImportImages::class;
    }

    /**
     * @return string
     */
    protected function getUpdateTitleClassName()
    {
        return UpdateTitle::class;
    }

    /**
     * @return string
     */
    protected function getUpdateDescriptionClassName()
    {
        return UpdateDescription::class;
    }

    /**
     * @inheritdoc
     */
    protected function getUpdateCalendarClassName()
    {
        return UpdateCalendar::class;
    }

    /**
     * @return string
     */
    protected function getUpdateTypicalAgeRangeClassName()
    {
        return UpdateTypicalAgeRange::class;
    }

    /**
     * @return string
     */
    protected function getDeleteTypicalAgeRangeClassName()
    {
        return DeleteTypicalAgeRange::class;
    }

    /**
     * @return string
     */
    protected function getUpdateOrganizerClassName()
    {
        return UpdateOrganizer::class;
    }

    /**
     * @return string
     */
    protected function getDeleteOrganizerClassName()
    {
        return DeleteOrganizer::class;
    }

    /**
     * @return string
     */
    protected function getDeleteCurrentOrganizerClassName()
    {
        return DeleteCurrentOrganizer::class;
    }

    /**
     * @return string
     */
    protected function getUpdateContactPointClassName()
    {
        return UpdateContactPoint::class;
    }

    /**
     * @return string
     */
    protected function getUpdateBookingInfoClassName()
    {
        return UpdateBookingInfo::class;
    }

    /**
     * @return string
     */
    protected function getUpdatePriceInfoClassName()
    {
        return UpdatePriceInfo::class;
    }

    /**
     * @return string
     */
    protected function getDeleteOfferClassName()
    {
        return DeleteEvent::class;
    }

    protected function getPublishClassName()
    {
        return Publish::class;
    }

    protected function getApproveClassName()
    {
        return Approve::class;
    }

    protected function getRejectClassName()
    {
        return Reject::class;
    }

    protected function getFlagAsDuplicateClassName()
    {
        return FlagAsDuplicate::class;
    }

    protected function getFlagAsInappropriateClassName()
    {
        return FlagAsInappropriate::class;
    }

    /**
     * @inheritdoc
     */
    protected function getUpdateTypeClassName()
    {
        return UpdateType::class;
    }

    /**
     * @inheritdoc
     */
    protected function getUpdateThemeClassName()
    {
        return UpdateTheme::class;
    }

    /**
     * @inheritdoc
     */
    protected function getUpdateFacilitiesClassName()
    {
        return UpdateFacilities::class;
    }
}
