<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Offer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Offer\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Offer\Commands\UpdateTitle;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\StringLiteral;

class DefaultOfferEditingService implements OfferEditingServiceInterface
{
    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var UuidGeneratorInterface
     */
    protected $uuidGenerator;

    /**
     * @var DocumentRepository
     */
    protected $readRepository;

    /**
     * @var OfferCommandFactoryInterface
     */
    protected $commandFactory;

    /**
     * @var \DateTimeImmutable|null
     */
    protected $publicationDate;

    public function __construct(
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        DocumentRepository $readRepository,
        OfferCommandFactoryInterface $commandFactory
    ) {
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
        $this->readRepository = $readRepository;
        $this->commandFactory = $commandFactory;
        $this->publicationDate = null;
    }

    /**
     * @return static
     */
    public function withFixedPublicationDateForNewOffers(
        \DateTimeImmutable $publicationDate
    ) {
        $c = clone $this;
        $c->publicationDate = $publicationDate;
        return $c;
    }

    public function updateTitle(string $id, Language $language, StringLiteral $title): void
    {
        $this->guardId($id);

        $this->commandBus->dispatch(
            new UpdateTitle(
                $id,
                new \CultuurNet\UDB3\Model\ValueObject\Translation\Language($language->getCode()),
                new Title($title->toNative())
            )
        );
    }

    public function updateDescription(string $id, Language $language, Description $description): void
    {
        $this->guardId($id);

        $this->commandBus->dispatch(
            $this->commandFactory->createUpdateDescriptionCommand(
                $id,
                $language,
                $description
            )
        );
    }

    public function addImage(string $id, UUID $imageId): void
    {
        $this->guardId($id);

        $this->commandBus->dispatch(
            $this->commandFactory->createAddImageCommand($id, $imageId)
        );
    }

    public function updateImage(
        string $id,
        Image $image,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder
    ): void {
        $this->guardId($id);

        $this->commandBus->dispatch(
            $this->commandFactory->createUpdateImageCommand(
                $id,
                $image->getMediaObjectId(),
                $description,
                $copyrightHolder
            )
        );
    }

    public function removeImage(string $id, Image $image): void
    {
        $this->guardId($id);

        $this->commandBus->dispatch(
            $this->commandFactory->createRemoveImageCommand($id, $image)
        );
    }

    public function selectMainImage(string $id, Image $image): void
    {
        $this->guardId($id);

        $this->commandBus->dispatch(
            $this->commandFactory->createSelectMainImageCommand($id, $image)
        );
    }

    public function updateTypicalAgeRange(string $id, AgeRange $ageRange): void
    {
        $this->guardId($id);

        $this->commandBus->dispatch(
            $this->commandFactory->createUpdateTypicalAgeRangeCommand($id, $ageRange)
        );
    }

    public function deleteTypicalAgeRange(string $id): void
    {
        $this->guardId($id);

        $this->commandBus->dispatch(
            $this->commandFactory->createDeleteTypicalAgeRangeCommand($id)
        );
    }

    public function updateOrganizer(string $id, $organizerId): void
    {
        $this->guardId($id);
        $this->commandBus->dispatch(new UpdateOrganizer($id, $organizerId));
    }

    public function deleteOrganizer(string $id, $organizerId): void
    {
        $this->guardId($id);
        $this->commandBus->dispatch(new DeleteOrganizer($id, $organizerId));
    }

    public function updateContactPoint(string $id, ContactPoint $contactPoint): void
    {
        $this->guardId($id);

        $this->commandBus->dispatch(
            $this->commandFactory->createUpdateContactPointCommand($id, $contactPoint)
        );
    }

    public function updateBookingInfo(string $id, BookingInfo $bookingInfo): void
    {
        $this->guardId($id);

        $this->commandBus->dispatch(
            $this->commandFactory->createUpdateBookingInfoCommand($id, $bookingInfo)
        );
    }

    public function guardId(string $id): void
    {
        try {
            $this->readRepository->fetch($id);
        } catch (DocumentDoesNotExist $e) {
            throw new EntityNotFoundException(
                sprintf('Offer with id: %s not found.', $id)
            );
        }
    }
}
