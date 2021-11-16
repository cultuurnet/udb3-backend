<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

final class MediaUrlRepository
{
    private array $mediaUrlMappings;

    public function __construct(array $mediaUrlMappings)
    {
        $this->mediaUrlMappings = $mediaUrlMappings;
    }

    public function getUpdatedUrl(string $oldUrl): string
    {
        foreach ($this->mediaUrlMappings as $udbVariant => $mediaUrlMapping) {
            if ($mediaUrlMapping['enabled'] && strpos($oldUrl, $mediaUrlMapping['legacy_url']) === 0) {
                return str_replace($mediaUrlMapping['legacy_url'], $mediaUrlMapping['url'], $oldUrl);
            }
        }
        return $oldUrl;
    }
}
