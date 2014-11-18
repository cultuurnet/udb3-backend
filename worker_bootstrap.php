<?php

$app = require __DIR__ . '/bootstrap.php';

$app->boot();

// Allows to access the command bus in perform() of jobs that come out of the
// queue.
\CultuurNet\UDB3\CommandHandling\QueueJob::setCommandBus($app['event_command_bus']);
