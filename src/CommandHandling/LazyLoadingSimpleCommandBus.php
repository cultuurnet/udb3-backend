<?php

namespace CultuurNet\UDB3\Silex\CommandHandling;

use Broadway\CommandHandling\SimpleCommandBus;

class LazyLoadingSimpleCommandBus extends SimpleCommandBus
{
    /**
     * @var bool
     */
    private $first = true;

    /**
     * @var callable
     */
    private $beforeFirstDispatch;

    public function beforeFirstDispatch(callable $beforeFirstDispatch): void
    {
        $this->beforeFirstDispatch = $beforeFirstDispatch;
    }

    public function dispatch($command)
    {
        if ($this->first) {
            $this->first = false;
            call_user_func($this->beforeFirstDispatch, $this);
        }

        parent::dispatch($command);
    }
}
