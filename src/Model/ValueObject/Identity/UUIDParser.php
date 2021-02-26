<?php

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

interface UUIDParser
{
    /**
     * @return UUID
     * @throws \InvalidArgumentException
     */
    public function fromUrl(Url $url);
}
