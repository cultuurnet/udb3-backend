<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use InvalidArgumentException;

final class OvernightNotAllowed extends InvalidArgumentException
{
    public const MESSAGE = 'overnight is only allowed when the event has term ' . EventTypeResolver::CAMP_OR_VACATION_TERM_ID;

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
