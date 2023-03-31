<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

interface DummyLocationUpdated
{
    public function getDummyLocation(): ?DummyLocation;
}
