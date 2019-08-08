<?php

namespace CultuurNet\UDB3\UDB2;

use ValueObjects\Web\Url;

interface UrlTransformerInterface
{
    /**
     * @param Url $url
     * @return Url
     */
    public function transform(Url $url);
}
