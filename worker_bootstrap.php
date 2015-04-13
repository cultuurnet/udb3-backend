<?php

$app = require __DIR__ . '/bootstrap.php';

$app->boot();

// Allows to access the command bus in perform() of jobs that come out of the
// queue.
\CultuurNet\UDB3\CommandHandling\QueueJob::setCommandBus($app['event_command_bus']);

// We need to close the database connection here, otherwise
// the worker child process will kill it when the process finishes, and the
// next worker child process won't be able to use the database.
/** @var \Doctrine\DBAL\Connection $databaseConnection **/
$databaseConnection = $app['dbal_connection'];
$databaseConnection->close();
