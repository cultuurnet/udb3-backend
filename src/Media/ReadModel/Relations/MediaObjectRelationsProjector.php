<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\Image\ImagesImportedFromUDB2 as EventImagesImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\Image\ImagesUpdatedFromUDB2 as EventImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\ImageAdded as EventImageAdded;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageAdded;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageRemoved;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesEvent;
use CultuurNet\UDB3\Organizer\Events\ImageAdded as OrganizerImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageRemoved as OrganizerImageRemoved;
use CultuurNet\UDB3\Place\Events\Image\ImagesImportedFromUDB2 as PlaceImagesImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\Image\ImagesUpdatedFromUDB2 as PlaceImagesUpdatedFromUDB2;

/**
 * Keeps track of which events, places and organizers use a media object.
 * Events of events and places share the same short class names, so they are matched with instanceof.
 */
final class MediaObjectRelationsProjector implements EventListener
{
    private MediaObjectRelationsRepository $repository;

    public function __construct(MediaObjectRelationsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        if ($event instanceof AbstractImageAdded) {
            $this->repository->add(
                $event->getImage()->getMediaObjectId()->toString(),
                $event instanceof EventImageAdded ? ItemType::event() : ItemType::place(),
                $event->getItemId()
            );
        }

        if ($event instanceof AbstractImageRemoved) {
            $this->repository->remove(
                $event->getImage()->getMediaObjectId()->toString(),
                $event->getItemId()
            );
        }

        if ($event instanceof EventImagesImportedFromUDB2 || $event instanceof EventImagesUpdatedFromUDB2) {
            $this->replaceImages($event, ItemType::event());
        }

        if ($event instanceof PlaceImagesImportedFromUDB2 || $event instanceof PlaceImagesUpdatedFromUDB2) {
            $this->replaceImages($event, ItemType::place());
        }

        if ($event instanceof OrganizerImageAdded) {
            $this->repository->add($event->getImageId(), ItemType::organizer(), $event->getOrganizerId());
        }

        if ($event instanceof OrganizerImageRemoved) {
            $this->repository->remove($event->getImageId(), $event->getOrganizerId());
        }

        if ($event instanceof EventCopied) {
            // A copied event has the same images as the original event.
            foreach ($this->repository->getMediaObjectIds($event->getOriginalEventId()) as $mediaObjectId) {
                $this->repository->add($mediaObjectId, ItemType::event(), $event->getItemId());
            }
        }
    }

    private function replaceImages(AbstractImagesEvent $event, ItemType $itemType): void
    {
        $this->repository->removeAllForItem($event->getItemId());

        /** @var Image $image */
        foreach ($event->getImages() as $image) {
            $this->repository->add($image->getMediaObjectId()->toString(), $itemType, $event->getItemId());
        }
    }
}
