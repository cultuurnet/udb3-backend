<?php

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Offer\Events\AbstractOrganizerUpdated;

final class OrganizerUpdated extends AbstractOrganizerUpdated
{
    use BackwardsCompatibleEventTrait;
}
