<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Event;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\Import\Offer\LegacyOffer;

/**
 * @deprecated Should no longer be used because all commands should use the VOs from the Model namespace.
 */
interface LegacyEvent extends LegacyOffer
{
    public function getLocation(): LocationId;
}
