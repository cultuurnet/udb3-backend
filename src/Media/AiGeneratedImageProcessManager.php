<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Media\Commands\UpdateMediaObjectAiGenerated;
use CultuurNet\UDB3\Media\Events\MediaObjectAiGeneratedUpdated;
use CultuurNet\UDB3\Media\ReadModel\Relations\MediaObjectRelationsRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Offer\Commands\Image\UpdateImageAiGenerated as UpdateOfferImageAiGenerated;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageUpdated;
use CultuurNet\UDB3\Organizer\Commands\UpdateImageAiGenerated as UpdateOrganizerImageAiGenerated;
use CultuurNet\UDB3\Organizer\Events\ImageUpdated as OrganizerImageUpdated;

/**
 * Keeps isAiGenerated of a media object in sync with all events, places and organizers that use it.
 * A change on one item updates the media object, which in turn updates all other items.
 * This ends because the media object and the items do not record a change when the value is the same.
 */
final class AiGeneratedImageProcessManager implements EventListener
{
    private CommandBus $commandBus;

    private MediaObjectRelationsRepository $relationsRepository;

    public function __construct(CommandBus $commandBus, MediaObjectRelationsRepository $relationsRepository)
    {
        $this->commandBus = $commandBus;
        $this->relationsRepository = $relationsRepository;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        if ($event instanceof AbstractImageUpdated && $event->isAiGenerated() !== null) {
            $this->updateMediaObject($event->getMediaObjectId(), $event->isAiGenerated());
        }

        if ($event instanceof OrganizerImageUpdated && $event->isAiGenerated() !== null) {
            $this->updateMediaObject($event->getImageId(), $event->isAiGenerated());
        }

        if ($event instanceof MediaObjectAiGeneratedUpdated) {
            $this->updateRelatedItems($event);
        }
    }

    private function updateMediaObject(string $mediaObjectId, bool $isAiGenerated): void
    {
        $this->commandBus->dispatch(
            new UpdateMediaObjectAiGenerated(new Uuid($mediaObjectId), $isAiGenerated)
        );
    }

    private function updateRelatedItems(MediaObjectAiGeneratedUpdated $event): void
    {
        $mediaObjectId = $event->getMediaObjectId();

        foreach ($this->relationsRepository->getRelations($mediaObjectId->toString()) as $relation) {
            $command = $relation->getItemType()->sameAs(ItemType::organizer()) ?
                new UpdateOrganizerImageAiGenerated($relation->getItemId(), $mediaObjectId, $event->isAiGenerated()) :
                new UpdateOfferImageAiGenerated($relation->getItemId(), $mediaObjectId, $event->isAiGenerated());

            $this->commandBus->dispatch($command);
        }
    }
}
