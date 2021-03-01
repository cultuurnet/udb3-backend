<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Clock;

use DateTimeInterface;

interface Clock
{
    public function getDateTime(): DateTimeInterface;
}
