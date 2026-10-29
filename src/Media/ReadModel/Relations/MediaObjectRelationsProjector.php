<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\Image\ImagesImportedFromUDB2 as EventImagesImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\Image\ImagesUpdatedFromUDB2 as EventImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\ImageAdded as EventImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved as EventImageRemoved;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Offer\Events\AbstractOfferDeleted;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageAdded;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageRemoved;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesEvent;
use CultuurNet\UDB3\Organizer\Events\ImageAdded as OrganizerImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageRemoved as OrganizerImageRemoved;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Place\Events\Image\ImagesImportedFromUDB2 as PlaceImagesImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\Image\ImagesUpdatedFromUDB2 as PlaceImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\ImageAdded as PlaceImageAdded;
use CultuurNet\UDB3\Place\Events\ImageRemoved as PlaceImageRemoved;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;

/**
 * Keeps track of which events, places and organizers use a media object, so a change to the media object can be
 * passed on to all of them.
 */
final class MediaObjectRelationsProjector implements EventListener
{
    private const ITEM_TYPES = [
        EventImageAdded::class => 'event',
        EventImageRemoved::class => 'event',
        EventImagesImportedFromUDB2::class => 'event',
        EventImagesUpdatedFromUDB2::class => 'event',
        EventDeleted::class => 'event',
        EventCopied::class => 'event',
        PlaceImageAdded::class => 'place',
        PlaceImageRemoved::class => 'place',
        PlaceImagesImportedFromUDB2::class => 'place',
        PlaceImagesUpdatedFromUDB2::class => 'place',
        PlaceDeleted::class => 'place',
        OrganizerImageAdded::class => 'organizer',
        OrganizerImageRemoved::class => 'organizer',
        OrganizerDeleted::class => 'organizer',
    ];

    private MediaObjectRelationsRepository $repository;

    public function __construct(MediaObjectRelationsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $itemType = self::ITEM_TYPES[get_class($event)] ?? null;
        if ($itemType === null) {
            return;
        }
        $itemType = new ItemType($itemType);

        if ($event instanceof AbstractImageAdded) {
            $this->add($event->getImage()->getMediaObjectId(), $itemType, $event->getItemId());
        } elseif ($event instanceof AbstractImageRemoved) {
            $this->repository->remove($event->getImage()->getMediaObjectId(), $event->getItemId());
        } elseif ($event instanceof AbstractImagesEvent) {
            $this->repository->removeItem($event->getItemId());
            foreach ($event->getImages()->toArray() as $image) {
                $this->add($image->getMediaObjectId(), $itemType, $event->getItemId());
            }
        } elseif ($event instanceof EventCopied) {
            // A copy gets the images of the original event without separate ImageAdded events.
            foreach ($this->repository->getByItem($event->getOriginalEventId()) as $relation) {
                $this->add($relation->getMediaObjectId(), $itemType, $event->getItemId());
            }
        } elseif ($event instanceof AbstractOfferDeleted) {
            $this->repository->removeItem($event->getItemId());
        } elseif ($event instanceof OrganizerImageAdded) {
            $this->add(new Uuid($event->getImageId()), $itemType, $event->getOrganizerId());
        } elseif ($event instanceof OrganizerImageRemoved) {
            $this->repository->remove(new Uuid($event->getImageId()), $event->getOrganizerId());
        } elseif ($event instanceof OrganizerDeleted) {
            $this->repository->removeItem($event->getOrganizerId());
        }
    }

    private function add(Uuid $mediaObjectId, ItemType $itemType, string $itemId): void
    {
        $this->repository->add(new MediaObjectRelation($mediaObjectId, $itemType, $itemId));
    }
}
