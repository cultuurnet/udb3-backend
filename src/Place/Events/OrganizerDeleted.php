<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Offer\Events\AbstractOrganizerDeleted;

final class OrganizerDeleted extends AbstractOrganizerDeleted
{
    use BackwardsCompatibleEventTrait;
}
