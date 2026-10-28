<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class AiGeneratedImageFileName
{
    public static function apply(string $location, Uuid $imageId, ?bool $isAiGenerated): string
    {
        if ($isAiGenerated === null) {
            return $location;
        }

        $pattern = '/(^|\/)(' . preg_quote($imageId->toString(), '/') . ')(\.ai)?\.([a-z0-9]+)$/i';
        $replacement = $isAiGenerated ? '$1$2.ai.$4' : '$1$2.$4';

        return preg_replace($pattern, $replacement, $location);
    }
}
