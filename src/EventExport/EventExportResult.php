<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

class EventExportResult
{
    private string $url;

    public function __construct(string $url)
    {
        $this->setUrl($url);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    private function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
