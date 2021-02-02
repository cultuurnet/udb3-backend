<?php

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

interface UUIDParser
{
    /**
     * @param Url $url
     * @return UUID
     * @throws \InvalidArgumentException
     */
    public function fromUrl(Url $url);
}
