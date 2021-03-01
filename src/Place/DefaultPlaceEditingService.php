<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Offer\DefaultOfferEditingService;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;

class DefaultPlaceEditingService extends DefaultOfferEditingService implements PlaceEditingServiceInterface
{
    /**
     * @var Repository
     */
    protected $writeRepository;

    public function __construct(
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        DocumentRepository $readRepository,
        OfferCommandFactoryInterface $commandFactory,
        Repository $writeRepository
    ) {
        parent::__construct(
            $commandBus,
            $uuidGenerator,
            $readRepository,
            $commandFactory,
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
        Calendar $calendar,
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
        Calendar $calendar,
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
    public function updateMajorInfo($id, Title $title, EventType $eventType, Address $address, Calendar $calendar, Theme $theme = null)
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
