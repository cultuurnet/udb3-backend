<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Offer\OfferCommandHandler;
use CultuurNet\UDB3\Place\Commands\AddImage;
use CultuurNet\UDB3\Place\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Place\Commands\ImportImages;
use CultuurNet\UDB3\Place\Commands\Moderation\Approve;
use CultuurNet\UDB3\Place\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Place\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Place\Commands\Moderation\Publish;
use CultuurNet\UDB3\Place\Commands\Moderation\Reject;
use CultuurNet\UDB3\Place\Commands\RemoveImage;
use CultuurNet\UDB3\Place\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\SelectMainImage;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Place\Commands\UpdateDescription;
use CultuurNet\UDB3\Place\Commands\UpdateImage;
use CultuurNet\UDB3\Place\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Place\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Place\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Place\Commands\UpdateTitle;
use CultuurNet\UDB3\Place\Commands\UpdateTypicalAgeRange;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Commandhandler for places.
 */
class CommandHandler extends OfferCommandHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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

    protected function handleUpdateAddress(UpdateAddress $updateAddress)
    {
        /** @var Place $place */
        $place = $this->offerRepository->load($updateAddress->getItemId());
        $place->updateAddress($updateAddress->getAddress(), $updateAddress->getLanguage());
        $this->offerRepository->save($place);
    }

    /**
     * Handle an update the major info command.
     */
    public function handleUpdateMajorInfo(UpdateMajorInfo $updateMajorInfo)
    {

        /** @var Place $place */
        $place = $this->offerRepository->load($updateMajorInfo->getItemId());

        $place->updateMajorInfo(
            $updateMajorInfo->getTitle(),
            $updateMajorInfo->getEventType(),
            $updateMajorInfo->getAddress(),
            $updateMajorInfo->getCalendar()
        );

        $this->offerRepository->save($place);
    }
}
