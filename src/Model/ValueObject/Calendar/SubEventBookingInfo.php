<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class SubEventBookingInfo
{
    private Url $url;

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }
}
