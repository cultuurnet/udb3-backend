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

    public function updateUrl(string $oldUrl): string
    {
        foreach ($this->mapping as $udb_variant => $settings) {
            if ($settings['enabled']) {
                return str_replace($settings['legacy_url'], $settings['new_url'], $oldUrl);
            }
        }
        return $oldUrl;
    }
}
