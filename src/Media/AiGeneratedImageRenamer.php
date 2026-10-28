<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Media\Commands\RenameImage;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageAdded;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageUpdated;
use CultuurNet\UDB3\Organizer\Events\ImageAdded as OrganizerImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageUpdated as OrganizerImageUpdated;

final class AiGeneratedImageRenamer implements EventListener
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        if ($event instanceof AbstractImageAdded) {
            $this->renameImage($event->getImage()->getMediaObjectId(), $event->getImage()->isAiGenerated());
        }

        if ($event instanceof AbstractImageUpdated) {
            $this->renameImage(new Uuid($event->getMediaObjectId()), $event->isAiGenerated());
        }

        if ($event instanceof OrganizerImageAdded || $event instanceof OrganizerImageUpdated) {
            $this->renameImage(new Uuid($event->getImageId()), $event->isAiGenerated());
        }
    }

    private function renameImage(Uuid $imageId, ?bool $isAiGenerated): void
    {
        if ($isAiGenerated === null) {
            return;
        }

        $this->commandBus->dispatch(new RenameImage($imageId, $isAiGenerated));
    }
}
