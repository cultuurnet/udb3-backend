<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class RenameImage
{
    private Uuid $imageId;

    private ?bool $isAiGenerated;

    public function __construct(Uuid $imageId, ?bool $isAiGenerated)
    {
        $this->imageId = $imageId;
        $this->isAiGenerated = $isAiGenerated;
    }

    public function getImageId(): Uuid
    {
        return $this->imageId;
    }

    public function isAiGenerated(): ?bool
    {
        return $this->isAiGenerated;
    }
}
