<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class UpdateImageAiGenerated
{
    private string $organizerId;

    private Uuid $imageId;

    private bool $isAiGenerated;

    public function __construct(string $organizerId, Uuid $imageId, bool $isAiGenerated)
    {
        $this->organizerId = $organizerId;
        $this->imageId = $imageId;
        $this->isAiGenerated = $isAiGenerated;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getImageId(): Uuid
    {
        return $this->imageId;
    }

    public function isAiGenerated(): bool
    {
        return $this->isAiGenerated;
    }
}
