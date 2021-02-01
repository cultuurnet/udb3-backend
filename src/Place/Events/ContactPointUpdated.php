<?php

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Offer\Events\AbstractContactPointUpdated;

final class ContactPointUpdated extends AbstractContactPointUpdated
{
    use BackwardsCompatibleEventTrait;
}
