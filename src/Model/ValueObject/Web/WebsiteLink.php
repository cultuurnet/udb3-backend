<?php

namespace CultuurNet\UDB3\Model\ValueObject\Web;

class WebsiteLink
{
    /**
     * @var Url
     */
    private $url;

    /**
     * @var TranslatedWebsiteLabel
     */
    private $label;


    public function __construct(Url $url, TranslatedWebsiteLabel $label)
    {
        $this->url = $url;
        $this->label = $label;
    }

    /**
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return WebsiteLink
     */
    public function withUrl(Url $url)
    {
        $c = clone $this;
        $c->url = $url;
        return $c;
    }

    /**
     * @return TranslatedWebsiteLabel
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return WebsiteLink
     */
    public function withLabel(TranslatedWebsiteLabel $label)
    {
        $c = clone $this;
        $c->label = $label;
        return $c;
    }
}
