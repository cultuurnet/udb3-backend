<?php

namespace CultuurNet\UDB3\Silex\Error;

use Throwable;

interface ErrorHandler
{
    public function handle(Throwable $throwable): void;
}
