<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\Location\LocationNotFound;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Offer\DefaultOfferEditingService;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;

final class EventEditingService extends DefaultOfferEditingService implements EventEditingServiceInterface
{
    private Repository $writeRepository;

    private PlaceRepository $placeRepository;

    public function __construct(
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
        $this->writeRepository = $writeRepository;
        $this->placeRepository = $placeRepository;
    }

    public function createEvent(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        $theme = null
    ): string {
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

    public function createApprovedEvent(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        Theme $theme = null
    ): string {
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

        $publicationDate = $this->publicationDate ?: new \DateTimeImmutable();
        $event->publish($publicationDate);
        $event->approve();

        $this->writeRepository->save($event);

        return $eventId;
    }
}
