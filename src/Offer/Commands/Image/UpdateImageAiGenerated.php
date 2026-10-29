<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Image;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class UpdateImageAiGenerated
{
    private string $offerId;

    private Uuid $mediaObjectId;

    private bool $isAiGenerated;

    public function __construct(string $offerId, Uuid $mediaObjectId, bool $isAiGenerated)
    {
        $this->offerId = $offerId;
        $this->mediaObjectId = $mediaObjectId;
        $this->isAiGenerated = $isAiGenerated;
    }

    public function getItemId(): string
    {
        return $this->offerId;
    }

    public function getMediaObjectId(): Uuid
    {
        return $this->mediaObjectId;
    }

    public function isAiGenerated(): bool
    {
        return $this->isAiGenerated;
    }
}
