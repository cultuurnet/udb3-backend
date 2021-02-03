<?php

namespace CultuurNet\UDB3\Broadway\EventHandling;

use Broadway\EventHandling\EventBusInterface;

interface ReplayModeEventBusInterface extends EventBusInterface
{
    public function startReplayMode();

    public function stopReplayMode();
}
