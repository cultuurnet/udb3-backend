<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2;

use ValueObjects\Web\Url;

interface UrlTransformerInterface
{
    /**
     * @return Url
     */
    public function transform(Url $url);
}
