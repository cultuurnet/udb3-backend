<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Event\Commands\AddImage;
use CultuurNet\UDB3\Event\Commands\CreateEvent;
use CultuurNet\UDB3\Event\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Event\Commands\ImportImages;
use CultuurNet\UDB3\Event\Commands\Moderation\Approve;
use CultuurNet\UDB3\Event\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Event\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish;
use CultuurNet\UDB3\Event\Commands\Moderation\Reject;
use CultuurNet\UDB3\Event\Commands\RemoveImage;
use CultuurNet\UDB3\Event\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Event\Commands\SelectMainImage;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Event\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Commands\UpdateImage;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Event\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Offer\OfferCommandHandler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class EventCommandHandler extends OfferCommandHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
            $command->getPublicationDate(Chronos::now())
        );

        $this->offerRepository->save($event);
    }

    public function handleUpdateMajorInfo(UpdateMajorInfo $updateMajorInfo): void
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

    public function handleUpdateLocation(UpdateLocation $updateLocation): void
    {
        /** @var Event $event */
        $event = $this->offerRepository->load($updateLocation->getItemId());

        $event->updateLocation($updateLocation->getLocationId());

        $this->offerRepository->save($event);
    }

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
}
