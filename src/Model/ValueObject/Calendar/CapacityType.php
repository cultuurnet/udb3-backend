<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

enum CapacityType: string
{
    case Limited = 'Limited';
    case Unlimited = 'Unlimited';
}
