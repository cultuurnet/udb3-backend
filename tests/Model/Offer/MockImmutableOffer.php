<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Offer;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;

class MockImmutableOffer extends ImmutableOffer
{
    /**
     * @inheritdoc
     */
    protected function guardCalendarType(Calendar $calendar)
    {
        return;
    }
}
