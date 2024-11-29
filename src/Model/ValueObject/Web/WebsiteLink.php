<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

class WebsiteLink
{
    private Url $url;

    private TranslatedWebsiteLabel $label;

    public function __construct(Url $url, TranslatedWebsiteLabel $label)
    {
        $this->url = $url;
        $this->label = $label;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function withUrl(Url $url): WebsiteLink
    {
        $c = clone $this;
        $c->url = $url;
        return $c;
    }

    public function getLabel(): TranslatedWebsiteLabel
    {
        return $this->label;
    }

    public function withLabel(TranslatedWebsiteLabel $label): WebsiteLink
    {
        $c = clone $this;
        $c->label = $label;
        return $c;
    }
}
