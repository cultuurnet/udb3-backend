<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Event\Location\LocationNotFound;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Offer\DefaultOfferEditingService;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;

class EventEditingService extends DefaultOfferEditingService implements EventEditingServiceInterface
{
    /**
     * @var EventServiceInterface
     */
    protected $eventService;

    /**
     * @var Repository
     */
    protected $writeRepository;

    /**
     * @var PlaceRepository
     */
    private $placeRepository;

    public function __construct(
        EventServiceInterface $eventService,
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        DocumentRepository $readRepository,
        OfferCommandFactoryInterface $commandFactory,
        Repository $writeRepository,
        PlaceRepository $placeRepository
    ) {
        parent::__construct(
            $commandBus,
            $uuidGenerator,
            $readRepository,
            $commandFactory
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
