<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Description as LegacyDescription;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteTypicalAgeRange;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateBookingInfo;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateContactPoint;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateDescription;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTypicalAgeRange;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractAddImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractImportImages;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractRemoveImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractSelectMainImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractUpdateImage;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractApprove;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsDuplicate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsInappropriate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractPublish;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractReject;
use CultuurNet\UDB3\Organizer\Organizer;

abstract class OfferCommandHandler extends Udb3CommandHandler
{
    protected Repository $offerRepository;

    protected Repository $organizerRepository;

    protected MediaManagerInterface $mediaManager;

    public function __construct(
        Repository $offerRepository,
        Repository $organizerRepository,
        MediaManagerInterface $mediaManager
    ) {
        $this->offerRepository = $offerRepository;
        $this->organizerRepository = $organizerRepository;
        $this->mediaManager = $mediaManager;
    }

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
    private function getCommandHandlers(): array
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

    abstract protected function getAddImageClassName(): string;

    abstract protected function getUpdateImageClassName(): string;

    abstract protected function getRemoveImageClassName(): string;

    abstract protected function getSelectMainImageClassName(): string;

    abstract protected function getImportImagesClassName(): string;

    abstract protected function getUpdateDescriptionClassName(): string;

    abstract protected function getUpdateTypicalAgeRangeClassName(): string;

    abstract protected function getDeleteTypicalAgeRangeClassName(): string;

    abstract protected function getUpdateContactPointClassName(): string;

    abstract protected function getUpdateBookingInfoClassName(): string;

    abstract protected function getPublishClassName(): string;

    abstract protected function getApproveClassName(): string;

    abstract protected function getRejectClassName(): string;

    abstract protected function getFlagAsDuplicateClassName(): string;

    abstract protected function getFlagAsInappropriateClassName(): string;

    public function handleAddImage(AbstractAddImage $addImage): void
    {
        $offer = $this->load($addImage->getItemId());

        $image = $this->mediaManager->getImage($addImage->getImageId());
        $offer->addImage($image);

        $this->offerRepository->save($offer);
    }

    public function handleRemoveImage(AbstractRemoveImage $removeImage): void
    {
        $offer = $this->load($removeImage->getItemId());
        $offer->removeImage($removeImage->getImage());
        $this->offerRepository->save($offer);
    }

    public function handleUpdateImage(AbstractUpdateImage $updateImage): void
    {
        $offer = $this->load($updateImage->getItemId());
        $offer->updateImage(
            $updateImage->getMediaObjectId(),
            new Description($updateImage->getDescription()),
            $updateImage->getCopyrightHolder()
        );
        $this->offerRepository->save($offer);
    }

    public function handleSelectMainImage(AbstractSelectMainImage $selectMainImage): void
    {
        $offer = $this->load($selectMainImage->getItemId());
        $offer->selectMainImage($selectMainImage->getImage());
        $this->offerRepository->save($offer);
    }


    public function handleImportImages(AbstractImportImages $importImages): void
    {
        $offer = $this->load($importImages->getItemId());
        $offer->importImages($importImages->getImages());
        $this->offerRepository->save($offer);
    }

    public function handleUpdateDescription(AbstractUpdateDescription $updateDescription): void
    {
        $offer = $this->load($updateDescription->getItemId());

        $offer->updateDescription(
            LegacyDescription::fromUdb3ModelDescription($updateDescription->getDescription()),
            LegacyLanguage::fromUdb3ModelLanguage($updateDescription->getLanguage())
        );

        $this->offerRepository->save($offer);
    }

    public function handleUpdateTypicalAgeRange(AbstractUpdateTypicalAgeRange $updateTypicalAgeRange): void
    {
        $offer = $this->load($updateTypicalAgeRange->getItemId());

        $offer->updateTypicalAgeRange(
            $updateTypicalAgeRange->getTypicalAgeRange()
        );

        $this->offerRepository->save($offer);
    }

    public function handleDeleteTypicalAgeRange(AbstractDeleteTypicalAgeRange $deleteTypicalAgeRange): void
    {
        $offer = $this->load($deleteTypicalAgeRange->getItemId());

        $offer->deleteTypicalAgeRange();

        $this->offerRepository->save($offer);
    }

    public function handleUpdateContactPoint(AbstractUpdateContactPoint $updateContactPoint): void
    {
        $offer = $this->load($updateContactPoint->getItemId());

        $offer->updateContactPoint(
            $updateContactPoint->getContactPoint()
        );

        $this->offerRepository->save($offer);
    }

    public function handleUpdateBookingInfo(AbstractUpdateBookingInfo $updateBookingInfo): void
    {
        $offer = $this->load($updateBookingInfo->getItemId());

        $offer->updateBookingInfo(
            $updateBookingInfo->getBookingInfo()
        );

        $this->offerRepository->save($offer);
    }

    private function handlePublish(AbstractPublish $publish): void
    {
        $offer = $this->load($publish->getItemId());
        $offer->publish($publish->getPublicationDate());
        $this->offerRepository->save($offer);
    }

    private function handleApprove(AbstractApprove $approve): void
    {
        $offer = $this->load($approve->getItemId());
        $offer->approve();
        $this->offerRepository->save($offer);
    }

    private function handleReject(AbstractReject $reject): void
    {
        $offer = $this->load($reject->getItemId());
        $offer->reject($reject->getReason());
        $this->offerRepository->save($offer);
    }

    private function handleFlagAsDuplicate(AbstractFlagAsDuplicate $flagAsDuplicate): void
    {
        $offer = $this->load($flagAsDuplicate->getItemId());
        $offer->flagAsDuplicate();
        $this->offerRepository->save($offer);
    }

    private function handleFlagAsInappropriate(AbstractFlagAsInappropriate $flagAsInappropriate): void
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
