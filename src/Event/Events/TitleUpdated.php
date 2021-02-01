<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Offer\Events\AbstractTitleUpdated;

final class TitleUpdated extends AbstractTitleUpdated
{
    use BackwardsCompatibleEventTrait;
}
