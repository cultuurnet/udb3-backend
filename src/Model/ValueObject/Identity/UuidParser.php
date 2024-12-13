<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

interface UuidParser
{
    /**
     * @throws \InvalidArgumentException
     */
    public function fromUrl(Url $url): Uuid;
}
