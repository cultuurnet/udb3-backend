<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
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
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\SelectMainImage;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Place\Commands\UpdateDescription;
use CultuurNet\UDB3\Place\Commands\UpdateImage;
use CultuurNet\UDB3\Place\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Place\Commands\UpdateTypicalAgeRange;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class CommandHandler extends OfferCommandHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected function getAddImageClassName(): string
    {
        return AddImage::class;
    }

    protected function getUpdateImageClassName(): string
    {
        return UpdateImage::class;
    }

    protected function getRemoveImageClassName(): string
    {
        return RemoveImage::class;
    }

    protected function getSelectMainImageClassName(): string
    {
        return SelectMainImage::class;
    }

    protected function getImportImagesClassName(): string
    {
        return ImportImages::class;
    }

    protected function getUpdateDescriptionClassName(): string
    {
        return UpdateDescription::class;
    }

    protected function getUpdateTypicalAgeRangeClassName(): string
    {
        return UpdateTypicalAgeRange::class;
    }

    protected function getDeleteTypicalAgeRangeClassName(): string
    {
        return DeleteTypicalAgeRange::class;
    }

    protected function getDeleteCurrentOrganizerClassName(): string
    {
        return DeleteCurrentOrganizer::class;
    }

    protected function getUpdateContactPointClassName(): string
    {
        return UpdateContactPoint::class;
    }

    protected function getUpdateBookingInfoClassName(): string
    {
        return UpdateBookingInfo::class;
    }

    protected function getUpdatePriceInfoClassName(): string
    {
        return UpdatePriceInfo::class;
    }

    protected function getPublishClassName(): string
    {
        return Publish::class;
    }

    protected function getApproveClassName(): string
    {
        return Approve::class;
    }

    protected function getRejectClassName(): string
    {
        return Reject::class;
    }

    protected function getFlagAsDuplicateClassName(): string
    {
        return FlagAsDuplicate::class;
    }

    protected function getFlagAsInappropriateClassName(): string
    {
        return FlagAsInappropriate::class;
    }

    protected function handleUpdateAddress(UpdateAddress $updateAddress): void
    {
        /** @var Place $place */
        $place = $this->offerRepository->load($updateAddress->getItemId());
        $place->updateAddress($updateAddress->getAddress(), $updateAddress->getLanguage());
        $this->offerRepository->save($place);
    }

    /**
     * Handle an update the major info command.
     */
    public function handleUpdateMajorInfo(UpdateMajorInfo $updateMajorInfo): void
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
