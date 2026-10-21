<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Repositories;

enum RegistrationSource: string
{
    case TopLevel = 'top-level';
    case SubEvent = 'subEvent';
}
