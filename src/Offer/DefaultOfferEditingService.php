<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

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

    /**
     * @var TypeResolverInterface
     */
    protected $typeResolver;

    /**
     * @var ThemeResolverInterface
     */
    protected $themeResolver;

    public function __construct(
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        DocumentRepository $readRepository,
        OfferCommandFactoryInterface $commandFactory,
        TypeResolverInterface $typeResolver,
        ThemeResolverInterface $themeResolver
    ) {
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
        $this->readRepository = $readRepository;
        $this->commandFactory = $commandFactory;
        $this->typeResolver = $typeResolver;
        $this->themeResolver = $themeResolver;
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

    /**
     * @param string $id
     * @return string
     */
    public function updateType($id, StringLiteral $typeId)
    {
        $this->guardId($id);
        $type = $this->typeResolver->byId($typeId);

        return $this->commandBus->dispatch(
            $this->commandFactory->createUpdateTypeCommand($id, $type)
        );
    }

    /**
     * @param string $id
     * @return string
     */
    public function updateTheme($id, StringLiteral $themeId)
    {
        $this->guardId($id);
        $theme = $this->themeResolver->byId($themeId);

        return $this->commandBus->dispatch(
            $this->commandFactory->createUpdateThemeCommand($id, $theme)
        );
    }

    /**
     * @param string $id
     * @return string
     */
    public function updateFacilities($id, array $facilities)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createUpdateFacilitiesCommand($id, $facilities)
        );
    }

    /**
     * @param string $id
     * @return string
     */
    public function updateTitle($id, Language $language, StringLiteral $title)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createUpdateTitleCommand(
                $id,
                $language,
                $title
            )
        );
    }

    /**
     * @param string $id
     * @return string
     */
    public function updateDescription($id, Language $language, Description $description)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createUpdateDescriptionCommand(
                $id,
                $language,
                $description
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function updateCalendar($id, Calendar $calendar)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createUpdateCalendarCommand(
                $id,
                $calendar
            )
        );
    }

    /**
     * @param string $id
     * @return string
     */
    public function addImage($id, UUID $imageId)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createAddImageCommand($id, $imageId)
        );
    }

    public function updateImage(
        $id,
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

    /**
     * @param string $id
     *  Id of the offer to remove the image from.
     *
     * @param Image $image
     *  The image that should be removed.
     *
     * @return string
     */
    public function removeImage($id, Image $image)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createRemoveImageCommand($id, $image)
        );
    }

    /**
     * @param string $id
     * @return string
     */
    public function selectMainImage($id, Image $image)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createSelectMainImageCommand($id, $image)
        );
    }

    /**
     * @param string $id
     * @return string
     */
    public function updateTypicalAgeRange($id, AgeRange $ageRange)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createUpdateTypicalAgeRangeCommand($id, $ageRange)
        );
    }

    /**
     * @param string $id
     * @return string
     */
    public function deleteTypicalAgeRange($id)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createDeleteTypicalAgeRangeCommand($id)
        );
    }

    /**
     * @param string $id
     * @param string $organizerId
     * @return string
     */
    public function updateOrganizer($id, $organizerId)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createUpdateOrganizerCommand($id, $organizerId)
        );
    }

    /**
     * @param string $id
     * @param string $organizerId
     * @return string
     */
    public function deleteOrganizer($id, $organizerId)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createDeleteOrganizerCommand($id, $organizerId)
        );
    }

    /**
     * @param string $id
     * @return string
     */
    public function updateContactPoint($id, ContactPoint $contactPoint)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createUpdateContactPointCommand($id, $contactPoint)
        );
    }

    /**
     * @param string $id
     * @return string
     */
    public function updateBookingInfo($id, BookingInfo $bookingInfo)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createUpdateBookingInfoCommand($id, $bookingInfo)
        );
    }

    public function updatePriceInfo(string $id, PriceInfo $priceInfo)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            $this->commandFactory->createUpdatePriceInfoCommand($id, $priceInfo)
        );
    }

    /**
     * @param string $id
     * @return string
     */
    public function delete($id)
    {
        return $this->commandBus->dispatch(
            $this->commandFactory->createDeleteOfferCommand($id)
        );
    }

    /**
     * @param string $id
     *
     * @throws EntityNotFoundException|DocumentGoneException
     */
    public function guardId($id)
    {
        $offer = $this->readRepository->get($id);

        if (is_null($offer)) {
            throw new EntityNotFoundException(
                sprintf('Offer with id: %s not found.', $id)
            );
        }
    }
}
