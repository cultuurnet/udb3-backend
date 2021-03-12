<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Throwable;

final class ChainedErrorHandler implements ErrorHandler
{
    /**
     * @var ErrorHandler[]
     */
    private $handlers;

    public function __construct(ErrorHandler ...$handlers)
    {
        $this->handlers = $handlers;
    }

    public function handle(Throwable $throwable): void
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($throwable);
        }
    }
}
