<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

final class MediaUrlRepository
{
    private array $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getUpdatedUrl(string $oldUrl): string
    {
        foreach ($this->mapping as $udbVariant => $mediaUrlMapping) {
            if ($mediaUrlMapping['enabled']) {
                return str_replace($mediaUrlMapping['legacy_url'], $mediaUrlMapping['url'], $oldUrl);
            }
        }
        return $oldUrl;
    }
}
