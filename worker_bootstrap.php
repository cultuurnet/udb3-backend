<?php

require __DIR__ . '/bootstrap.php';

// Allows to access $app in perform() of queue jobs
// @todo Find a cleaner way to do this.
$GLOBALS['app'] = $app;
