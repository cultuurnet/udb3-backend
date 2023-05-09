<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Event\ValueObjects\DummyLocation;

interface DummyLocationUpdated
{
    public function getDummyLocation(): ?DummyLocation;
}
