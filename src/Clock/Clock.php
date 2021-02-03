<?php
/**
 * @file
 */

namespace CultuurNet\Clock;

use DateTimeInterface;

interface Clock
{
    /**
     * @return \DateTimeInterface
     */
    public function getDateTime();
}
