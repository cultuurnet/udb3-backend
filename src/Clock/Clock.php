<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Clock;

use DateTimeInterface;

interface Clock
{
    /**
     * @return \DateTimeInterface
     */
    public function getDateTime();
}
