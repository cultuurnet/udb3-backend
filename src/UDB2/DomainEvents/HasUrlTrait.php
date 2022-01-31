<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;


use CultuurNet\UDB3\Model\ValueObject\Web\Url;

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
            'url' => $this->getUrl()->toString(),
        ];
    }
}
