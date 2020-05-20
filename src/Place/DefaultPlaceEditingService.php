<?php

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Repository\RepositoryInterface;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\CalendarInterface;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Offer\DefaultOfferEditingService;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;

class DefaultPlaceEditingService extends DefaultOfferEditingService implements PlaceEditingServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    protected $writeRepository;

    /**
     * @param CommandBusInterface $commandBus
     * @param UuidGeneratorInterface $uuidGenerator
     * @param DocumentRepositoryInterface $readRepository
     * @param OfferCommandFactoryInterface $commandFactory
     * @param RepositoryInterface $writeRepository
     * @param LabelServiceInterface $labelService
     */
    public function __construct(
        CommandBusInterface $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        DocumentRepositoryInterface $readRepository,
        OfferCommandFactoryInterface $commandFactory,
        RepositoryInterface $writeRepository,
        LabelServiceInterface $labelService
    ) {
        parent::__construct(
            $commandBus,
            $uuidGenerator,
            $readRepository,
            $commandFactory,
            $labelService,
            new PlaceTypeResolver(),
            new PlaceThemeResolver()
        );

        $this->writeRepository = $writeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function createPlace(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        Address $address,
        CalendarInterface $calendar,
        Theme $theme = null
    ) {
        $id = $this->uuidGenerator->generate();

        $place = Place::createPlace(
            $id,
            $mainLanguage,
            $title,
            $eventType,
            $address,
            $calendar,
            $theme,
            $this->publicationDate
        );

        $this->writeRepository->save($place);

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function createApprovedPlace(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        Address $address,
        CalendarInterface $calendar,
        Theme $theme = null
    ) {
        $id = $this->uuidGenerator->generate();

        $place = Place::createPlace(
            $id,
            $mainLanguage,
            $title,
            $eventType,
            $address,
            $calendar,
            $theme
        );

        $publicationDate = $this->publicationDate ? $this->publicationDate : new \DateTimeImmutable();
        $place->publish($publicationDate);
        $place->approve();

        $this->writeRepository->save($place);

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function updateMajorInfo($id, Title $title, EventType $eventType, Address $address, CalendarInterface $calendar, Theme $theme = null)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            new UpdateMajorInfo($id, $title, $eventType, $address, $calendar, $theme)
        );
    }

    /**
     * @inheritdoc
     */
    public function updateAddress($id, Address $address, Language $language)
    {
        $this->guardId($id);

        return $this->commandBus->dispatch(
            new UpdateAddress($id, $address, $language)
        );
    }


    /**
     * {@inheritdoc}
     */
    public function deletePlace($id)
    {
        return $this->delete($id);
    }
}
