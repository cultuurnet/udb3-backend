<?php

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use ValueObjects\Web\Url;

trait HasUrlTrait
{
    /**
     * @var Url
     */
    protected $url;

    private function setUrl(Url $url)
    {
        $this->url = $url;
    }

    /**
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'url' => (string) $this->getUrl(),
        ];
    }
}
