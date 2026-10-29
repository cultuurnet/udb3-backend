<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class UpdateMediaObjectAiGenerated
{
    private Uuid $mediaObjectId;

    private bool $isAiGenerated;

    public function __construct(Uuid $mediaObjectId, bool $isAiGenerated)
    {
        $this->mediaObjectId = $mediaObjectId;
        $this->isAiGenerated = $isAiGenerated;
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
