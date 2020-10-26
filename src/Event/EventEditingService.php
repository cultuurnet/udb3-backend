<?php

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\RepositoryInterface;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Event\Location\LocationNotFound;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Offer\DefaultOfferEditingService;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;

class EventEditingService extends DefaultOfferEditingService implements EventEditingServiceInterface
{
    /**
     * @var EventServiceInterface
     */
    protected $eventService;

    /**
     * @var RepositoryInterface
     */
    protected $writeRepository;

    /**
     * @var PlaceRepository
     */
    private $placeRepository;

    public function __construct(
        EventServiceInterface $eventService,
        CommandBusInterface $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        DocumentRepositoryInterface $readRepository,
        OfferCommandFactoryInterface $commandFactory,
        RepositoryInterface $writeRepository,
        LabelServiceInterface $labelService,
        PlaceRepository $placeRepository
    ) {
        parent::__construct(
            $commandBus,
            $uuidGenerator,
            $readRepository,
            $commandFactory,
            $labelService,
            new EventTypeResolver(),
            new EventThemeResolver()
        );
        $this->eventService = $eventService;
        $this->writeRepository = $writeRepository;
        $this->placeRepository = $placeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function createEvent(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        $theme = null
    ) {
        $eventId = $this->uuidGenerator->generate();

        try {
            $this->placeRepository->load($location->toNative());
        } catch (AggregateNotFoundException $e) {
            throw LocationNotFound::withLocationId($location);
        }

        $event = Event::create(
            $eventId,
            $mainLanguage,
            $title,
            $eventType,
            $location,
            $calendar,
            $theme,
            $this->publicationDate
        );

        $this->writeRepository->save($event);

        return $eventId;
    }

    /**
     * @inheritdoc
     */
    public function createApprovedEvent(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        Theme $theme = null
    ) {
        $eventId = $this->uuidGenerator->generate();

        try {
            $this->placeRepository->load($location->toNative());
        } catch (AggregateNotFoundException $e) {
            throw LocationNotFound::withLocationId($location);
        }

        $event = Event::create(
            $eventId,
            $mainLanguage,
            $title,
            $eventType,
            $location,
            $calendar,
            $theme
        );

        $publicationDate = $this->publicationDate ? $this->publicationDate : new \DateTimeImmutable();
        $event->publish($publicationDate);
        $event->approve();

        $this->writeRepository->save($event);

        return $eventId;
    }

    /**
     * @inheritdoc
     */
    public function copyEvent($originalEventId, Calendar $calendar)
    {
        if (!is_string($originalEventId)) {
            throw new \InvalidArgumentException(
                'Expected originalEventId to be a string, received ' . gettype($originalEventId)
            );
        }

        try {
            /** @var Event $event */
            $event = $this->writeRepository->load($originalEventId);
        } catch (AggregateNotFoundException $exception) {
            throw new \InvalidArgumentException(
                'No original event found to copy with id ' . $originalEventId
            );
        }

        $eventId = $this->uuidGenerator->generate();

        $newEvent = $event->copy($eventId, $calendar);

        $this->writeRepository->save($newEvent);

        return $eventId;
    }

    /**
     * {@inheritdoc}
     */
    public function updateMajorInfo($eventId, Title $title, EventType $eventType, LocationId $location, Calendar $calendar, $theme = null)
    {
        $this->guardId($eventId);

        return $this->commandBus->dispatch(
            new UpdateMajorInfo($eventId, $title, $eventType, $location, $calendar, $theme)
        );
    }

    /**
     * @inheritdoc
     */
    public function updateLocation($eventId, LocationId $locationId)
    {
        $this->guardId($eventId);

        return $this->commandBus->dispatch(
            new UpdateLocation($eventId, $locationId)
        );
    }

    /**
     * @inheritdoc
     */
    public function updateAudience($eventId, Audience $audience)
    {
        return $this->commandBus->dispatch(
            new UpdateAudience($eventId, $audience)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteEvent($eventId)
    {
        return $this->delete($eventId);
    }
}
