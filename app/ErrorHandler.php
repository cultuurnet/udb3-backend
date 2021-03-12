<?php

namespace CultuurNet\UDB3\Silex;

use Throwable;

interface ErrorHandler
{
    public function handle(Throwable $throwable): void;
}
