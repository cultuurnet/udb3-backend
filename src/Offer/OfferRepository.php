<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Assert\AssertionFailedException;
use Broadway\Domain\AggregateRoot;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Place\Place;
use CultuurNet\UDB3\Place\PlaceRepository;

class OfferRepository implements Repository
{
    private EventRepository $eventRepository;

    private PlaceRepository $placeRepository;

    public function __construct(
        EventRepository $eventRepository,
        PlaceRepository $placeRepository
    ) {
        $this->eventRepository = $eventRepository;
        $this->placeRepository = $placeRepository;
    }

    public function save(AggregateRoot $aggregate): void
    {
        try {
            $this->eventRepository->save($aggregate);
        } catch (AssertionFailedException $e) {
            $this->placeRepository->save($aggregate);
        }
    }

    public function load($id): Offer
    {
        try {
            /** @var Event $event */
            $event = $this->eventRepository->load($id);
            return $event;
        } catch (AggregateNotFoundException $e) {
            /** @var Place $place */
            $place = $this->placeRepository->load($id);
            return $place;
        }
    }
}
