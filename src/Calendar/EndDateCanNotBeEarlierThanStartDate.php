<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

final class EndDateCanNotBeEarlierThanStartDate extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('End date can not be earlier than start date.');
    }
}
