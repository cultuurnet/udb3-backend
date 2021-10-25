<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Event;

use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\Import\Offer\LegacyOffer;

interface LegacyEvent extends LegacyOffer
{
    public function getLocation(): LocationId;

    public function getAudienceType(): AudienceType;
}
