<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEventWithIri;

final class PlaceProjectedToJSONLD extends AbstractEventWithIri
{
    private bool $disableUpdatingEventsLocatedAtPlace = false;

    public function disableUpdatingEventsLocatedAtPlace(): PlaceProjectedToJSONLD
    {
        $c = clone $this;
        $c->disableUpdatingEventsLocatedAtPlace = true;
        return $c;
    }

    public function isUpdatingEventsLocatedAtPlaceDisabled(): bool
    {
        return $this->disableUpdatingEventsLocatedAtPlace;
    }
}
