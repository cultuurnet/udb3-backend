<?php

namespace CultuurNet\Broadway\EventHandling;

use Broadway\EventHandling\EventBusInterface;

interface ReplayModeEventBusInterface extends EventBusInterface
{
    public function startReplayMode();

    public function stopReplayMode();
}
