<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

class EventExportResult
{
    private string $url;

    /**
     * @param string $url
     */
    public function __construct($url)
    {
        $this->setUrl($url);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    private function setUrl($url): void
    {
        $this->url = $url;
    }
}
