<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2;

use ValueObjects\Web\Url;

trait UrlTransformingTrait
{
    /**
     * @return Url
     */
    public function transformUrl(Url $url)
    {
        return $url;
    }
}
