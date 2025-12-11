<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Verenigingsloket\Enum;

enum VerenigingsloketConnectionStatus : string
{
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
}
